<?php

/*
 *  LMS version 1.11-git
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

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'customercontacttypes.php');

function module_main()
{
    global $LMS,$SMARTY,$SESSION;

    if(!empty($_GET['consent']))
	    $LMS->DB->Execute('UPDATE customers SET consentdate = ?NOW? WHERE id = ?',
		    array($SESSION->id));

    $userinfo = $LMS->GetCustomer($SESSION->id);
    $usernodes = $LMS->GetCustomerNodes($SESSION->id);
    //$balancelist = $LMS->GetCustomerBalanceList($SESSION->id);
	$documents = $LMS->DB->GetAll('SELECT d.id, d.number, d.type, c.title, c.fromdate, c.todate, 
		c.description, n.template, d.closed, d.cdate
		FROM documentcontents c
		JOIN documents d ON (c.docid = d.id)
		LEFT JOIN numberplans n ON (d.numberplanid = n.id)
		WHERE d.customerid = ?'
			. (ConfigHelper::checkConfig('userpanel.show_confirmed_documents_only') ? ' AND d.closed = 1': '') . '
		ORDER BY cdate', array($SESSION->id));

	if (!empty($documents))
		foreach ($documents as &$doc)
			$doc['attachments'] = $LMS->DB->GetAllBykey('SELECT * FROM documentattachments WHERE docid = ?
				ORDER BY main DESC, filename', 'id', array($doc['id']));

	$fields_changed = $LMS->DB->GetAllByKey('SELECT id, fieldname, fieldvalue FROM up_info_changes WHERE customerid = ?',
		'fieldname', array($SESSION->id));

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
    $documents = $LMS->DB->GetAll('SELECT d.id, d.number, d.type, c.title, c.fromdate, c.todate, 
		c.description, n.template, d.closed, d.cdate
		FROM documentcontents c
		JOIN documents d ON (c.docid = d.id)
		LEFT JOIN numberplans n ON (d.numberplanid = n.id)
		WHERE d.customerid = ?
		ORDER BY cdate', array($SESSION->id));

	if (!empty($documents))
		foreach ($documents as &$doc)
			$doc['attachments'] = $LMS->DB->GetAllBykey('SELECT * FROM documentattachments WHERE docid = ?
				ORDER BY main DESC, filename', 'id', array($doc['id']));

    $SMARTY->assign('userinfo',$userinfo);
    $SMARTY->assign('usernodes',$usernodes);
    $SMARTY->assign('documents',$documents);
    $SMARTY->display('module:updateuser.html');
}

function parse_notification_mail($string, $customerinfo) {
	$string = str_replace('%cid%', $customerinfo['id'], $string);
	$string = str_replace('%customername%', $customerinfo['customername'], $string);
	return $string;
}

function module_updateusersave()
{
    global $LMS, $SMARTY, $SESSION, $rights, $error;

    $userinfo = $LMS->GetCustomer($SESSION->id);

    $userdata = $_POST['userdata'];
    $right = $rights['info'];
    $id = $SESSION->id;
    $error = NULL;

	if (isset($right['edit_addr']) || isset($right['edit_addr_ack'])
		|| isset($right['edit_contact']) || isset($right['edit_contact_ack']))
	foreach (array_diff_assoc($userdata, $userinfo) as $field => $val) {
	    if($field == 'phone' || $field == 'email' || $field == 'im')
	    {
		    $type = $field == 'phone' ? 'contacts' : $field . 's';
		    $checked_property = $field == 'im' ? 'uid' : $field;
		    foreach($val as $i => $v)
		    {
		        $v = trim(htmlspecialchars($v, ENT_NOQUOTES));
			if(isset($right['edit_contact']))
			{
			    if(isset($userinfo[$type][$i]) && $userinfo[$type][$i][$checked_property] != $v)
			    {
				    if($v)
					    $LMS->DB->Execute('UPDATE customercontacts SET contact = ? WHERE id = ? AND customerid = ?', array($v, $i, $id));
				    else
					    $LMS->DB->Execute('DELETE FROM customercontacts WHERE id = ? AND customerid = ?', array($i, $id));
			    }
			    elseif(!isset($userinfo[$type][$i])  && $v)
			    	    $LMS->DB->Execute('INSERT INTO customercontacts (customerid, contact, type) VALUES (?, ?, ?)',
					array($id, $v, CONTACT_LANDLINE));
			    
			    $userinfo[$type][$i][$checked_property] = $v;
			} elseif (isset($right['edit_contact_ack']) && ($v || isset($userinfo[$type][$i])))
				if (!isset($userinfo[$type][$i]) || $userinfo[$type][$i][$checked_property] != $v) {
					$LMS->DB->Execute('DELETE FROM up_info_changes WHERE customerid = ? AND fieldname = ?',
						array($id, $field . $i));
					$LMS->DB->Execute('INSERT INTO up_info_changes(customerid, fieldname, fieldvalue)
						VALUES(?, ?, ?)', array($id, $field . $i, $v));
					$need_change_notification = true;
				}
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
			} elseif(isset($right['edit_addr_ack'])) {
				$LMS->DB->Execute('DELETE FROM up_info_changes WHERE customerid = ? AND fieldname = ?',
					array($id, $field));
				$LMS->DB->Execute('INSERT INTO up_info_changes(customerid, fieldname, fieldvalue)
					VALUES(?, ?, ?)', array($id, $field, $val));
				$need_change_notification = true;
			}
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
				} elseif(isset($right['edit_contact_ack'])) {
					$LMS->DB->Execute('DELETE FROM up_info_changes WHERE customerid = ? AND fieldname = ?',
						array($id, $field));
					$LMS->DB->Execute('INSERT INTO up_info_changes(customerid, fieldname, fieldvalue)
						VALUES(?, ?, ?)', array($id, $field, $val));
					$need_change_notification = true;
				}
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
				} elseif(isset($right['edit_addr_ack'])) {
					$LMS->DB->Execute('DELETE FROM up_info_changes WHERE customerid = ? AND fieldname = ?',
						array($id, $field));
					$LMS->DB->Execute('INSERT INTO up_info_changes(customerid, fieldname, fieldvalue)
						VALUES(?, ?, ?)', array($id, $field, $val));
					$need_change_notification = true;
				}
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
				} elseif(isset($right['edit_addr_ack'])) {
					$LMS->DB->Execute('DELETE FROM up_info_changes WHERE customerid = ? AND fieldname = ?',
						array($id, $field));
					$LMS->DB->Execute('INSERT INTO up_info_changes(customerid, fieldname, fieldvalue)
						VALUES(?, ?, ?)', array($id, $field, $val));
					$need_change_notification = true;
				}
			}
			break;
		default:
			break;
		}
	}

	if (isset($error)) {
		$usernodes = $LMS->GetCustomerNodes($SESSION->id);
		$usernodes['ownerid'] = $SESSION->id;

		$SMARTY->assign('userinfo',$userinfo);
		$SMARTY->assign('usernodes',$usernodes);
		$SMARTY->assign('error',$error);
		$SMARTY->display('module:updateuser.html');
	} else {
		if (isset($needupdate))
			$LMS->CustomerUpdate($userinfo);
		elseif (isset($need_change_notification)) {
			$mail_sender = ConfigHelper::getConfig('userpanel.change_notification_mail_sender', ConfigHelper::getConfig('mail.smtp_username'));
			$mail_recipient = ConfigHelper::getConfig('userpanel.change_notification_mail_recipient');
			$mail_subject = ConfigHelper::getConfig('userpanel.change_notification_mail_subject');
			$mail_body = ConfigHelper::getConfig('userpanel.change_notification_mail_body');

			if (!empty($mail_sender) && !empty($mail_recipient) && !empty($mail_subject) && !empty($mail_body)) {
				$mail_subject = parse_notification_mail($mail_subject, $userinfo);
				$mail_body = parse_notification_mail($mail_body, $userinfo);
				$LMS->SendMail($mail_recipient, array(
						'From' => $mail_sender,
						'To' => $mail_recipient,
						'Subject' => $mail_subject,
					), $mail_body);
			}
		}
		header('Location: ?m=info');
	}
}

function module_updatepinform() {
	global $LMS, $SMARTY, $SESSION;

	$userinfo = $LMS->GetCustomer($SESSION->id);

	$usernodes = $LMS->GetCustomerNodes($SESSION->id);
	$usernodes['ownerid'] = $SESSION->id;

	$SMARTY->assign('userinfo',$userinfo);
	$SMARTY->assign('usernodes',$usernodes);

	$SMARTY->display('module:updatepin.html');
}

function module_updatepin() {
	global $LMS, $SMARTY, $SESSION;

	if (!ConfigHelper::checkConfig('userpanel.pin_changes') || !isset($_POST['userdata']))
		header('Location: ?m=info');

	$error = null;

	$userdata = $_POST['userdata'];
	$userinfo = $LMS->GetCustomer($SESSION->id);

	if ($userinfo['pin'] != $userdata['oldpin'])
		$error['oldpin'] = trans('Incorrect current PIN!');

	if (!strlen($userdata['pin']) || !strlen($userdata['pin2']))
		$error['pin'] = $error['pin2'] = trans('PINs cannot be empty!');
	elseif ($userdata['pin'] != $userdata['pin2'])
		$error['pin'] = $error['pin2'] = trans('Entered PINs do not match!');
	elseif ($userinfo['pin'] == $userdata['pin'])
		$error['pin'] = $error['pin2'] = trans('New PIN should be different than old PIN!');
	else{
		$pin_min_size = intval(ConfigHelper::getConfig('phpui.pin_min_size', 4));
		$pin_max_size = intval(ConfigHelper::getConfig('phpui.pin_max_size', 6));
		$pin_allowed_characters = ConfigHelper::getConfig('phpui.pin_allowed_characters', '0123456789');
		if (!validate_random_string($userdata['pin'], $pin_min_size, $pin_max_size, $pin_allowed_characters)) {
			$pin_allowed_characters = ConfigHelper::getConfig('phpui.pin_allowed_characters', '0123456789');
			$pin_allowed_characters = str_split($pin_allowed_characters, 30);
			$error['pin'] = $error['pin2'] = trans('PIN should have at least $a, maximum $b characters and contain only \'$c\' characters!',
				ConfigHelper::getConfig('phpui.pin_min_size', 4),
				ConfigHelper::getConfig('phpui.pin_max_size', 6),
				implode('<br>', $pin_allowed_characters));
		}
	}

	if (isset($error)) {
		$usernodes = $LMS->GetCustomerNodes($SESSION->id);
		$usernodes['ownerid'] = $SESSION->id;

		$SMARTY->assign('userinfo',$userinfo);
		$SMARTY->assign('usernodes',$usernodes);
		$SMARTY->assign('error', $error);
		$SMARTY->assign('updatepin', 1);

		$SMARTY->display('module:updatepin.html');
	} else {
		$LMS->UpdateCustomerPIN($SESSION->id, $userdata['pin']);

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
					$old = $DB->GetOne('SELECT contact AS phone FROM customercontacts WHERE id = ? AND type & ? > 0',
						array($matches[1], CONTACT_MOBILE|CONTACT_LANDLINE));
				elseif (preg_match('/email([0-9]+)/', $change['fieldname'], $matches))
					$old = $DB->GetOne('SELECT contact AS email FROM customercontacts WHERE id = ? AND type & ? > 0',
						array($matches[1], (CONTACT_EMAIL|CONTACT_INVOICES|CONTACT_NOTIFICATIONS)));
				elseif (preg_match('/im([0-9]+)/', $change['fieldname'], $matches))
					$old = $DB->GetOne('SELECT contact AS im FROM customercontacts WHERE id = ? AND type & ? > 0',
						array($matches[1], CONTACT_IM));

				if (isset($old)) {
					$userchanges[$key]['oldvalue'] = $old;
					unset($old);
				} elseif (isset($userchanges[$key][$change['fieldname']]))
					$userchanges[$key]['oldvalue'] = $userchanges[$key][$change['fieldname']];
			}

		$SMARTY->assign('userchanges', $userchanges);
		$SMARTY->display('module:info:setup_changes.html');
	}

	function parse_customer_mail($text, $params) {
		return str_replace('%changes%', implode(', ', array_map('trans', $params['changes'])), $text);
	}

	function module_submit_changes_save() {
		global $LMS;

		$DB = LMSDB::getInstance();

		if (isset($_POST['userchanges'])) {
			$args = array();
			$addresses = array();
			$confirmed_changes = array();
			foreach ($_POST['userchanges'] as $changeid) {
				$changes = $DB->GetRow('SELECT customerid, fieldname, fieldvalue FROM up_info_changes
					WHERE id = ?', array($changeid));
				if (!isset($args[$changes['customerid']])) {
					$args[$changes['customerid']] = array(
						SYSLOG::RES_CUST => $changes['customerid'],
						SYSLOG::RES_USER => Auth::GetCurrentUser(),
					);
					$confirmed_changes[$changes['customerid']] = array();
				}

				if (preg_match('/(phone|email|im)([0-9]+)/', $changes['fieldname'], $matches)) {
					$confirmed_changes[$changes['customerid']][] = $matches[1];
					if ($matches[2]) {
						$fields = array(
							SYSLOG::RES_CUST => $changes['customerid'],
							SYSLOG::RES_CUSTCONTACT => $matches[2],
						);
						if($changes['fieldvalue']) {
							$DB->Execute('UPDATE customercontacts SET contact = ? WHERE id = ?', array($changes['fieldvalue'], $matches[2]));
							if ($LMS->SYSLOG) {
								$fields['contact'] = $changes['fieldvalue'];
								$LMS->SYSLOG->AddMessage(SYSLOG::RES_CUSTCONTACT, SYSLOG::OPER_UPDATE, $fields);
							}
						} else {
							$DB->Execute('DELETE FROM customercontacts WHERE id = ?', array($matches[2]));
							if ($LMS->SYSLOG) {
								$LMS->SYSLOG->AddMessage(SYSLOG::RES_CUSTCONTACT, SYSLOG::OPER_DELETE, $fields);
							}
						}
					} else { // new phone or email
						$DB->Execute('INSERT INTO customercontacts (contact, customerid, type) VALUES(?, ?, ?)',
							array($changes['fieldvalue'], $changes['customerid'], $matches[1] == 'phone' ? CONTACT_LANDLINE : CONTACT_EMAIL));
						if ($LMS->SYSLOG) {
							$fields = array(
								SYSLOG::RES_CUST => $changes['customerid'],
								SYSLOG::RES_CUSTCONTACT => $DB->GetLastInsertID('customercontacts'),
								'contact' => $changes['fieldvalue'],
								'type' => $matches[1] == 'phone' ? CONTACT_LANDLINE : CONTACT_EMAIL,
							);
							$LMS->SYSLOG->AddMessage(SYSLOG::RES_CUSTCONTACT, SYSLOG::OPER_ADD, $fields);
						}
					}
				} else
				switch ($changes['fieldname']) {
					case 'name':
					case 'lastname':
					case 'ssn':
					case 'ten':
						$confirmed_changes[$changes['customerid']][] = $changes['fieldname'];

						$DB->Execute('UPDATE customers SET '.$changes['fieldname'].' = ? WHERE id = ?',
							array($changes['fieldvalue'], $changes['customerid']));
						$args[$changes['customerid']][$changes['fieldname']] = $changes['fieldvalue'];
						break;
					case 'street':
					case 'building':
					case 'apartment':
					case 'zip':
					case 'city':
						$confirmed_changes[$changes['customerid']][] = $changes['fieldname'];

						if ($changes['fieldname'] == 'building')
							$changes['fieldname'] = 'house';
						elseif ($changes['fieldname'] == 'apartment')
							$changes['fieldname'] = 'flat';

						if (!isset($addresses[$changes['customerid']]))
							$addresses[$changes['customerid']] = $DB->GetOne('SELECT address_id FROM customer_addresses WHERE customer_id = ? AND type = ?',
								array($changes['customerid'], BILLING_ADDRESS));

						$DB->Execute('UPDATE addresses SET ' . $changes['fieldname'] . ' = ?
							WHERE id = ?', array($changes['fieldvalue'], $addresses[$changes['customerid']]));
						break;
				}
				$DB->Execute('DELETE FROM up_info_changes WHERE id = ?', array($changeid));
			}

			if ($LMS->SYSLOG && !empty($args))
				foreach ($args as $customerid => $fields)
					if (count($fields) > 2)
						$LMS->SYSLOG->AddMessage(SYSLOG::RES_CUST, SYSLOG::OPER_UPDATE, $fields);

			$mail_subject = ConfigHelper::getConfig('userpanel.change_confirmation_mail_subject');
			$mail_body = ConfigHelper::getConfig('userpanel.change_confirmation_mail_body');
			if (!empty($mail_subject) && !empty($mail_body) && !empty($confirmed_changes)) {
				$mail_sender = ConfigHelper::getConfig('userpanel.change_notification_mail_sender', ConfigHelper::getConfig('mail.smtp_username'));
				foreach ($confirmed_changes as $customerid => $changes)
					if (!empty($changes)) {
						$customerinfo = $LMS->GetCustomer($customerid);
						$mail_recipients = array();
						foreach ($customerinfo['emails'] as $email)
							if (($email['type'] & (CONTACT_NOTIFICATIONS | CONTACT_DISABLED)) == CONTACT_NOTIFICATIONS)
								$mail_recipients[] = $email['contact'];

						if (!empty($mail_recipients)) {
							$subject = parse_customer_mail($mail_subject, array('changes' => $changes));
							$body = parse_customer_mail($mail_body, array('changes' => $changes));

							foreach ($mail_recipients as $mail_recipient) {
								$LMS->SendMail($mail_recipient, array(
										'From' => $mail_sender,
										'To' => $mail_recipient,
										'Subject' => $subject,
									), $body);
							}
						}
					}
			}
		}

		module_changes();
	}

	function module_submit_changes_delete() {
		global $LMS;

		$DB = LMSDB::getInstance();

		if (isset($_POST['userchanges'])) {
			$rejected_changes = array();
			foreach ($_POST['userchanges'] as $changeid) {
				$change = $DB->GetRow('SELECT customerid, fieldname FROM up_info_changes WHERE id = ?',
					array($changeid));
				if (!isset($rejected_changes[$change['customerid']]))
					$rejected_changes[$change['customerid']] = array();
				$rejected_changes[$change['customerid']][] = $change['fieldname'];
				$DB->Execute('DELETE FROM up_info_changes WHERE id = ?', array($changeid));
			}
			$mail_subject = ConfigHelper::getConfig('userpanel.change_rejection_mail_subject');
			$mail_body = ConfigHelper::getConfig('userpanel.change_rejection_mail_body');
			if (!empty($mail_subject) && !empty($mail_body) && !empty($rejected_changes)) {
				$mail_sender = ConfigHelper::getConfig('userpanel.change_notification_mail_sender', ConfigHelper::getConfig('mail.smtp_username'));
				foreach ($rejected_changes as $customerid => $changes)
					if (!empty($changes)) {
						$customerinfo = $LMS->GetCustomer($customerid);
						$mail_recipients = array();
						foreach ($customerinfo['emails'] as $email)
							if (($email['type'] & (CONTACT_NOTIFICATIONS | CONTACT_DISABLED)) == CONTACT_NOTIFICATIONS)
								$mail_recipients[] = $email['contact'];

						if (!empty($mail_recipients)) {
							$subject = parse_customer_mail($mail_subject, array('changes' => $changes));
							$body = parse_customer_mail($mail_body, array('changes' => $changes));

							foreach ($mail_recipients as $mail_recipient) {
								$LMS->SendMail($mail_recipient, array(
										'From' => $mail_sender,
										'To' => $mail_recipient,
										'Subject' => $subject,
									), $body);
							}
						}
					}
			}
		}

		module_changes();
	}

	function module_setup() {
		global $SMARTY, $LMS;

		$SMARTY->assign('hide_nodesbox', ConfigHelper::getConfig('userpanel.hide_nodesbox'));
		$SMARTY->assign('hide_documentbox', ConfigHelper::getConfig('userpanel.hide_documentbox'));
		$SMARTY->assign('consent_text', ConfigHelper::getConfig('userpanel.data_consent_text'));
		$SMARTY->assign('show_confirmed_documents_only', ConfigHelper::checkConfig('userpanel.show_confirmed_documents_only'));
		$SMARTY->assign('pin_changes', ConfigHelper::checkConfig('userpanel.pin_changes'));
		$SMARTY->assign('change_notification_mail_sender', ConfigHelper::getConfig('userpanel.change_notification_mail_sender'));
		$SMARTY->assign('change_notification_mail_recipient', ConfigHelper::getConfig('userpanel.change_notification_mail_recipient'));
		$SMARTY->assign('change_notification_mail_subject', ConfigHelper::getConfig('userpanel.change_notification_mail_subject'));
		$SMARTY->assign('change_notification_mail_body', ConfigHelper::getConfig('userpanel.change_notification_mail_body'));
		$SMARTY->assign('change_confirmation_mail_subject', ConfigHelper::getConfig('userpanel.change_confirmation_mail_subject'));
		$SMARTY->assign('change_confirmation_mail_body', ConfigHelper::getConfig('userpanel.change_confirmation_mail_body'));
		$SMARTY->assign('change_rejection_mail_subject', ConfigHelper::getConfig('userpanel.change_rejection_mail_subject'));
		$SMARTY->assign('change_rejection_mail_body', ConfigHelper::getConfig('userpanel.change_rejection_mail_body'));

		$SMARTY->display('module:info:setup.html');
	}

	function module_submit_setup() {
		$DB = LMSDB::getInstance();

		$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?',
			array(isset($_POST['hide_nodesbox']) ? 1 : 0, 'userpanel', 'hide_nodesbox'));
		$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?',
			array(isset($_POST['hide_documentbox']) ? 1 : 0, 'userpanel', 'hide_documentbox'));
		$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?',
			array($_POST['consent_text'], 'userpanel', 'data_consent_text'));
		$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?',
			array(isset($_POST['show_confirmed_documents_only']) ? 'true' : 'false', 'userpanel', 'show_confirmed_documents_only'));
		$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?',
			array(isset($_POST['pin_changes']) ? 'true' : 'false', 'userpanel', 'pin_changes'));
		$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?',
			array($_POST['change_notification_mail_sender'], 'userpanel', 'change_notification_mail_sender'));
		$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?',
			array($_POST['change_notification_mail_recipient'], 'userpanel', 'change_notification_mail_recipient'));
		$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?',
			array($_POST['change_notification_mail_subject'], 'userpanel', 'change_notification_mail_subject'));
		$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?',
			array($_POST['change_notification_mail_body'], 'userpanel', 'change_notification_mail_body'));
		$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?',
			array($_POST['change_confirmation_mail_subject'], 'userpanel', 'change_confirmation_mail_subject'));
		$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?',
			array($_POST['change_confirmation_mail_body'], 'userpanel', 'change_confirmation_mail_body'));
		$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?',
			array($_POST['change_rejection_mail_subject'], 'userpanel', 'change_rejection_mail_subject'));
		$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?',
			array($_POST['change_rejection_mail_body'], 'userpanel', 'change_rejection_mail_body'));

		header('Location: ?m=userpanel&module=info');
	}
}

?>
