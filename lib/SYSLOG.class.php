<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

class SYSLOG
{
    public const RES_USER = 1;
    public const RES_ASSIGN = 2;
    public const RES_LIAB = 3;
    public const RES_NODEASSIGN = 4;
    public const RES_NODE = 5;
    public const RES_MAC = 6;
    public const RES_CUST = 7;
    public const RES_CUSTCONTACT = 8;
    public const RES_IMCONTACT = 9;
    public const RES_CUSTGROUP = 10;
    public const RES_CUSTASSIGN = 11;
    public const RES_TARIFF = 12;
    public const RES_NODEGROUP = 13;
    public const RES_NODEGROUPASSIGN = 14;
    public const RES_TAX = 15;
    public const RES_NUMPLAN = 16;
    public const RES_NUMPLANASSIGN = 17;
    public const RES_DIV = 18;
    public const RES_COUNTRY = 19;
    public const RES_STATE = 20;
    public const RES_ZIP = 21;
    public const RES_HOST = 22;
    public const RES_DAEMONINST = 23;
    public const RES_DAEMONCONF = 24;
    public const RES_CASHSOURCE = 25;
    public const RES_UICONF = 26;
    public const RES_PROMO = 27;
    public const RES_PROMOSCHEMA = 28;
    public const RES_PROMOASSIGN = 29;
    public const RES_EXCLGROUP = 30;
    public const RES_DBBACKUP = 31;
    public const RES_PAYMENT = 32;
    public const RES_CASHIMPORT = 33;
    public const RES_SOURCEFILE = 34;
    public const RES_CASH = 35;
    public const RES_DOC = 36;
    public const RES_INVOICECONT = 37;
    public const RES_RECEIPTCONT = 38;
    public const RES_DNOTECONT = 39;
    public const RES_CASHREG = 40;
    public const RES_CASHRIGHT = 41;
    public const RES_CASHREGHIST = 42;
    public const RES_NETWORK = 43;
    public const RES_NETDEV = 44;
    public const RES_NETLINK = 45;
    public const RES_MGMTURL = 46;
    public const RES_TMPL = 47;
    public const RES_RADIOSECTOR = 48;
    public const RES_USERGROUP = 49;
    public const RES_USERASSIGN = 50;
    public const RES_TARIFFTAG = 51;
    public const RES_TARIFFASSIGN = 52;
    public const RES_EVENT = 53;
    public const RES_EVENTASSIGN = 54;
    public const RES_ADDRESS = 55;
    public const RES_TICKET = 56;
    public const RES_DOCATTACH = 57;
    public const RES_DOCCONTENT = 58;
    public const RES_CUSTCONSENT = 59;
    public const RES_CUSTNOTE = 60;
    public const RES_ROUTEDNET = 61;
    public const RES_VLAN = 62;
    public const RES_NUMPLANUSER = 63;
    public const RES_NETDEV_MAC = 64;
    public const RES_VOIP_ACCOUNT = 65;
    public const RES_VOIP_ACCOUNT_NUMBER = 66;
    public const RES_NETNODE = 67;
    public const RES_TARIFF_PRICE_VARIANT = 68;
    public const RES_TICKET_MESSAGE = 69;
    public const RES_QUEUE = 70;

    public const OPER_ADD = 1;
    public const OPER_DELETE = 2;
    public const OPER_UPDATE = 3;

    public const OPER_GET = 4;
    public const OPER_DBBACKUPRECOVER = 240;
    public const OPER_USERPASSWDCHANGE = 251;
    public const OPER_USERNOACCESS = 252;
    public const OPER_USERLOGFAIL = 253;
    public const OPER_USERLOGIN = 254;
    public const OPER_USERLOGOUT = 255;
    public const OPER_USERAUTCHANGE = 256;

