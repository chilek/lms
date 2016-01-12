<?php

define('SYSLOG_RES_USER', 1);
define('SYSLOG_RES_ASSIGN', 2);
define('SYSLOG_RES_LIAB', 3);
define('SYSLOG_RES_NODEASSIGN', 4);
define('SYSLOG_RES_NODE', 5);
define('SYSLOG_RES_MAC', 6);
define('SYSLOG_RES_CUST', 7);
define('SYSLOG_RES_CUSTCONTACT', 8);
define('SYSLOG_RES_IMCONTACT', 9);
define('SYSLOG_RES_CUSTGROUP', 10);
define('SYSLOG_RES_CUSTASSIGN', 11);
define('SYSLOG_RES_TARIFF', 12);
define('SYSLOG_RES_NODEGROUP', 13);
define('SYSLOG_RES_NODEGROUPASSIGN', 14);
define('SYSLOG_RES_TAX', 15);
define('SYSLOG_RES_NUMPLAN', 16);
define('SYSLOG_RES_NUMPLANASSIGN', 17);
define('SYSLOG_RES_DIV', 18);
define('SYSLOG_RES_COUNTRY', 19);
define('SYSLOG_RES_STATE', 20);
define('SYSLOG_RES_ZIP', 21);
define('SYSLOG_RES_HOST', 22);
define('SYSLOG_RES_DAEMONINST', 23);
define('SYSLOG_RES_DAEMONCONF', 24);
define('SYSLOG_RES_CASHSOURCE', 25);
define('SYSLOG_RES_UICONF', 26);
define('SYSLOG_RES_PROMO', 27);
define('SYSLOG_RES_PROMOSCHEMA', 28);
define('SYSLOG_RES_PROMOASSIGN', 29);
define('SYSLOG_RES_EXCLGROUP', 30);
define('SYSLOG_RES_DBBACKUP', 31);
define('SYSLOG_RES_PAYMENT', 32);
define('SYSLOG_RES_CASHIMPORT', 33);
define('SYSLOG_RES_SOURCEFILE', 34);
define('SYSLOG_RES_CASH', 35);
define('SYSLOG_RES_DOC', 36);
define('SYSLOG_RES_INVOICECONT', 37);
define('SYSLOG_RES_RECEIPTCONT', 38);
define('SYSLOG_RES_DNOTECONT', 39);
define('SYSLOG_RES_CASHREG', 40);
define('SYSLOG_RES_CASHRIGHT', 41);
define('SYSLOG_RES_CASHREGHIST', 42);
define('SYSLOG_RES_NETWORK', 43);
define('SYSLOG_RES_NETDEV', 44);
define('SYSLOG_RES_NETLINK', 45);
define('SYSLOG_RES_MGMTURL', 46);
define('SYSLOG_RES_TMPL', 47);
define('SYSLOG_RES_RADIOSECTOR', 48);

