<?php

/*
 *  LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

function module_main()
{
    global $LMS,$SMARTY,$SESSION;

    if(!empty($_GET['consent']))
	    $LMS->DB->Execute('UPDATE customers SET consentdate = ?NOW? WHERE id = ?',
		    array($SESSION->id));

    $userinfo = $LMS->GetCustomer($SESSION->id);
    $usernodes = $LMS->GetCustomerNodes($SESSION->id);
    //$balancelist = $LMS->GetCustomerBalanceList($SESSION->id);
    $documents = $LMS->DB->GetAll('SELECT c.docid, d.number, d.type, c.title, c.fromdate, c.todate, 
	c.description, c.filename, c.md5sum, c.contenttype, n.template, d.closed, d.cdate
	FROM documentcontents c
	JOIN documents d ON (c.docid = d.id)
	LEFT JOIN numberplans n ON (d.numberplanid = n.id)
	WHERE d.customerid = ?
	ORDER BY cdate', array($SESSION->id));

    $fields_changed = $LMS->DB->GetRow('SELECT id FROM up_info_changes WHERE customerid = ?', 
    	array($SESSION->id));

    $SMARTY->assign('userinfo',$userinfo);
    $SMARTY->assign('usernodes',$usernodes);
    //$SMARTY->assign('balancelist',$balancelist);
    $SMARTY->assign('documents',$documents);
    $SMARTY->assign('fields_changed', $fields_changed);
    $SMARTY->display('module:info.html');
} 

function module_docview()
{
	include 'docview.php';
}

function module_updateuserform()
{
    global $LMS,$SMARTY,$SESSION;

    $userinfo = $LMS->GetCustomer($SESSION->id);
    $usernodes = $LMS->GetCustomerNodes($SESSION->id);
    $documents = $LMS->DB->GetAll('SELECT c.docid, d.number, d.type, c.title, c.fromdate, c.todate, 
	c.description, c.filename, c.md5sum, c.contenttype, n.template, d.closed, d.cdate
	FROM documentcontents c
	JOIN documents d ON (c.docid = d.id)
	LEFT JOIN numberplans n ON (d.numberplanid = n.id)
	WHERE d.customerid = ?
	ORDER BY cdate', array($SESSION->id));
    
    $userinfo['im'] = isset($userinfo['messengers'][IM_GG]) ? $userinfo['messengers'][IM_GG]['uid'] : '';
    $userinfo['yahoo'] = isset($userinfo['messengers'][IM_YAHOO]) ? $userinfo['messengers'][IM_YAHOO]['uid'] : '';
    $userinfo['skype'] = isset($userinfo['messengers'][IM_SKYPE]) ? $userinfo['messengers'][IM_SKYPE]['uid'] : '';
    
    $SMARTY->assign('userinfo',$userinfo);
    $SMARTY->assign('usernodes',$usernodes);
    $SMARTY->assign('documents',$documents);
    $SMARTY->display('module:updateuser.html');
}

function module_updateusersave() 
{
    global $LMS, $SMARTY, $SESSION, $rights, $error;

    $userinfo = $LMS->GetCustomer($SESSION->id);

    $userinfo['im'] = isset($userinfo['messengers'][IM_GG]) ? $userinfo['messengers'][IM_GG]['uid'] : '';
    $userinfo['yahoo'] = isset($userinfo['messengers'][IM_YAHOO]) ? $userinfo['messengers'][IM_YAHOO]['uid'] : '';
    $userinfo['skype'] = isset($userinfo['messengers'][IM_SKYPE]) ? $userinfo['messengers'][IM_SKYPE]['uid'] : '';

    $userdata = $_POST['userdata'];
    $right = $rights['info'];
    $id = $SESSION->id;
    $error = NULL;

    if(isset($right['edit_addr']) || 
	isset($right['edit_addr_ack']) ||
    	isset($right['edit_contact']) || 
	isset($right['edit_contact_ack'])
    )
	foreach(array_diff_assoc($userdata, $userinfo) as $field => $val) 
	{
	    if($field == 'phone' || $field == 'email')
	    {
		    $type = $field == 'phone' ? 'contacts' : 'emails';
		    foreach($val as $i => $v)
		    {
		        $v = trim(htmlspecialchars($v, ENT_NOQUOTES));
			if(isset($right['edit_contact']))
			{
			    if(isset($userinfo[$type][$i]) && $userinfo[$type][$i][$field] != $v)
			    {
				    if($v)
					    $LMS->DB->Execute('UPDATE customercontacts SET contact = ? WHERE id = ? AND customerid = ?', array($v, $i, $id));
				    else
					    $LMS->DB->Execute('DELETE FROM customercontacts WHERE id = ? AND customerid = ?', array($i, $id));
			    }
			    elseif(!isset($userinfo[$type][$i])  && $v)
			    	    $LMS->DB->Execute('INSERT INTO customercontacts (customerid, contact, type) VALUES (?, ?, ?)',
					array($id, $v, CONTACT_LANDLINE));
			    
			    $userinfo[$type][$i][$field] = $v;
			}
			elseif(isset($right['edit_contact_ack']) && ($v || isset($userinfo['contacts'][$i])))
				if(!isset($userinfo[$type][$i]) || $userinfo[$type][$i][$field] != $v)
					$LMS->DB->Execute('INSERT INTO up_info_changes(customerid, fieldname, fieldvalue) 
						VALUES(?, ?, ?)', array($id, $field.$i, $v));
		    }
		    continue;
	    }
	    else
		    $val = trim(htmlspecialchars($val, ENT_NOQUOTES));
            
	    switch($field) {
		case 'name':
		case 'lastname':
		case 'street':
		case 'building':
		case 'apartment':
		case 'zip':
		case 'city':
			if(isset($right['edit_addr'])) {
				$userinfo[$field] = $val;
				$needupdate = 1;
			}
			elseif(isset($right['edit_addr_ack']))
				$LMS->DB->Execute('INSERT INTO up_info_changes(customerid, fieldname, fieldvalue) 
					VALUES(?, ?, ?)', array($id, $field, $val));
			break;
		case 'email':
			if($val!='' && !check_email($val)) {
				$error['email'] = 1;
			}
			else 
			{
				if(isset($right['edit_contact'])) {
					$userinfo[$field] = $val;
					$needupdate = 1;
				}
				elseif(isset($right['edit_contact_ack']))
					$LMS->DB->Execute('INSERT INTO up_info_changes(customerid, fieldname, fieldvalue) 
						VALUES(?, ?, ?)', array($id, $field, $val));
			}
			break;
		case 'ten':
			if($val!='' && !check_ten($val)) {
				$error['ten']=1;
			}
			else 
			{
				if(isset($right['edit_addr'])) {
					$userinfo[$field] = $val;
					$needupdate = 1;
				}
				elseif(isset($right['edit_addr_ack']))
					$LMS->DB->Execute('INSERT INTO up_info_changes(customerid, fieldname, fieldvalue) 
						VALUES(?, ?, ?)', array($id, $field, $val));
			}
			break;
		case 'ssn':
			if($val!='' && !check_ssn($val)) {
				$error['ssn']=1;
			}
			else 
			{
				if(isset($right['edit_addr'])) {
					$userinfo[$field] = $val;
					$needupdate = 1;
				}
				elseif(isset($right['edit_addr_ack']))
					$LMS->DB->Execute('INSERT INTO up_info_changes(customerid, fieldname, fieldvalue) 
						VALUES(?, ?, ?)', array($id, $field, $val));
			}
			break;
		case 'im':
			if(isset($right['edit_contact']))
			{
				$LMS->DB->Execute('DELETE FROM imessengers WHERE customerid = ? AND type = ?', array($id, IM_GG));
				if($val)
					$LMS->DB->Execute('INSERT INTO imessengers (customerid, uid, type) VALUES (?,?,?)', array($id,$val,IM_GG));
			}
			elseif(isset($right['edit_contact_ack']))
				$LMS->DB->Execute('INSERT INTO up_info_changes(customerid, fieldname, fieldvalue) 
					VALUES(?, ?, ?)', array($id, $field, $val));
			break;
		case 'yahoo':
			if(isset($right['edit_contact']))
			{
				$LMS->DB->Execute('DELETE FROM imessengers WHERE customerid = ? AND type = ?', array($id, IM_YAHOO));
				if($val)
					$LMS->DB->Execute('INSERT INTO imessengers (customerid, uid, type) VALUES (?,?,?)', array($id,$val,IM_YAHOO));
			}
			elseif(isset($right['edit_contact_ack']))
				$LMS->DB->Execute('INSERT INTO up_info_changes(customerid, fieldname, fieldvalue) 
					VALUES(?, ?, ?)', array($id, $field, $val));
			break;
		case 'skype':
			if(isset($right['edit_contact']))
			{
				$LMS->DB->Execute('DELETE FROM imessengers WHERE customerid = ? AND type = ?', array($id, IM_SKYPE));
				if($val)
					$LMS->DB->Execute('INSERT INTO imessengers (customerid, uid, type) VALUES (?,?,?)', array($id,$val,IM_SKYPE));
			}
			elseif(isset($right['edit_contact_ack']))
				$LMS->DB->Execute('INSERT INTO up_info_changes(customerid, fieldname, fieldvalue) 
					VALUES(?, ?, ?)', array($id, $field, $val));
			break;
		default:
			break;
	    }
	}

    if(isset($error))
    {
    	$usernodes = $LMS->GetCustomerNodes($SESSION->id);
        $usernodes['ownerid'] = $SESSION->id;

        $SMARTY->assign('userinfo',$userinfo);
        $SMARTY->assign('usernodes',$usernodes);
	$SMARTY->assign('error',$error);
        $SMARTY->display('module:updateuser.html');
    }
    else
    {
	if(isset($needupdate))
    		$LMS->CustomerUpdate($userinfo);
        header('Location: ?m=info');
    }
}

if(defined('USERPANEL_SETUPMODE'))
{
	function module_changes()
	{
		global $layout, $SMARTY, $DB, $LMS;

		$layout['pagetitle'] = trans('Changes affirmation');

		$userchanges = $DB->GetAll('SELECT up_info_changes.id AS changeid, customerid, fieldname, fieldvalue AS newvalue, '.
					$DB->Concat('UPPER(lastname)',"' '",'c.name').' AS customername, c.* 
					FROM up_info_changes
					JOIN customerview c ON (c.id = up_info_changes.customerid)');

		if(isset($userchanges))
			foreach($userchanges as $key => $change)
			{
				if (preg_match('/phone([0-9]+)/', $change['fieldname'], $matches))
					$old = $DB->GetOne('SELECT contact AS phone FROM customercontacts WHERE id = ? AND type < ?',
						array($matches[1], CONTACT_MOBILE));
				elseif (preg_match('/email([0-9]+)/', $change['fieldname'], $matches))
					$old = $DB->GetOne('SELECT contact AS email FROM customercontacts WHERE id = ? AND type & ? > 0',
						array($matches[1], (CONTACT_EMAIL|CONTACT_INVOICES|CONTACT_NOTIFICATIONS)));
				else
					switch($change['fieldname'])
					{
					case 'im':
						$old = $DB->GetOne('SELECT uid FROM imessengers WHERE customerid = ? AND type = ?', array($change['customerid'], IM_GG));
					break;
					case 'yahoo':
						$old = $DB->GetOne('SELECT uid FROM imessengers WHERE customerid = ? AND type = ?', array($change['customerid'], IM_YAHOO));
					break;
					case 'skype':
						$old = $DB->GetOne('SELECT uid FROM imessengers WHERE customerid = ? AND type = ?', array($change['customerid'], IM_SKYPE));
					break;
					}
				
				if(isset($old))
				{
					$userchanges[$key]['oldvalue'] = $old;
					unset($old);
				}
				elseif(isset($userchanges[$key][$change['fieldname']]))
					$userchanges[$key]['oldvalue'] = $userchanges[$key][$change['fieldname']];
			}

		$SMARTY->assign('userchanges', $userchanges);
		$SMARTY->display('module:info:setup_changes.html');
	}

	function module_submit_changes_save() {
		global $LMS, $SYSLOG_RESOURCE_KEYS;

		$DB = LMSDB::getInstance();

		if (isset($_POST['userchanges'])) {
			$args = array();
			foreach ($_POST['userchanges'] as $changeid) {
				$changes = $DB->GetRow('SELECT customerid, fieldname, fieldvalue FROM up_info_changes
					WHERE id = ?', array($changeid));
				if (!isset($args[$changes['customerid']]))
					$args[$changes['customerid']] = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $changes['customerid'],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => $LMS->AUTH->id,
					);

				if (preg_match('/(phone|email)([0-9]+)/', $changes['fieldname'], $matches)) {
					if ($matches[2]) {
						$fields = array(
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $changes['customerid'],
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTCONTACT] => $matches[2],
						);
						if($changes['fieldvalue']) {
							$DB->Execute('UPDATE customercontacts SET contact = ? WHERE id = ?', array($changes['fieldvalue'], $matches[2]));
							if ($LMS->SYSLOG) {
								$fields['contact'] = $changes['fieldvalue'];
								$LMS->SYSLOG->AddMessage(SYSLOG_RES_CUSTCONTACT, SYSLOG_OPER_UPDATE, $fields,
									array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST],
										$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTCONTACT]));
							}
						} else {
							$DB->Execute('DELETE FROM customercontacts WHERE id = ?', array($matches[2]));
							if ($LMS->SYSLOG) {
								$LMS->SYSLOG->AddMessage(SYSLOG_RES_CUSTCONTACT, SYSLOG_OPER_DELETE, $fields,
									array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST],
										$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTCONTACT]));
							}
						}
					} else { // new phone or email
						$DB->Execute('INSERT INTO customercontacts (contact, customerid, type) VALUES(?, ?, ?)',
							array($changes['fieldvalue'], $changes['customerid'], $matches[1] == 'phone' ? CONTACT_LANDLINE : CONTACT_EMAIL));
						if ($LMS->SYSLOG) {
							$fields = array(
								$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $changes['customerid'],
								$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTCONTACT] => $DB->GetLastInsertID('customercontacts'),
								'contact' => $changes['fieldvalue'],
								'type' => $matches[1] == 'phone' ? CONTACT_LANDLINE : CONTACT_EMAIL,
							);
							$LMS->SYSLOG->AddMessage(SYSLOG_RES_CUSTCONTACT, SYSLOG_OPER_ADD, $fields,
								array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST],
									$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTCONTACT]));
						}
					}
				} else
				switch ($changes['fieldname']) {
					case 'im':
					case 'yahoo':
					case 'skype':
						$contact_types = array(
							'im' => IM_GG,
							'yahoo' => IM_YAHOO,
							'skype' => IM_SKYPE,
						);
						$contact_type = $contact_types[$changes['fieldname']];
						$fields = array(
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $changes['customerid'],
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => $LMS->AUTH->id,
							'type' => $contact_type,
						);

						$DB->Execute('DELETE FROM imessengers WHERE customerid = ? AND type = ?', array($changes['customerid'], $contact_type));
						if ($LMS->SYSLOG)
							$LMS->SYSLOG->AddMessage(SYSLOG_RES_IMCONTACT, SYSLOG_OPER_DELETE, $fields,
								array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST],
									$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER]));
						if ($changes['fieldvalue']) {
							$DB->Execute('INSERT INTO imessengers (customerid, uid, type) VALUES (?, ?, ?)',
								array($changes['customerid'], $changes['fieldvalue'], $contact_type));
							if ($LMS->SYSLOG) {
								$fields['uid'] = $changes['fieldvalue'];
								$LMS->SYSLOG->AddMessage(SYSLOG_RES_IMCONTACT, SYSLOG_OPER_ADD, $fields,
									array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST],
										$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER]));
							}
						}
						break;
					case 'name':
					case 'lastname':
					case 'street':
					case 'building':
					case 'apartment':
					case 'zip':
					case 'city':
					case 'ssn':
					case 'ten':
						$DB->Execute('UPDATE customers SET '.$changes['fieldname'].' = ? WHERE id = ?',
							array($changes['fieldvalue'], $changes['customerid']));
						$args[$changes['customerid']][$changes['fieldname']] = $changes['fieldvalue'];
						break;
				}

				if ($LMS->SYSLOG && !empty($args))
					foreach ($args as $customerid => $fields)
						if (count($fields) > 2)
							$LMS->SYSLOG->AddMessage(SYSLOG_RES_CUST, SYSLOG_OPER_UPDATE, $fields,
								array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST],
									$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER]));
				$DB->Execute('DELETE FROM up_info_changes WHERE id = ?', array($changeid));
			}
		}

		module_changes();
	}

	function module_submit_changes_delete()
	{
		global $DB;

		if(isset($_POST['userchanges']))
			foreach($_POST['userchanges'] as $changeid)
				$DB->Execute('DELETE FROM up_info_changes WHERE id = ?', array($changeid));

		module_changes();
	}
	
	function module_setup()
	{
		global $SMARTY, $LMS;
		
		$SMARTY->assign('hide_nodesbox', ConfigHelper::getConfig('userpanel.hide_nodesbox'));
		$SMARTY->assign('consent_text', ConfigHelper::getConfig('userpanel.data_consent_text'));
    		$SMARTY->display('module:info:setup.html');
        }
	
	function module_submit_setup()
	{
		global $DB;

		$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?',
			array(isset($_POST['hide_nodesbox']) ? 1 : 0, 'userpanel', 'hide_nodesbox'));
		$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?',
			array($_POST['consent_text'], 'userpanel', 'data_consent_text'));

		header('Location: ?m=userpanel&module=info');
	}
}

?>