    private static $resources = array(
        self::RES_USER => 'user<!syslog>',
        self::RES_ASSIGN => 'assignment<!syslog>',
        self::RES_LIAB => 'liability<!syslog>',
        self::RES_NODEASSIGN => 'node assignment<!syslog>',
        self::RES_NODE => 'node<!syslog>',
        self::RES_MAC => 'mac<!syslog>',
        self::RES_CUST => 'customer<!syslog>',
        self::RES_CUSTCONTACT => 'customer contact<!syslog>',
        self::RES_IMCONTACT => 'IM contact<!syslog>',
        self::RES_CUSTGROUP => 'customer group<!syslog>',
        self::RES_CUSTASSIGN => 'customer assignment<!syslog>',
        self::RES_TARIFF => 'tariff<!syslog>',
        self::RES_NODEGROUP => 'node group<!syslog>',
        self::RES_NODEGROUPASSIGN => 'node group assignment<!syslog>',
        self::RES_TAX => 'tax rate<!syslog>',
        self::RES_NUMPLAN => 'number plan<!syslog>',
        self::RES_NUMPLANASSIGN => 'number plan assignment<!syslog>',
        self::RES_DIV => 'division<!syslog>',
        self::RES_COUNTRY => 'country<!syslog>',
        self::RES_STATE => 'state<!syslog>',
        self::RES_ZIP => 'zip code<!syslog>',
        self::RES_HOST => 'host<!syslog>',
        self::RES_DAEMONINST => 'daemon instance<!syslog>',
        self::RES_DAEMONCONF => 'daemon instance setting<!syslog>',
        self::RES_CASHSOURCE => 'cash import source<!syslog>',
        self::RES_UICONF => 'configuration setting<!syslog>',
        self::RES_PROMO => 'promotion<!syslog>',
        self::RES_PROMOSCHEMA => 'promotion schema<!syslog>',
        self::RES_PROMOASSIGN => 'promotion schema assignment<!syslog>',
        self::RES_EXCLGROUP => 'customer group exclusion<!syslog>',
        self::RES_DBBACKUP => 'database backup<!syslog>',
        self::RES_PAYMENT => 'payment<!syslog>',
        self::RES_CASHIMPORT => 'imported financial operation<!syslog>',
        self::RES_SOURCEFILE => 'imported file with financial operations<!syslog>',
        self::RES_CASH => 'financial operation<!syslog>',
        self::RES_DOC => 'document<!syslog>',
        self::RES_INVOICECONT => 'invoice contents<!syslog>',
        self::RES_RECEIPTCONT => 'receipt contents<!syslog>',
        self::RES_DNOTECONT => 'debit note contents<!syslog>',
        self::RES_CASHREG => 'cash registry<!syslog>',
        self::RES_CASHRIGHT => 'cash registry rights<!syslog>',
        self::RES_CASHREGHIST => 'cash registry history<!syslog>',
        self::RES_NETWORK => 'network<!syslog>',
        self::RES_NETDEV => 'network device<!syslog>',
        self::RES_NETLINK => 'network link<!syslog>',
        self::RES_MGMTURL => 'management URL<!syslog>',
        self::RES_TMPL => 'template<!syslog>',
        self::RES_RADIOSECTOR => 'radio sector<!syslog>',
        self::RES_USERGROUP => 'user group<!syslog>',
        self::RES_USERASSIGN => 'user assignment<!syslog>',
        self::RES_TARIFFTAG => 'tariff tag<!syslog>',
        self::RES_TARIFFASSIGN => 'tariff assignment<!syslog>',
        self::RES_EVENT => 'event<!syslog>',
        self::RES_EVENTASSIGN => 'event assignment<!syslog>',
        self::RES_ADDRESS => 'address<!syslog>',
        self::RES_TICKET => 'ticket<!syslog>',
        self::RES_DOCATTACH => 'document attachment<!syslog>',
        self::RES_DOCCONTENT => 'document content<!syslog>',
        self::RES_CUSTCONSENT => 'customer consent<!syslog>',
        self::RES_CUSTNOTE => 'customer note<!syslog>',
        self::RES_ROUTEDNET => 'routed network<!syslog>',
        self::RES_VLAN => 'vlan<!syslog>',
        self::RES_NUMPLANUSER => 'number plan user<!syslog>',
        self::RES_NETDEV_MAC => 'network device mac<!syslog>',
        self::RES_VOIP_ACCOUNT => 'VoIP account<!syslog>',
        self::RES_VOIP_ACCOUNT_NUMBER => 'VoIP account number<!syslog>',
        self::RES_NETNODE => 'network node<!syslog>',
        self::RES_TARIFF_PRICE_VARIANT => 'tariff price variant<!syslog>',
        self::RES_TICKET_MESSAGE => 'ticket message<!syslog>',
        self::RES_QUEUE => 'queue<!syslog>',
    );
    private static $resource_keys = array(
        self::RES_USER => 'userid',
        self::RES_ASSIGN => 'assignmentid',
        self::RES_LIAB => 'liabilityid',
        self::RES_NODEASSIGN => 'nodeassignmentid',
        self::RES_NODE => 'nodeid',
        self::RES_MAC => 'macid',
        self::RES_CUST => 'customerid',
        self::RES_CUSTCONTACT => 'customercontactid',
        self::RES_IMCONTACT => 'imessengerid',
        self::RES_CUSTGROUP => 'customergroupid',
        self::RES_CUSTASSIGN => 'customerassignmentid',
        self::RES_TARIFF => 'tariffid',
        self::RES_NODEGROUP => 'nodegroupid',
        self::RES_NODEGROUPASSIGN => 'nodegroupassignmentid',
        self::RES_TAX => 'taxrateid',
        self::RES_NUMPLAN => 'numberplanid',
        self::RES_NUMPLANASSIGN => 'numberplanassignmentid',
        self::RES_DIV => 'divisionid',
        self::RES_COUNTRY => 'countryid',
        self::RES_STATE => 'stateid',
        self::RES_ZIP => 'zipcodeid',
        self::RES_HOST => 'hostid',
        self::RES_DAEMONINST => 'daemoninstanceid',
        self::RES_DAEMONCONF => 'daemonconfigid',
        self::RES_CASHSOURCE => 'cashsourceid',
        self::RES_UICONF => 'uiconfigid',
        self::RES_PROMO => 'promotionid',
        self::RES_PROMOSCHEMA => 'promotionschemaid',
        self::RES_PROMOASSIGN => 'promotionassignmentid',
        self::RES_EXCLGROUP => 'excludedgroupid',
        self::RES_DBBACKUP => null,
        self::RES_PAYMENT => 'paymentid',
        self::RES_CASHIMPORT => 'importid',
        self::RES_SOURCEFILE => 'sourcefileid',
        self::RES_CASH => 'cashid',
        self::RES_DOC => 'documentid',
        self::RES_INVOICECONT => null,
        self::RES_RECEIPTCONT => null,
        self::RES_DNOTECONT => 'debitnotecontentid',
        self::RES_CASHREG => 'cashregistryid',
        self::RES_CASHRIGHT => 'cashrightid',
        self::RES_CASHREGHIST => 'cashreghistoryid',
        self::RES_NETWORK => 'networkid',
        self::RES_NETDEV => 'networkdeviceid',
        self::RES_NETLINK => 'networklinkid',
        self::RES_MGMTURL => 'managementurlid',
        self::RES_TMPL => 'templateid',
        self::RES_RADIOSECTOR => 'radiosectorid',
        self::RES_USERGROUP => 'usergroupid',
        self::RES_USERASSIGN => 'userassignmentid',
        self::RES_TARIFFTAG => 'tarifftagid',
        self::RES_TARIFFASSIGN => 'tariffassignmentid',
        self::RES_EVENT => 'eventid',
        self::RES_EVENTASSIGN => 'eventassignmentid',
        self::RES_ADDRESS => 'address_id',
        self::RES_TICKET => 'ticketid',
        self::RES_DOCATTACH => 'documentattachmentid',
        self::RES_DOCCONTENT => 'documentcontentid',
        self::RES_CUSTCONSENT => 'customerconsentid',
        self::RES_CUSTNOTE => 'customernoteid',
        self::RES_ROUTEDNET => 'routednetworkid',
        self::RES_VLAN => 'vlanid',
        self::RES_NUMPLANUSER => 'numberplanuserid',
        self::RES_NETDEV_MAC => 'networkdevicemacid',
        self::RES_VOIP_ACCOUNT => 'voipaccountid',
        self::RES_VOIP_ACCOUNT_NUMBER => 'voipnumberid',
        self::RES_NETNODE => 'netnodeid',
        self::RES_TARIFF_PRICE_VARIANT => 'tariffpricevariantid',
        self::RES_TICKET_MESSAGE => 'ticketid',
        self::RES_QUEUE => 'queueid',
    );
    private static $operations = array(
        self::OPER_ADD => 'addition<!syslog>',
        self::OPER_DELETE => 'deletion<!syslog>',
        self::OPER_UPDATE => 'update<!syslog>',
        self::OPER_GET => 'get<!syslog>',
        self::OPER_DBBACKUPRECOVER => 'recover<!syslog>',
        self::OPER_USERPASSWDCHANGE => 'password change<!syslog>',
        self::OPER_USERNOACCESS => 'access denied<!syslog>',
        self::OPER_USERLOGFAIL => 'log in failed<!syslog>',
        self::OPER_USERLOGIN => 'log in<!syslog>',
        self::OPER_USERLOGOUT => 'log out<!syslog>',
    );
    private static $operation_styles = array(
        self::OPER_ADD => 'color: green',
        self::OPER_DELETE => 'color: red',
        self::OPER_UPDATE => 'color: blue',
        self::OPER_GET => 'color: black',
        self::OPER_DBBACKUPRECOVER => 'color: aqua',
        self::OPER_USERPASSWDCHANGE => 'color: navy',
        self::OPER_USERNOACCESS => 'color: purple',
        self::OPER_USERLOGFAIL => 'color: crimson',
        self::OPER_USERLOGIN => 'color: gray',
        self::OPER_USERLOGOUT => 'color: darkgray',
    );

