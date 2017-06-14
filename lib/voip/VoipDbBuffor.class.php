<?php

/*!
 * \class VoipDbBuffor
 * \brief Class responsibile for optimize SQL queries to database.
 */
class VoipDbBuffor {

    /*!
     * \brief Container for cdr records.
     */
    private $cdr_container = array();

    /*!
     * \brief Used customer tariff rule states.
     * Units are summed and for excluding multiple UPDATE
     * queries for that same voip account id.
     */
    private $used_rules = array();

    /*!
     * \brief Method of providing information for the class.
     */
    private $provider = NULL;

    /*!
     * \brief Container for estimate class.
     */
    private $estimate = NULL;

    /*!
     * \brief Pattern for changing cdr text to array.
     */
    private $pattern = '';

    public function __construct(VoipDataProvider $p) {
        $this->provider = $p;
        $this->estimate = new Estimate($p);

        $this->pattern = '/^' . ConfigHelper::getConfig('voip.cdr_billing_record_format', '"(?<caller>(?:\+?[0-9]*|unavailable.*|anonymous.*))",' .
                         '"(.*)",' .
                         '"(?<callee>[0-9]*)",' .
                         '"(?<call_type>(?:incoming.*|outgoing.*))",' .
                         '"(.*)",' .
                         '"(.*)",' .
                         '"(.*)",' .
                         '"(.*)",' .
                         '"(.*)",' .
                         '"(?<call_start>(?<call_start_year>[0-9]{4})-(?<call_start_month>[0-9]{2})-(?<call_start_day>[0-9]{2}) (?<call_start_hour>[0-9]{2}):(?<call_start_min>[0-9]{2}):(?<call_start_sec>[0-9]{2}))",' .
                         '(?:"(?<call_answer>(?<call_answer_year>[0-9]{4})-(?<call_answer_month>[0-9]{2})-(?<call_answer_day>[0-9]{2}) (?<call_answer_hour>[0-9]{2}):(?<call_answer_min>[0-9]{2}):(?<call_answer_sec>[0-9]{2}))")?,' .
                         '"(?<call_end>(?<call_end_year>[0-9]{4})-(?<call_end_month>[0-9]{2})-(?<call_end_day>[0-9]{2}) (?<call_end_hour>[0-9]{2}):(?<call_end_min>[0-9]{2}):(?<call_end_sec>[0-9]{2}))",(?<totaltime>[0-9]*),(?<billedtime>[0-9]*),' .
                         '"(?<call_status>.*)",' .
                         '"(.*)",' .
                         '"(?<uniqueid>.*)".*') . '$/';
    }

    /*!
     * \brief Add CDR record to list.
     *
     * \param  array/string $c cdr data
     * \return boolean         when all good
     * \return string       $e error message 
     */
    public function appendCdr($c) {
        if (is_array($c))
            $cdr = $c;
        else if (is_string($c))
            $cdr = $this->parseRecord($c);

        $cdr['call_type']   = $this->parseCallType($cdr['call_type']);
        $cdr['call_status'] = $this->parseCallStatus($cdr['call_status']);

        switch ($cdr['call_type']) {
            case CALL_INCOMING: //no payments for incoming call
                $cdr['price'] = 0;
            break;

            case CALL_OUTGOING:
                if (isset($cdr['billedtime']) && $cdr['billedtime'] > 0) {
                    $info  = $this->estimate->getCallCost($cdr['caller'], $cdr['callee'], $cdr['billedtime']);

                if ($info['used_rules'])
                    foreach ($info['used_rules'] as $r) {
                        if (isset($this->used_rules[$cdr['caller']][$r['ruleid']]))
                                $this->used_rules[$cdr['caller']][$r['rule_id']]['used_units'] += $r['used_units'];
                            else {
                                $this->used_rules[$cdr['caller']][$r['rule_id']] = $r;
                            }
                        }

                    $cdr['price'] = $info['price'];
                } else {
                    $cdr['price'] = 0;
                }
            break;
        }

        if (($e = $this->validRecord($cdr)) === TRUE) {
            $this->cdr_container[] = $cdr;
            return TRUE;
        }

          return $e;
    }

    /*!
     * \brief Add used tariff rule units to list.
     *
     * \param int $v_id voip account id
     * \param int $r_id tariff rule id
     * \param int $u    used units
     */
    public function appendRuleState($v_id, $r_id, $u) {
        if (isset($this->rule_states[$v_id][$r_id])) {
            $this->rule_states[$v_id][$r_id] += $u;
        } else {
            $this->rule_states[$v_id][$r_id] = $u;
        }
    }

