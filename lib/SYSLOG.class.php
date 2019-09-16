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
    const RES_USER = 1;
    const RES_ASSIGN = 2;
    const RES_LIAB = 3;
    const RES_NODEASSIGN = 4;
    const RES_NODE = 5;
    const RES_MAC = 6;
    const RES_CUST = 7;
    const RES_CUSTCONTACT = 8;
    const RES_IMCONTACT = 9;
    const RES_CUSTGROUP = 10;
    const RES_CUSTASSIGN = 11;
    const RES_TARIFF = 12;
    const RES_NODEGROUP = 13;
    const RES_NODEGROUPASSIGN = 14;
    const RES_TAX = 15;
    const RES_NUMPLAN = 16;
    const RES_NUMPLANASSIGN = 17;
    const RES_DIV = 18;
    const RES_COUNTRY = 19;
    const RES_STATE = 20;
    const RES_ZIP = 21;
    const RES_HOST = 22;
    const RES_DAEMONINST = 23;
    const RES_DAEMONCONF = 24;
    const RES_CASHSOURCE = 25;
    const RES_UICONF = 26;
    const RES_PROMO = 27;
    const RES_PROMOSCHEMA = 28;
    const RES_PROMOASSIGN = 29;
    const RES_EXCLGROUP = 30;
    const RES_DBBACKUP = 31;
    const RES_PAYMENT = 32;
    const RES_CASHIMPORT = 33;
    const RES_SOURCEFILE = 34;
    const RES_CASH = 35;
    const RES_DOC = 36;
    const RES_INVOICECONT = 37;
    const RES_RECEIPTCONT = 38;
    const RES_DNOTECONT = 39;
    const RES_CASHREG = 40;
    const RES_CASHRIGHT = 41;
    const RES_CASHREGHIST = 42;
    const RES_NETWORK = 43;
    const RES_NETDEV = 44;
    const RES_NETLINK = 45;
    const RES_MGMTURL = 46;
    const RES_TMPL = 47;
    const RES_RADIOSECTOR = 48;
    const RES_USERGROUP = 49;
    const RES_USERASSIGN = 50;
    const RES_TARIFFTAG = 51;
    const RES_TARIFFASSIGN = 52;

    const OPER_ADD = 1;
    const OPER_DELETE = 2;
    const OPER_UPDATE = 3;
    const OPER_DBBACKUPRECOVER = 240;
    const OPER_USERPASSWDCHANGE = 251;
    const OPER_USERNOACCESS = 252;
    const OPER_USERLOGFAIL = 253;
    const OPER_USERLOGIN = 254;
    const OPER_USERLOGOUT = 255;
    const OPER_USERAUTCHANGE = 256;

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
        self::RES_MGMTURL => 'management url<!syslog>',
        self::RES_TMPL => 'template<!syslog>',
        self::RES_RADIOSECTOR => 'radio sector<!syslog>',
        self::RES_USERGROUP => 'user group<!syslog>',
        self::RES_USERASSIGN => 'user assignment<!syslog>',
        self::RES_TARIFFTAG => 'tariff tag<!syslog>',
        self::RES_TARIFFASSIGN => 'tariff assignment<!syslog>',
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
    );
    private static $operations = array(
        self::OPER_ADD => 'addition<!syslog>',
        self::OPER_DELETE => 'deletion<!syslog>',
        self::OPER_UPDATE => 'update<!syslog>',
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
        self::OPER_DBBACKUPRECOVER => 'color: aqua',
        self::OPER_USERPASSWDCHANGE => 'color: navy',
        self::OPER_USERNOACCESS => 'color: purple',
        self::OPER_USERLOGFAIL => 'color: crimson',
        self::OPER_USERLOGIN => 'color: gray',
        self::OPER_USERLOGOUT => 'color: darkgray',
    );

    private static $syslog = null;

    private $DB;
    private $userid = null;
    private $transid = 0;
    private $module = '';

    public static function getInstance($force = false)
    {
        if (self::$syslog == null && ($force || ConfigHelper::checkConfig('phpui.logging'))) {
            self::$syslog = new SYSLOG();
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
                && (is_int($val) || preg_match('/^[0-9]+$/', $val))) {
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
        $key = (isset($params['key']) && !empty($params['key']) ? $params['key'] : '');
        $value = (isset($params['value']) && preg_match('/^[0-9]+$/', $params['value']) ? $params['value'] : '');
        $propname = (isset($params['propname']) && !empty($params['propname']) ? $params['propname'] : '');
        $propvalue = (isset($params['propvalue']) ? $params['propvalue'] : '');
        $userid = (isset($params['userid']) && !empty($params['userid']) ? intval($params['userid']) : null);
        $offset = (isset($params['offset']) && !empty($params['offset']) ? intval($params['offset']) : 0);
        $limit = (isset($params['limit']) && !empty($params['limit']) ? intval($params['limit']) : 20);
        $order = (isset($params['order']) && preg_match('/ASC/i', $params['order']) ? 'ASC' : 'DESC');
        $datefrom = (isset($params['datefrom']) && !empty($params['datefrom']) ? intval($params['datefrom']) : 0);
        $dateto = (isset($params['dateto']) && !empty($params['dateto']) ? intval($params['dateto']) : 0);
        $resource = (isset($params['resource']) && !empty($params['resource']) ? $params['resource'] : 0);

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
            . (!empty($where) ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY lt.id ' . $order
            . ' LIMIT ' . $limit . (!empty($offset) ? ' OFFSET ' . $offset : ''),
            $args
        );
        return $trans;
    }

    public function DecodeMessageData(&$data)
    {
        global $PERIODS, $PAYTYPES, $LINKTYPES, $LINKSPEEDS;

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
                $data['value'] = empty($data['value']) ? trans('default') : $PAYTYPES[$data['value']];
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
                } else {
                    $data['value'] = $data['value'];
                }
                break;
            case 'ipaddr':
                if (!check_ip($data['value'])) {
                    $data['value'] = long_ip($data['value']);
                }
                break;
            case 'ipaddr_pub':
                $data['value'] = empty($data['value']) ? trans('none') : long_ip($data['value']);
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
            default:
                $data['value'] = $data['value'];
        }
        if ($data['resource'] != self::RES_USER && strlen($data['value']) > 50) {
            $data['value'] = substr($data['value'], 0, 50) . '...';
        }
        $data['value'] = htmlspecialchars($data['value']);
        //$data['name'] = trans($data['name']);
    }

    public function DecodeTransaction(&$tran)
    {
        $tran['messages'] = $this->DB->GetAll(
            'SELECT id, resource, operation FROM logmessages lm
			WHERE lm.transactionid = ? ORDER BY lm.id',
            array($tran['id'])
        );
        // PHP code is much faster then LIMIT 11 sql clause
        $tran['messages'] = array_slice($tran['messages'], 0, 11);

        if (!empty($tran['messages'])) {
            foreach ($tran['messages'] as $idx => $tr) {
                $msg = &$tran['messages'][$idx];
                $msg['text'] = '<span class="bold">' . self::getResourceName($tr['resource']);
                $msg['text'] .= ': ' . self::getOperationName($tr['operation']) . '</span>';
                $keys = $this->DB->GetAll('SELECT name, value FROM logmessagekeys 
					WHERE logmessageid = ? ORDER BY name', array($tr['id']));
                if (!empty($keys)) {
                    $msg['keys'] = array();
                    foreach ($keys as $key => $v) {
                        $msg['text'] .= ', ' . $v['name'] . ': ' . $v['value'];
                        $key_name = preg_replace('/^[a-z]+_/i', '', $v['name']);
                        $msg['keys'][$v['name']] = array('type' => array_search($key_name, self::$resource_keys), 'value' => $v['value']);
                    }
                }
                $data = $this->DB->GetAll('SELECT name, value FROM logmessagedata 
					WHERE logmessageid = ? ORDER BY name', array($tr['id']));
                if (!empty($data)) {
                    $msg['data'] = array();
                    foreach ($data as $key => $v) {
                        $v['resource'] = $msg['resource'];
                        $this->DecodeMessageData($v);
                        $msg['text'] .= ', ' . $v['name'] . ': ' . $v['value'];
                        $msg['data'][$v['name']] = $v['value'];
                    }
                }
            }
        }
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
        $date = !isset($resource['date']) || empty($resource['date']) ? time() : intval($resource['date']);
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
            }
        }
    }
}