    private static $syslog = null;

    private static $resourceKeyByName = null;

    private $DB;
    private $userid = null;
    private $transid = 0;
    private $module = '';

    public static function getInstance($force = false)
    {
        if (self::$syslog == null && ($force || ConfigHelper::checkConfig('logs.enabled'))) {
            self::$syslog = new SYSLOG();
            foreach (self::$resource_keys as $key => $name) {
                if (isset($name)) {
                    self::$resourceKeyByName[$name] = $key;
                }
            }
        }
        return self::$syslog;
    }

    public function __construct()
    {
        $this->DB = LMSDB::getInstance();
    }

    public static function getAllResources()
    {
        $resources = array();
        foreach (self::$resources as $resourcetype => $resourcename) {
            $resources[$resourcetype] = trans($resourcename);
        }
        asort($resources);
        return $resources;
    }

    public static function getResourceName($resourceid)
    {
        return trans(self::$resources[$resourceid]);
    }

    public static function getResourceKey($resourceid)
    {
        return self::$resource_keys[$resourceid];
    }

    public static function getOperationName($operationid)
    {
        return trans(self::$operations[$operationid]);
    }

    public static function getOperationStyle($operationid)
    {
        return self::$operation_styles[$operationid];
    }

    public function NewTransaction($module, $userid = null)
    {
        $currentuserid = Auth::GetCurrentUser();
        if ($currentuserid) {
            $this->userid = $currentuserid;
        } elseif (!is_null($userid)) {
            $this->userid = intval($userid);
        }

        $this->module = $module;
        $this->transid = 0;
        //$this->DB->Execute('INSERT INTO logtransactions (time, userid, module)
        //  VALUES(?NOW?, ?, ?)', array($this->userid, $this->module));
        //$this->transid = $this->DB->GetLastInsertID('logtransactions');
    }

