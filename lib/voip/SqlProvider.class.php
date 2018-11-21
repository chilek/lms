<?php

/*! 
 * \class SqlProvider
 * \brief Data provider for VoIP classes based on SQL queries.
 */
class SqlProvider extends VoipDataProvider {

    private static $instance = null;

    /*!
     * \brief Array with cache for SqlProvider::getGroupByPrefix() method.
     */
    private $prefix_group = array();

    /*!
     * \brief Array with cache for SqlProvider::findLongestPrefix() method.
     */
    private $number_tariff = array();

    /*!
     * \brief Array with cache for SqlProvider::getVoipAccByPhone() method.
     */
    private $number_groupname = array();

    /*!
     * \brief Function return instance of SQL provider.
     */
    public static function getInstance() {
        if ( self::$instance === null ) {
            self::$instance = new SqlProvider();
        }

        return self::$instance;
    }

    /*!
     * \brief Return informations about prefix group belongs to tariff by prefix.
     *
     * \param  int    $t_id   tariff id
     * \param  string $prefix prefix
     * \return int    $group  prefix group id
     * \throws NULL           when prefix don't belongs to tariff or tariff doesn't exists
     */
    public function getGroupByPrefix($prefix, $t_id) {
        if (isset($this->prefix_group[$prefix.'|'.$t_id]))
            return $this->prefix_group[$prefix.'|'.$t_id];

        $DB = LMSDB::getInstance();
        $g = $DB->GetRow('SELECT
                            vp.groupid as id, vpg.price,
                            vpg.unitsize as unit_size
                          FROM
                            voip_prefixes vp
                            LEFT JOIN voip_price_groups vpg 
                            on vp.groupid = vpg.prefix_group_id
                          WHERE
                            vp.prefix = ? AND
                            vpg.voip_tariff_id = ?',
                          array($prefix, $t_id));

        $this->prefix_group[$prefix.'|'.$t_id] = $g;

        return $g;
    }

    /*!
     * \brief Get informations about customer by phone number.
     *
     * \param  string $number customer phone number
     * \return array          informations about customer
     */
    public function getCustomerByPhone($number) {
        $DB = LMSDB::getInstance();
        $c = $DB->GetRow('SELECT
                            va.id as voipaccountid, vn.phone, va.balance,
                            t.voip_tariff_id as tariffid,
                            t.voip_tariff_rule_id as tariffruleid, va.flags
                          FROM
                            voipaccounts va
                            JOIN voip_numbers vn ON vn.voip_account_id = va.id
                            JOIN assignments a ON a.customerid = va.ownerid
                            JOIN tariffs t ON t.id = a.tariffid
                            JOIN voip_number_assignments vna ON vna.number_id = vn.id AND vna.assignment_id = a.id
                          WHERE
                            vn.phone ?LIKE? ? AND
                            t.type = ?',
                          array($number, SERVICE_PHONE));

        return $c;
    }

    /*!
     * \brief Find most suited prefix for phone number.
     *
     * \param  string $number callee phone number
     * \param  int    $t_id   tariff id
     * \return string longest matched prefix
     */
    public function getLongestPrefix($number, $t_id) {
        $DB = LMSDB::getInstance();

        if (isset($this->number_tariff[$number.'|'.$t_id]))
            return $this->number_tariff[$number.'|'.$t_id];

        $p = $DB->GetOne("SELECT
                            vp.prefix
                          FROM
                            voip_prefixes vp
                            LEFT JOIN voip_price_groups vpg on
                            vp.groupid = vpg.prefix_group_id
                          WHERE 
                            ? ?LIKE? (vp.prefix || '%') AND
                            vpg.voip_tariff_id = ?
                          ORDER BY
                            vp.prefix DESC",
                          array($number, $t_id));

        $this->number_tariff[$number.'|'.$t_id] = $p;

        return $p;
    }

    /*!
     * \brief Return array with tariff rules to use.
     *
     * \param  int   $rulegroupid   tariff rule group id
     * \param  int   $prefixgroupid prefix group id
     * \return array                asociative array
     */
    public function getRules($rulegroupid, $groupid) {
        $DB = LMSDB::getInstance();
        $tmp = $DB->GetAll('SELECT
                              id, settings
                            FROM
                              voip_rules
                            WHERE
                              rule_group_id   = ? AND
                              prefix_group_id = ?',
                            array($rulegroupid, $groupid));

        if (!$tmp)
            return array();

        $rules = array();
        foreach($tmp as $v) {
            $s = unserialize($v['settings']);
            $s['ruleid'] = $v['id'];

            $rules[$v['id']] = $s;
        }

        return $rules;
    }

    /*!
     * \brief Return customer rule states of specific group id.
     *
     * \param  int   $vid     voip account id
     * \param  int   $groupid prefix group id
     * \return array $s       customer rule states
     */
    public function getCustomerRuleStates($vid, $groupid) {
        $DB = LMSDB::getInstance();
        $s = $DB->GetAll('SELECT
                            vrs.rule_id, vrs.units_left
                          FROM
                            voipaccounts va
                            LEFT JOIN voip_rule_states vrs on va.id = vrs.voip_account_id
                            LEFT JOIN voip_rules vr on vrs.rule_id = vr.id
                          WHERE
                            va.id            = ? AND
                            vr.rule_group_id = ?',
                          array($vid, $groupid));

        return $s;
    }

    /*!
     * \brief Return voip account informations by phone number.
     *
     * \param  string $number phone number
     * \return array  $i      informations about voip account
     *                        array(voip_id, flags, group)
     */
    public function getPrefixGroupName($number, $t_id) {
         if (isset($this->number_groupname[$number.'|'.$t_id]))
            return $this->number_groupname[$number.'|'.$t_id];

        $DB = LMSDB::getInstance();

        $pref = $this->getLongestPrefix($number, $t_id);

        $i = $DB->GetOne('SELECT
                             vpg.name
                          FROM
                             voip_prefixes vp 
                             LEFT JOIN voip_prefix_groups vpg ON vp.groupid = vpg.id
                          WHERE
                              prefix ?LIKE? ?',
                          array($pref));

        $this->number_groupname[$number.'|'.$t_id] = $i;

        return $i;
    }
}

?>
