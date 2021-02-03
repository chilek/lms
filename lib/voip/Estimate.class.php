<?php

/*!
 * \class Estimate
 * \brief Class responsibility for return informations about calls.
 */
class Estimate
{
    private $provider = null;

    /*!
     * \brief Class constructor
     *
     * \param object $provider Method of providing information for the class.
     */
    public function __construct(VoipDataProvider $provider)
    {
        $this->provider = $provider;
    }

    /*!
     * \brief Function return max call time in seconds between two phone numbers.
     *
     * \param  string $caller caller phone number
     * \param  string $callee callee phone number
     * \return int    $time   maximum call time in seconds
     */
    public function getMaxCallTime($caller, $callee)
    {
        $provider = $this->provider;

        $customer          = $provider->getCustomerByPhone($caller);
        $prefix            = $provider->getLongestPrefix($callee, $customer['tariffid']);
        $group             = $provider->getGroupByPrefix($prefix, $customer['tariffid']);
        $rules             = $provider->getRules($customer['tariffruleid'], $group['id']);
        $customer['rules'] = $provider->getCustomerRuleStates($customer['voipaccountid'], $group['id']);

        $time = 0;

        while ($rules && $customer['balance']) {
            $rule = $provider->getBestRule($rules);
            $id   = $rule['ruleid'];

            if (isset($customer['rules'][$id]['units']) && $customer['rules'][$id]['units'] == 0) {
                unset($rules[$rule['ruleid']]);
                continue;
            }

            $units = (isset($customer['rules'][$id]['units'])) ? $customer['rules'][$id]['units'] : $rule['units'];
            $price = ($units * $rule['price'] * $rule['unit_size']) / 60;

            if ($price >= $customer['balance']) {
                return $time + $this->calcTime($customer['balance'], $rule['unit_size'], $rule['price']);
            } else {
                $time += $this->calcTime($price, $rule['unit_size'], $rule['price']);
                $customer['balance'] -= $price;
            }

            unset($rules[$rule['ruleid']]);
        }

        return $time + $this->calcTime($customer['balance'], $group['unit_size'], $group['price']);
    }

    /*!
     * \brief Function return cell cost between two phone numbers.
     *
     * \param  string $caller caller phone number
     * \param  string $callee callee phone number
     * \param  float  $time   connection time in seconds
     * \return array  $price  connection cost and used rules
     */
    public function getCallCost($caller, $callee, $time)
    {
        $provider = $this->provider;

        $customer          = $provider->getCustomerByPhone($caller);
        $prefix            = $provider->getLongestPrefix($callee, $customer['tariffid']);
        $group             = $provider->getGroupByPrefix($prefix, $customer['tariffid']);
        $rules             = $provider->getRules($customer['tariffruleid'], $group['id']);
        $customer['rules'] = $provider->getCustomerRuleStates($customer['voipaccountid'], $group['id']);

        $result = array();
        $result['price']      = 0;
        $result['used_rules'] = array();

        while ($rules && $time) {
            $rule = $provider->getBestRule($rules);
            $id   = $rule['ruleid'];

            if (isset($customer['rules'][$id]['units']) && $customer['rules'][$id]['units'] == 0) {
                unset($rules[$rule['ruleid']]);
                continue;
            }

            $units     = (isset($customer['rules'][$id]['units'])) ? $customer['rules'][$id]['units'] : $rule['units'];
            $rule_time = $units * $rule['unit_size'];
            $t         = ($rule_time >= $time) ? $time : $rule_time;

            $result['price'] += $this->calcCost($t, $rule['unit_size'], $rule['price']);
            $result['used_rules'][] = array('rule_id'     => $rule['ruleid'],
                                            'rule_units'  => $rule['units'],
                                            'used_units'  => ceil($t/$rule['unit_size']),
                                            'voip_acc_id' => $customer['voipaccountid']);

            $time -= $t;
            unset($rules[$rule['ruleid']]);
        }

        if ($time) {
            $result['price'] += $this->calcCost($time, $group['unit_size'], $group['price']);
        }

        return $result;
    }

    /*!
     * \brief Function calculate call cost.
     *
     * \param  int   $t call time
     * \param  int   $s unit size
     * \param  float $p price per minute
     * \return float
     */
    private function calcCost($t, $s, $p)
    {
        if ($s == 0) {
            throw new Exception('Unit size must be higher than 0.');
        }

        return ceil($t/$s) * (($p*$s) / 60);
    }

    /*!
     * \brief Function calculate call time.
     *
     * \param  int   $c account balance
     * \param  int   $s unit size
     * \param  float $p price per minute
     * \return float
     */
    private function calcTime($c, $s, $p)
    {
        if ($s == 0) {
            throw new Exception('Unit size must be higher than 0.');
        }

        // if price is equals to 0 then simulate infinity
        if ($p == 0) {
            return PHP_INT_MAX;
        }

        return floor(($c*60) / ($p*$s)) * $s;
    }
}