    public function AddMessage($resource, $operation, $data = null, $keys = null)
    {
        if (empty($this->transid) && empty($this->module)) {
            return;
        }
        if (empty($this->transid)) {
            $this->DB->Execute('INSERT INTO logtransactions (time, userid, module)
				VALUES(?NOW?, ?, ?)', array($this->userid, $this->module));
            $this->transid = $this->DB->GetLastInsertID('logtransactions');
        }

        $this->DB->Execute('INSERT INTO logmessages (transactionid, resource, operation)
			VALUES(?, ?, ?)', array($this->transid, $resource, $operation));
        $id = $this->DB->GetLastInsertID('logmessages');
        if (!empty($data) && is_array($data)) {
            foreach ($data as $resourcetype => $val) {
                if (((is_int($resourcetype) && isset(self::$resource_keys[$resourcetype]))
                || (!is_int($resourcetype) && is_array($keys) && in_array($resourcetype, $keys)))
                && (is_int($val) || !isset($val) || preg_match('/^[0-9]+$/', $val))) {
                    if (!isset($val)) {
                        $val = 0;
                    }
                    $this->DB->Execute(
                        'INSERT INTO logmessagekeys (logmessageid, name, value)
						VALUES(?, ?, ?)',
                        array($id, is_int($resourcetype) ? self::$resource_keys[$resourcetype] : $resourcetype, $val)
                    );
                } else {
                    $this->DB->Execute(
                        'INSERT INTO logmessagedata (logmessageid, name, value)
						VALUES(?, ?, ?)',
                        array($id, $resourcetype, $val)
                    );
                }
            }
        }
    }

    public function GetTransactions($params)
    {
        $key = (!empty($params['key']) ? $params['key'] : '');
        $value = (isset($params['value']) && preg_match('/^[0-9]+$/', $params['value']) ? $params['value'] : '');
        $propname = (!empty($params['propname']) ? $params['propname'] : '');
        $propvalue = ($params['propvalue'] ?? '');
        $userid = (!empty($params['userid']) ? intval($params['userid']) : null);
        $module = isset($params['module']) && strlen($params['module']) ? $params['module'] : null;
        $offset = (!empty($params['offset']) ? intval($params['offset']) : 0);
        $limit = (!empty($params['limit']) ? intval($params['limit']) : 20);
        $order = (isset($params['order']) && preg_match('/ASC/i', $params['order']) ? 'ASC' : 'DESC');
        $datefrom = (!empty($params['datefrom']) ? intval($params['datefrom']) : 0);
        $dateto = (!empty($params['dateto']) ? intval($params['dateto']) : 0);
        $resource = (!empty($params['resource']) ? $params['resource'] : 0);
        $details = !empty($params['details']);

        $args = array();
        $where = array();
        $joins = array();
        if ($key != '' && strval($value) != '') {
            $joins[] = 'JOIN logmessagekeys lmk ON lmk.logmessageid = lm.id';
            $where[] = 'lmk.name = ? AND lmk.value ' . (empty($value) ? '>' : '=') . ' ?';
            $args[] = $key;
            $args[] = $value;
        }
        if ($propname != '' && $propvalue != '') {
            $joins[] = 'JOIN logmessagedata lmd ON lmd.logmessageid = lm.id';
            $where[] = 'lmd.name = ? AND lmd.value ?LIKE? ?';
            $args[] = $propname;
            $args[] = '%' . $propvalue . '%';
        }
        if ($resource) {
            $where[] = 'lm.resource = ?';
            $args[] = $resource;
        }
        if ($userid) {
            $where[] = 'lt.userid = ?';
            $args[] = $userid;
        }
        if ($module) {
            $where[] = 'lt.module = ?';
            $args[] = $module;
        }
        if ($datefrom) {
            $where[] = 'lt.time >= ?';
            $args[] = $datefrom;
        }
        if ($dateto) {
            $where[] = 'lt.time <= ?';
            $args[] = $dateto;
        }
        $trans = $this->DB->GetAll(
            'SELECT DISTINCT lt.id, lt.time, lt.userid, u.login, lt.module FROM logtransactions lt
            JOIN logmessages lm ON lm.transactionid = lt.id
            LEFT JOIN users u ON u.id = lt.userid ' . implode(' ', $joins)
            . (!empty($where) ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY lt.id ' . $order
            . ' LIMIT ' . $limit . (!empty($offset) ? ' OFFSET ' . $offset : ''),
            $args
        );

        if ($details && !empty($trans)) {
            $tids = Utils::array_column($trans, 'id');

            $messages = $this->DB->GetAll(
                'SELECT lm.transactionid, lm.id, resource, operation FROM logmessages lm
                WHERE lm.transactionid IN ?
                ORDER BY transactionid, lm.id',
                array($tids)
            );
            $transaction_messages = array();
            if (!empty($messages)) {
                foreach ($messages as $message) {
                    $transactionid = $message['transactionid'];
                    $messageid = $message['id'];
                    if (!isset($transaction_messages[$transactionid][$messageid])) {
                        $transaction_messages[$transactionid][$messageid] = array();
                    }
                    $transaction_messages[$transactionid][$messageid] = $message;
                }
            }

            $keys = $this->DB->GetAll(
                'SELECT m.transactionid, m.id, k.name, k.value FROM logmessagekeys k
                JOIN logmessages m ON m.id = k.logmessageid
                WHERE m.transactionid IN ?
                ORDER BY m.transactionid, m.id, k.name',
                array($tids)
            );
            $transaction_keys = array();
            if (!empty($keys)) {
                foreach ($keys as $key) {
                    $transactionid = $key['transactionid'];
                    if (!isset($transaction_keys[$transactionid])) {
                        $transaction_keys[$transactionid] = array();
                    }
                    $transaction_keys[$transactionid][] = $key;
                }
            }

            $data = $this->DB->GetAll(
                'SELECT m.transactionid, m.id, d.name, d.value FROM logmessagedata d
                JOIN logmessages m ON m.id = d.logmessageid
                WHERE m.transactionid IN ?
                ORDER BY m.transactionid, m.id, d.name',
                array($tids)
            );
            $transaction_data = array();
            if (!empty($data)) {
                foreach ($data as $d) {
                    $transactionid = $d['transactionid'];
                    if (!isset($transaction_data[$transactionid])) {
                        $transaction_data[$transactionid] = array();
                    }
                    $transaction_data[$transactionid][] = $d;
                }
            }

            foreach ($trans as &$tran) {
                $this->_decodeTransaction(
                    $tran,
                    $transaction_messages[$tran['id']] ?? null,
                    $transaction_keys[$tran['id']] ?? null,
                    $transaction_data[$tran['id']] ?? null
                );
            }
            unset($tran);
        }

        return $trans;
    }

    public function DecodeMessageData(&$data)
    {
        global $PERIODS, $PAYTYPES, $LINKTYPES, $LINKSPEEDS, $CSTATUSES;

        if (!isset($data['name'])) {
            $data['name'] = '';
        }

        switch ($data['name']) {
            case 'datefrom':
            case 'dateto':
            case 'issuedto':
            case 'consentdate':
            case 'time':
            case 'sdate':
            case 'cdate':
                $data['value'] = !empty($data['value']) ? $data['value'] = date('Y.m.d', $data['value']) : $data['value'];
                break;
            case 'at':
                $data['value'] = strlen($data['value']) > 6 ? date('Y.m.d', $data['value']) : $data['value'];
                break;
            case 'period':
                $data['value'] = $PERIODS[$data['value']];
                break;
            case 'paytype':
                $data['value'] = empty($data['value']) ? trans('default') : trans($PAYTYPES[$data['value']]);
                break;
            case 'paytime':
                $data['value'] = $data['value'] == -1 ? trans('default') : $data['value'];
                break;
            case 'invoice':
            case 'issuetoendofyear':
            case 'access':
            case 'warning':
            case 'chkmac':
            case 'halfduplex':
                $data['value'] = $data['value'] == 1 ? trans('yes') : trans('no');
                break;
            case 'type':
                if ($data['resource'] == self::RES_CUST) {
                    $data['value'] = empty($data['value']) ? trans('private person') : trans('legal entity');
                }
                break;
            case 'ipaddr':
                if (!check_ip($data['value'])) {
                    $data['value'] = long_ip($data['value']);
                }
                break;
            case 'ipaddr_pub':
                $data['value'] = empty($data['value']) ? trans('none') : (check_ip($data['value']) ? $data['value'] : long_ip($data['value']));
                break;
            case 'linktype':
                $data['value'] = $LINKTYPES[$data['value']];
                break;
            case 'linkspeed':
                $data['value'] = !empty($data['value']) ? $LINKSPEEDS[$data['value']] : '';
                break;
            case 'port':
                $data['value'] = $data['value'] == 0 ? trans('none') : $data['value'];
                break;
            case 'status':
                if ($data['resource'] == self::RES_CUST) {
                    $data['value'] = $CSTATUSES[$data['value']]['singularlabel'];
                }
                break;
            case 'twofactorauthsecretkey':
                $data['value'] = '***';
                break;
            default:
                if (isset($data['name']) && strpos($data['name'], 'chkconsent') === 0) {
                    $data['value'] = !empty($data['value']) ? $data['value'] = date('Y.m.d', $data['value']) : $data['value'];
                }
        }
        if (isset($data['value'])) {
            if ($data['resource'] != self::RES_USER && strlen($data['value']) > 50) {
                $data['value'] = substr($data['value'], 0, 50) . '...';
            }
            $data['value'] = htmlspecialchars($data['value']);
        }
        //$data['name'] = trans($data['name']);
    }

    private function _decodeTransaction(&$transaction, $messages, $keys, $data)
    {
        static $message_limit = null;

        if (!isset($message_limit)) {
            $message_limit = intval(ConfigHelper::getConfig('logs.message_limit', 11));
        }

        // PHP code is much faster then LIMIT 11 sql clause
        $transaction['messages'] = array_reverse(empty($message_limit) ? $messages : array_slice($messages, 0, $message_limit, true), true);

        if (!empty($keys)) {
            foreach ($keys as $key) {
                $messageid = $key['id'];
                if (!isset($transaction['messages'][$messageid])) {
                    break;
                }
                if (!isset($transaction['messages'][$messageid]['keys'])) {
                    $transaction['messages'][$messageid]['keys'] = array();
                }
                $transaction['messages'][$messageid]['keys'][$key['name']] = array(
                    'value' => $key['value'],
                );
            }
        }

        if (!empty($data)) {
            foreach ($data as $d) {
                $messageid = $d['id'];
                if (!isset($transaction['messages'][$messageid])) {
                    break;
                }
                if (!isset($transaction['messages'][$messageid]['data'])) {
                    $transaction['messages'][$messageid]['data'] = array();
                }
                $transaction['messages'][$messageid]['data'][$d['name']] = array(
                    'value' => $d['value'],
                );
            }
        }

        if (!empty($transaction['messages'])) {
            foreach ($transaction['messages'] as $messageid => &$msg) {
                $msg['text'] = '<span class="bold">' . self::getResourceName($msg['resource']);
                $msg['text'] .= ': ' . self::getOperationName($msg['operation']) . '</span>';
                if (!empty($msg['keys'])) {
                    foreach ($msg['keys'] as $keyname => &$key) {
                        $msg['text'] .= ', ' . $keyname . ': ' . $key['value'];
                        $key_name = preg_replace('/^[a-z]+_/i', '', $keyname);
                        $key['type'] = self::$resourceKeyByName[$key_name] ?? 0;
                    }
                    unset($key);
                }
                if (!empty($msg['data'])) {
                    foreach ($msg['data'] as $dname => &$data) {
                        $data['resource'] = $msg['resource'];
                        $this->DecodeMessageData($data);
                        $msg['text'] .= ', ' . $dname . ': ' . $data['value'];
                    }
                    unset($data);
                }
            }
            unset($msg);
        }
    }

    public function DecodeTransaction(&$transaction)
    {
        $messages = $this->DB->GetAllByKey(
            'SELECT id, resource, operation FROM logmessages lm
            WHERE lm.transactionid = ?
            ORDER BY lm.id',
            'id',
            array($transaction['id'])
        );

        $keys = $this->DB->GetAll(
            'SELECT m.id, k.name, k.value FROM logmessagekeys k
            JOIN logmessages m ON m.id = k.logmessageid
            WHERE m.transactionid = ?
            ORDER BY m.id, k.name',
            array($transaction['id'])
        );

        $data = $this->DB->GetAll(
            'SELECT m.id, d.name, d.value FROM logmessagedata d
            JOIN logmessages m ON m.id = d.logmessageid
            WHERE m.transactionid = ?
            ORDER BY m.id, d.name',
            array($transaction['id'])
        );

        $this->_decodeTransaction($transaction, $messages, $keys, $data);
    }

    public function GetResourcePropertyNames($type)
    {
        $names = $this->DB->GetCol('SELECT DISTINCT name FROM logmessagedata lmd
			JOIN logmessages lm ON lm.id = lmd.logmessageid
			WHERE lm.resource = ? ORDER BY name', array($type));
        return $names;
    }

    public function GetResourcePropertyValues($type, $name)
    {
        $values = $this->DB->GetCol(
            'SELECT DISTINCT value FROM logmessagedata lmd
			JOIN logmessages lm ON lm.id = lmd.logmessageid
			WHERE lm.resource = ? AND lmd.name = ? AND lmd.value <> ? ORDER BY value LIMIT 20',
            array($type, $name, '')
        );
        return $values;
    }

    public function GetResourceProperties($resource)
    {
        $type = $resource['type'];
        $id = $resource['id'];
        $date = empty($resource['date']) ? time() : intval($resource['date']);
        // get all possible resource properties
        $names = $this->GetResourcePropertyNames($type);
        if (empty($names)) {
            return null;
        }
        $result = array();
        // check if resource has already been deleted
        $value = $this->DB->GetOne(
            'SELECT lmk.value FROM logmessagekeys lmk
			JOIN logmessages lm ON lm.id = lmk.logmessageid
			JOIN logtransactions lt ON lt.id = lm.transactionid
			WHERE lmk.name = ? AND lmk.value = ?
				AND lt.time < ? AND lm.operation = ?
				AND lm.resource = ?
			ORDER BY lm.id DESC LIMIT 1',
            array(self::$resource_keys[$type], $id, $date,
                self::OPER_DELETE,
            $type)
        );
        if (!empty($value)) {
            return null;
        }
        // get all resource property values
        foreach ($names as $name) {
            $value = $this->DB->GetCol(
                'SELECT lmd.value FROM logmessagedata lmd
				JOIN logmessages lm ON lm.id = lmd.logmessageid
				JOIN logmessagekeys lmk ON lmk.logmessageid = lm.id
				JOIN logtransactions lt ON lt.id = lm.transactionid
				WHERE lmk.name = ? AND lmk.value = ? AND lmd.name = ?
					AND lt.time <= ? AND lm.operation IN (?, ?)
					AND lm.resource = ?
				ORDER BY lm.id DESC LIMIT 1',
                array(self::$resource_keys[$type], $id, $name, $date,
                    self::OPER_ADD,
                self::OPER_UPDATE,
                $type)
            );
            if (!empty($value)) {
                $value = $value[0];
                $data = array('name' => $name, 'value' => $value, 'resource' => $type);
                $this->DecodeMessageData($data);
                $result[$name] = $data['value'];
            }
        }
        //xdebug_var_dump($result);
        return $result;
    }

    public function AddResources($namesArray, $keysArray = null)
    {
        if (is_array($namesArray)) {
            foreach ($namesArray as $key => $value) {
                self::$resources[$key] = $value;
            }
        }
        if ($keysArray != null && is_array($keysArray)) {
            foreach ($keysArray as $key => $value) {
                self::$resource_keys[$key] = $value;
                if (isset($value)) {
                    self::$resourceKeyByName[$value] = $key;
                }
            }
        }
    }
}