$SYSLOG_RESOURCES = array(
	SYSLOG_RES_USER => trans('user<!syslog>'),
	SYSLOG_RES_ASSIGN => trans('assignment<!syslog>'),
	SYSLOG_RES_LIAB => trans('liability<!syslog>'),
	SYSLOG_RES_NODEASSIGN => trans('node assignment<!syslog>'),
	SYSLOG_RES_NODE => trans('node<!syslog>'),
	SYSLOG_RES_MAC => trans('mac<!syslog>'),
	SYSLOG_RES_CUST => trans('customer<!syslog>'),
	SYSLOG_RES_CUSTCONTACT => trans('customer contact<!syslog>'),
	SYSLOG_RES_IMCONTACT => trans('IM contact<!syslog>'),
	SYSLOG_RES_CUSTGROUP => trans('customer group<!syslog>'),
	SYSLOG_RES_CUSTASSIGN => trans('customer assignment<!syslog>'),
	SYSLOG_RES_TARIFF => trans('tariff<!syslog>'),
	SYSLOG_RES_NODEGROUP => trans('node group<!syslog>'),
	SYSLOG_RES_NODEGROUPASSIGN => trans('node group assignment<!syslog>'),
	SYSLOG_RES_TAX => trans('tax rate<!syslog>'),
	SYSLOG_RES_NUMPLAN => trans('number plan<!syslog>'),
	SYSLOG_RES_NUMPLANASSIGN => trans('number plan assignment<!syslog>'),
	SYSLOG_RES_DIV => trans('division<!syslog>'),
	SYSLOG_RES_COUNTRY => trans('country<!syslog>'),
	SYSLOG_RES_STATE => trans('state<!syslog>'),
	SYSLOG_RES_ZIP => trans('zip code<!syslog>'),
	SYSLOG_RES_HOST => trans('host<!syslog>'),
	SYSLOG_RES_DAEMONINST => trans('daemon instance<!syslog>'),
	SYSLOG_RES_DAEMONCONF => trans('daemon instance setting<!syslog>'),
	SYSLOG_RES_CASHSOURCE => trans('cash import source<!syslog>'),
	SYSLOG_RES_UICONF => trans('configuration setting<!syslog>'),
	SYSLOG_RES_PROMO => trans('promotion<!syslog>'),
	SYSLOG_RES_PROMOSCHEMA => trans('promotion schema<!syslog>'),
	SYSLOG_RES_PROMOASSIGN => trans('promotion schema assignment<!syslog>'),
	SYSLOG_RES_EXCLGROUP => trans('customer group exclusion<!syslog>'),
	SYSLOG_RES_DBBACKUP => trans('database backup<!syslog>'),
	SYSLOG_RES_PAYMENT => trans('payment<!syslog>'),
	SYSLOG_RES_CASHIMPORT => trans('imported financial operation<!syslog>'),
	SYSLOG_RES_SOURCEFILE => trans('imported file with financial operations<!syslog>'),
	SYSLOG_RES_CASH => trans('financial operation<!syslog>'),
	SYSLOG_RES_DOC => trans('document<!syslog>'),
	SYSLOG_RES_INVOICECONT => trans('invoice contents<!syslog>'),
	SYSLOG_RES_RECEIPTCONT => trans('receipt contents<!syslog>'),
	SYSLOG_RES_DNOTECONT => trans('debit note contents<!syslog>'),
	SYSLOG_RES_CASHREG => trans('cash registry<!syslog>'),
	SYSLOG_RES_CASHRIGHT => trans('cash registry rights<!syslog>'),
	SYSLOG_RES_CASHREGHIST => trans('cash registry history<!syslog>'),
	SYSLOG_RES_NETWORK => trans('network<!syslog>'),
	SYSLOG_RES_NETDEV => trans('network device<!syslog>'),
	SYSLOG_RES_NETLINK => trans('network link<!syslog>'),
	SYSLOG_RES_MGMTURL => trans('management url<!syslog>'),
	SYSLOG_RES_TMPL => trans('template<!syslog>'),
	SYSLOG_RES_RADIOSECTOR => trans('radio sector<!syslog>'),
);