    /*!
     * \brief Method insert appended informations to data base.
     */
    public function insert() {
        $P         = $this->provider;
        $insert    = array();
        $cust_load = array();

        foreach ($this->cdr_container as $c) {
            $caller    = $P->getCustomerByPhone($c['caller']);
            if (empty($caller))
                $caller['phone'] = $c['caller'];
            $callee    = $P->getCustomerByPhone($c['callee']);
            if (empty($callee))
                $callee['phone'] = $c['callee'];
            $caller_gr = $P->getPrefixGroupName($caller['phone'], $caller['tariffid']);
            $callee_gr = $P->getPrefixGroupName($callee['phone'], $caller['tariffid']);

            $insert[] = "('" . $c['caller']             . "'," .
                        "'"  . $c['callee']             . "'," .
                               $c['call_start']         . ',' .
                               $c['totaltime']          . ',' .
                               $c['billedtime']         . ',' .
                               $c['price']              . ',' .
                               $c['call_status']        . ',' .
                               $c['call_type']          . ',' .
                               ($caller['voipaccountid'] ? $caller['voipaccountid'] : 'NULL') . ',' .
                               ($callee['voipaccountid'] ? $callee['voipaccountid'] : 'NULL') . ',' .
                               ((int) $caller['flags']) . ',' .
                               ((int) $callee['flags']) . ',' .
                               ($caller_gr               ? "'$caller_gr'"           : 'NULL') . ',' .
                               ($callee_gr               ? "'$callee_gr'"           : 'NULL') . ',' .
                               $c['uniqueid']           . ')';

            if ($c['price'] > 0) {
                if (isset($cust_load[$caller['voipaccountid']]))
                    $cust_load[$caller['voipaccountid']] += $c['price'];
                else
                    $cust_load[$caller['voipaccountid']] = $c['price'];
            }
        }

        $DB = LMSDB::getInstance();
        $DB->BeginTrans();

        //insert cdr records
        $DB->Execute('INSERT INTO voip_cdr
                         (caller, callee, call_start_time, totaltime, billedtime,
                          price, status, type, callervoipaccountid, calleevoipaccountid, caller_flags,
                          callee_flags, caller_prefix_group, callee_prefix_group, uniqueid)
                      VALUES ' . implode(',', $insert));

        //update customer account balance
        foreach ($cust_load as $k=>$v) {
            $DB->Execute("UPDATE voipaccounts SET balance=balance-$v WHERE id = " . $caller['voipaccountid']);
        }

        //update customer tariff rules
        $new_rules = array();
        foreach ($this->used_rules as $rule_list) {

            foreach ($rule_list as $r) {
                $exists = $DB->GetOne('SELECT 1
                                       FROM 
                                          voip_rule_states
                                        WHERE
                                             voip_account_id = ? AND
                                             rule_id         = ?',
                                        array($r['voip_acc_id'], $r['rule_id']));

                if ($exists) {
                    $DB->Execute('UPDATE voip_rule_states
                                  SET units_left = units_left - ?
                                  WHERE
                                      voip_account_id = ? AND
                                      rule_id         = ?',
                                  array($r['used_units'], $r['voip_acc_id'], $r['rule_id']));
                } else {
                    $new_rules[] = '(' . $r['voip_acc_id'] . ','
                                       . $r['rule_id']     . ','
                                       . ($r['rule_units']-$r['used_units']) . ')';
                }
            }
        }

        if ($new_rules) {
            $DB->Execute('INSERT INTO voip_rule_states
                              (voip_account_id, rule_id, units_left)
                          VALUES ' . implode(',', $new_rules));
        }

        $DB->CommitTrans();
        $this->cdr_container = array();
        $this->used_rules    = array();
    }

    /*!
     * \brief Change call type (string) to defined number (int).
     *
     * \param string $type call type
     * \return int       number assigned to call type
     * \return exception when can't match string to call type
     */
    public function parseCallType($type) {
        if (preg_match("/incoming/i", $type))
            return CALL_INCOMING;

        if (preg_match("/outgoing/i", $type))
            return CALL_OUTGOING;

        return 'incorrect';
    }

    /*!
     * \brief Change call status (string) to defined number (int).
     *
     * \param string $type call type
     * \return int number assigned to call status
     * \return php_function die when can't match string to call type
     */
    public function parseCallStatus($type) {
        if (preg_match("/busy/i", $type))
            return CALL_BUSY;

        if (preg_match("/answered/i", $type))
            return CALL_ANSWERED;

        if (preg_match("/(noanswer|no answer)/i", $type))
            return CALL_NO_ANSWER;

        if (preg_match("/fail/i", $type))
            return CALL_SERVER_FAILED;

        return 'incorect';
    }

    /*!
     * \brief Change text to asociative array.
     *
     * \param  string $r string to parse
     * \return array     associative array with paremeters
     */
    private function parseRecord($r) {
        preg_match($this->pattern, $r, $matches);

        $matches['call_start'] = mktime($matches['call_start_hour'],
                                        $matches['call_start_min'],
                                        $matches['call_start_sec'],
                                        $matches['call_start_month'],
                                        $matches['call_start_day'],
                                        $matches['call_start_year']);

        foreach ($matches as $k=>$v) {
            if (is_numeric($k))
                unset($matches[$k]);
            else if (!$matches[$k])
                $matches[$k] = 0;
        }

        return $matches;
    }

    /*!
     * \brief Valid array with cdr data.
     *
     * \param  array  $r cdr record
     * \return true      when everything is fine
     * \return string    first founded error description
     */
    private function validRecord($r) {

        if (empty($r['caller']))
            return "Caller phone number isn't set.";
        if (!preg_match("/([0-9]+|anonymous|unavailable)/", $r['caller']))
            return "Caller phone number has incorrect format.";

        if (empty($r['callee']))
            return "Callee phone number isn't set.";
        if (!is_numeric($r['callee']))
            return "Callee phone number has incorrect format.";

        if (empty($r['call_type']))
            return "Call type isn't set.";
        else if (!is_int($r['call_type']))
            return "Call type has incorrect format.";

        if (!isset($r['call_start']))
            return "Call start isn't set.";
        else if (!is_numeric($r['call_start']))
            return "Call start time has incorrect format.";

        if (!isset($r['totaltime']))
            return "Totaltime isn't set.";
        else if (!is_numeric($r['totaltime']))
            return "Totaltime has incorrect format.";

        if (!isset($r['billedtime']))
            return "Billedtime isn't set.";
        else if (!is_numeric($r['billedtime']))
            return "Billedtime has incorract format.";

        if (empty($r['uniqueid']))
            return "Call unique id isn't set.";
        else if (!preg_match("/[0-9]*\.[0-9]*/i", $r['uniqueid']))
            return "Call unique id has incorrect format.";

        if (!isset($r['price']))
            return "Price ist't set.";
        else if (!is_numeric($r['price']))
            return "Price has incorrect format.";

        return true;
    }
}

?>