$SYSLOG_RESOURCE_KEYS = array(
	SYSLOG_RES_USER => 'userid',
	SYSLOG_RES_ASSIGN => 'assignmentid',
	SYSLOG_RES_LIAB => 'liabilityid',
	SYSLOG_RES_NODEASSIGN => 'nodeassignmentid',
	SYSLOG_RES_NODE => 'nodeid',
	SYSLOG_RES_MAC => 'macid',
	SYSLOG_RES_CUST => 'customerid',
	SYSLOG_RES_CUSTCONTACT => 'customercontactid',
	SYSLOG_RES_IMCONTACT => 'imessengerid',
	SYSLOG_RES_CUSTGROUP => 'customergroupid',
	SYSLOG_RES_CUSTASSIGN => 'customerassignmentid',
	SYSLOG_RES_TARIFF => 'tariffid',
	SYSLOG_RES_NODEGROUP => 'nodegroupid',
	SYSLOG_RES_NODEGROUPASSIGN => 'nodegroupassignmentid',
	SYSLOG_RES_TAX => 'taxrateid',
	SYSLOG_RES_NUMPLAN => 'numberplanid',
	SYSLOG_RES_NUMPLANASSIGN => 'numberplanassignmentid',
	SYSLOG_RES_DIV => 'divisionid',
	SYSLOG_RES_COUNTRY => 'countryid',
	SYSLOG_RES_STATE => 'stateid',
	SYSLOG_RES_ZIP => 'zipcodeid',
	SYSLOG_RES_HOST => 'hostid',
	SYSLOG_RES_DAEMONINST => 'daemoninstanceid',
	SYSLOG_RES_DAEMONCONF => 'daemonconfigid',
	SYSLOG_RES_CASHSOURCE => 'cashsourceid',
	SYSLOG_RES_UICONF => 'uiconfigid',
	SYSLOG_RES_PROMO => 'promotionid',
	SYSLOG_RES_PROMOSCHEMA => 'promotionschemaid',
	SYSLOG_RES_PROMOASSIGN => 'promotionassignmentid',
	SYSLOG_RES_EXCLGROUP => 'excludedgroupid',
	SYSLOG_RES_DBBACKUP => null,
	SYSLOG_RES_PAYMENT => 'paymentid',
	SYSLOG_RES_CASHIMPORT => 'importid',
	SYSLOG_RES_SOURCEFILE => 'sourcefileid',
	SYSLOG_RES_CASH => 'cashid',
	SYSLOG_RES_DOC => 'documentid',
	SYSLOG_RES_INVOICECONT => null,
	SYSLOG_RES_RECEIPTCONT => null,
	SYSLOG_RES_DNOTECONT => 'debitnotecontentid',
	SYSLOG_RES_CASHREG => 'cashregistryid',
	SYSLOG_RES_CASHRIGHT => 'cashrightid',
	SYSLOG_RES_CASHREGHIST => 'cashreghistoryid',
	SYSLOG_RES_NETWORK => 'networkid',
	SYSLOG_RES_NETDEV => 'networkdeviceid',
	SYSLOG_RES_NETLINK => 'networklinkid',
	SYSLOG_RES_MGMTURL => 'managementurlid',
	SYSLOG_RES_TMPL => 'templateid',
	SYSLOG_RES_RADIOSECTOR => 'radiosectorid',
);

define('SYSLOG_OPER_ADD', 1);
define('SYSLOG_OPER_DELETE', 2);
define('SYSLOG_OPER_UPDATE', 3);
define('SYSLOG_OPER_DBBACKUPRECOVER', 240);
define('SYSLOG_OPER_USERPASSWDCHANGE', 251);
define('SYSLOG_OPER_USERNOACCESS', 252);
define('SYSLOG_OPER_USERLOGFAIL', 253);
define('SYSLOG_OPER_USERLOGIN', 254);
define('SYSLOG_OPER_USERLOGOUT', 255);

$SYSLOG_OPERATIONS = array(
	SYSLOG_OPER_ADD => trans('addition<!syslog>'),
	SYSLOG_OPER_DELETE => trans('deletion<!syslog>'),
	SYSLOG_OPER_UPDATE => trans('update<!syslog>'),
	SYSLOG_OPER_DBBACKUPRECOVER => trans('recover<!syslog>'),
	SYSLOG_OPER_USERPASSWDCHANGE => trans('password change<!syslog>'),
	SYSLOG_OPER_USERNOACCESS => trans('access denied<!syslog>'),
	SYSLOG_OPER_USERLOGFAIL => trans('log in failed<!syslog>'),
	SYSLOG_OPER_USERLOGIN => trans('log in<!syslog>'),
	SYSLOG_OPER_USERLOGOUT => trans('log out<!syslog>'),
);

if (isset($SMARTY)) {
	asort($SYSLOG_RESOURCES);
	$SMARTY->assign('_SYSLOG_RESOURCES', $SYSLOG_RESOURCES);
	$SMARTY->assign('_SYSLOG_RESOURCE_KEYS', $SYSLOG_RESOURCE_KEYS);
	$SMARTY->assign('_SYSLOG_OPERATIONS', $SYSLOG_OPERATIONS);
}

class SYSLOG {
	private $DB;
	private $AUTH = null;
	private $userid = 0;
	private $transid = 0;
	private $module = '';

	function __construct(&$DB) {
		$this->DB = $DB;
	}

	function SetAuth(&$AUTH) {
		$this->AUTH = $AUTH;
	}

	function NewTransaction($module, $userid = null) {
		if (is_null($this->AUTH)) {
			if (!is_null($userid))
				$this->userid = intval($userid);
		} else
			$this->userid = $this->AUTH->id;
		$this->module = $module;
		$this->transid = 0;
		//$this->DB->Execute('INSERT INTO logtransactions (time, userid, module)
		//	VALUES(?NOW?, ?, ?)', array($this->userid, $this->module));
		//$this->transid = $this->DB->GetLastInsertID('logtransactions');
	}

	function AddMessage($resource, $operation, $data = null, $keys = null) {
		if (empty($this->transid) && empty($this->module))
			return;
		if (empty($this->transid)) {
			$this->DB->Execute('INSERT INTO logtransactions (time, userid, module)
				VALUES(?NOW?, ?, ?)', array($this->userid, $this->module));
			$this->transid = $this->DB->GetLastInsertID('logtransactions');
		}

		$this->DB->Execute('INSERT INTO logmessages (transactionid, resource, operation)
			VALUES(?, ?, ?)', array($this->transid, $resource, $operation));
		$id = $this->DB->GetLastInsertID('logmessages');
		if (!empty($data) && is_array($data))
			foreach ($data as $name => $val)
				if (!empty($keys) && is_array($keys) && array_search($name, $keys) !== FALSE
					&& (is_long($val) || is_int($val) || preg_match('/^[0-9]+$/', $val)))
					$this->DB->Execute('INSERT INTO logmessagekeys (logmessageid, name, value)
						VALUES(?, ?, ?)',
						array($id, $name, $val));
				else
					$this->DB->Execute('INSERT INTO logmessagedata (logmessageid, name, value)
						VALUES(?, ?, ?)',
						array($id, $name, $val));
	}

	function GetTransactions($params) {
		$key = (isset($params['key']) && !empty($params['key']) ? $params['key'] : '');
		$value = (isset($params['value']) && preg_match('/^[0-9]+$/', $params['value']) ? $params['value'] : '');
		$propname = (isset($params['propname']) && !empty($params['propname']) ? $params['propname'] : '');
		$propvalue = (isset($params['propvalue']) && !empty($params['propvalue']) ? $params['propvalue'] : '');
		$userid = (isset($params['userid']) && !empty($params['userid']) ? intval($params['userid']) : 0);
		$offset = (isset($params['offset']) && !empty($params['offset']) ? intval($params['offset']) : 0);
		$limit = (isset($params['limit']) && !empty($params['limit']) ? intval($params['limit']) : 20);
		$order = (isset($params['order']) && preg_match('/ASC/i', $params['order']) ? 'ASC' : 'DESC');
		$datefrom = (isset($params['datefrom']) && !empty($params['datefrom']) ? intval($params['datefrom']) : 0);
		$dateto = (isset($params['dateto']) && !empty($params['dateto']) ? intval($params['dateto']) : 0);
		$resource = (isset($params['resource']) && !empty($params['resource']) ? $params['resource'] : 0);

		switch ($propname) {
			case 'ipaddr':
			case 'ipaddr_pub':
				if (check_ip($propvalue))
					$propvalue = ip_long($propvalue);
				break;
		}
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
		$trans = $this->DB->GetAll('SELECT DISTINCT lt.id, lt.time, lt.userid, u.login, lt.module FROM logtransactions lt
			JOIN logmessages lm ON lm.transactionid = lt.id 
			LEFT JOIN users u ON u.id = lt.userid ' . implode(' ', $joins)
			. (!empty($where) ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY lt.id ' . $order
			. ' LIMIT ' . $limit . (!empty($offset) ? ' OFFSET ' . $offset : ''),
				$args);
		return $trans;
	}

	function DecodeMessageData(&$data) {
		global $SYSLOG_RESOURCES, $SYSLOG_OPERATIONS, $PERIODS, $PAYTYPES, $LINKTYPES, $LINKSPEEDS;

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
				if ($data['resource'] == SYSLOG_RES_CUST)
					$data['value'] = empty($data['value']) ? trans('private person') : trans('legal entity');
				else
					$data['value'] = $data['value'];
				break;
			case 'ipaddr':
				$data['value'] = long2ip($data['value']);
				break;
			case 'ipaddr_pub':
				$data['value'] = empty($data['value']) ? trans('none') : long2ip($data['value']);
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
		if ($data['resource'] != SYSLOG_RES_USER && strlen($data['value']) > 50)
			$data['value'] = substr($data['value'], 0, 50) . '...';
		$data['value'] = htmlspecialchars($data['value']);
		//$data['name'] = trans($data['name']);
	}

	function DecodeTransaction(&$tran) {
		global $SYSLOG_RESOURCES, $SYSLOG_OPERATIONS, $SYSLOG_RESOURCE_KEYS;
		$tran['messages'] = $this->DB->GetAll('SELECT id, resource, operation FROM logmessages lm
			WHERE lm.transactionid = ? ORDER BY lm.id LIMIT 11',
			array($tran['id']));
		if (!empty($tran['messages']))
			foreach ($tran['messages'] as $idx => $tr) {
				$msg = &$tran['messages'][$idx];
				$msg['text'] = '<span class="bold">' . $SYSLOG_RESOURCES[$tr['resource']];
				$msg['text'] .= ': ' . $SYSLOG_OPERATIONS[$tr['operation']] . '</span>';
				$keys =	$this->DB->GetAll('SELECT name, value FROM logmessagekeys 
					WHERE logmessageid = ? ORDER BY name', array($tr['id']));
				if (!empty($keys)) {
					$msg['keys'] = array();
					foreach ($keys as $key => $v) {
						$msg['text'] .= ', ' . $v['name'] . ': ' . $v['value'];
						$key_name = preg_replace('/^[a-z]+_/i', '', $v['name']);
						$msg['keys'][$v['name']] = array('type' => array_search($key_name, $SYSLOG_RESOURCE_KEYS), 'value' => $v['value']);
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

	function GetResourcePropertyNames($type) {
		$names = $this->DB->GetCol('SELECT DISTINCT name FROM logmessagedata lmd
			JOIN logmessages lm ON lm.id = lmd.logmessageid
			WHERE lm.resource = ? ORDER BY name', array($type));
		return $names;
	}

	function GetResourcePropertyValues($type, $name) {
		$values = $this->DB->GetCol('SELECT DISTINCT value FROM logmessagedata lmd
			JOIN logmessages lm ON lm.id = lmd.logmessageid
			WHERE lm.resource = ? AND lmd.name = ? AND lmd.value <> ? ORDER BY value LIMIT 20',
				array($type, $name, ''));
		return $values;
	}

	function GetResourceProperties($resource) {
		global $SYSLOG_RESOURCE_KEYS;
		$type = $resource['type'];
		$id = $resource['id'];
		$date = !isset($resource['date']) || empty($resource['date']) ? time() : intval($resource['date']);
		// get all possible resource properties
		$names = $this->GetResourcePropertyNames($type);
		if (empty($names))
			return null;
		$result = array();
		// check if resource has already been deleted
		$value = $this->DB->GetOne('SELECT lmk.value FROM logmessagekeys lmk
			JOIN logmessages lm ON lm.id = lmk.logmessageid
			JOIN logtransactions lt ON lt.id = lm.transactionid
			WHERE lmk.name = ? AND lmk.value = ?
				AND lt.time < ? AND lm.operation = ?
				AND lm.resource = ?
			ORDER BY lm.id DESC LIMIT 1',
			array($SYSLOG_RESOURCE_KEYS[$type], $id, $date,
				SYSLOG_OPER_DELETE, $type));
		if (!empty($value))
			return null;
		// get all resource property values
		foreach ($names as $name) {
			$value = $this->DB->GetCol('SELECT lmd.value FROM logmessagedata lmd
				JOIN logmessages lm ON lm.id = lmd.logmessageid
				JOIN logmessagekeys lmk ON lmk.logmessageid = lm.id
				JOIN logtransactions lt ON lt.id = lm.transactionid
				WHERE lmk.name = ? AND lmk.value = ? AND lmd.name = ?
					AND lt.time <= ? AND lm.operation IN (?, ?)
					AND lm.resource = ?
				ORDER BY lm.id DESC LIMIT 1',
				array($SYSLOG_RESOURCE_KEYS[$type], $id, $name, $date,
					SYSLOG_OPER_ADD, SYSLOG_OPER_UPDATE, $type));
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
}

?>
