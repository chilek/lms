<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

function getMessageTemplate($tmplid, $elem) {
	global $DB;

	$result = new xajaxResponse();
	$message = $DB->GetOne('SELECT message FROM templates WHERE id = ?', array($tmplid));
	$result->call('messageTemplateReceived', $elem, $message);

	return $result;
}

function getMessageTemplates($tmpltype) {
	global $LMS;

	$result = new xajaxResponse();
	$templates = $LMS->GetMessageTemplates($tmpltype);
	$result->call('messageTemplatesReceived', $templates);

	return $result;
}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction(array('getMessageTemplate', 'getMessageTemplates'));
$SMARTY->assign('xajax', $LMS->RunXajax());

function GetRecipients($filter, $type = MSG_MAIL) {
	global $LMS;

	$group = intval($filter['group']);
	$network = intval($filter['network']);
	if (is_array($filter['customergroup'])) {
		$customergroup = array_map('intval', $filter['customergroup']);
		$customergroup = implode(',', $customergroup);
	} else
		$customergroup = intval($filter['customergroup']);
	$nodegroup = intval($filter['nodegroup']);
	$linktype = intval($filter['linktype']);
	$tarifftype = intval($filter['tarifftype']);
	$consent = isset($filter['consent']);

	if($group == 4)
	{
		$deleted = 1;
		$network = NULL;
		$customergroup = NULL;
	}
	else
		$deleted = 0;

	$disabled = ($group == 5) ? 1 : 0;
	$indebted = ($group == 6) ? 1 : 0;
	$notindebted = ($group == 7) ? 1 : 0;

	if($group>3) $group = 0;

	if($network)
		$net = $LMS->GetNetworkParams($network);

	if($type == MSG_SMS)
	{
		$smstable = 'JOIN (SELECT ' . $LMS->DB->GroupConcat('phone') . ' AS phone, customerid
				FROM customercontacts
				WHERE (type & '.CONTACT_MOBILE.') = '.CONTACT_MOBILE.'
				GROUP BY customerid
			) x ON (x.customerid = c.id) ';
	}

	if ($tarifftype) {
		$tarifftable = 'JOIN (
			SELECT DISTINCT a.customerid FROM assignments a
			JOIN tariffs t ON t.id = a.tariffid
			WHERE a.suspended = 0
				AND (a.datefrom = 0 OR a.datefrom < ?NOW?)
				AND (a.dateto = 0 OR a.dateto > ?NOW?)
				AND t.type = ' . $tarifftype . '
		) a ON a.customerid = c.id ';
	}

	$recipients = $LMS->DB->GetAll('SELECT c.id, email, pin, '
		.($type==MSG_SMS ? 'x.phone, ': '')
		.$LMS->DB->Concat('c.lastname', "' '", 'c.name').' AS customername,
		COALESCE(b.value, 0) AS balance
		FROM customersview c 
		LEFT JOIN (
			SELECT SUM(value) AS value, customerid
			FROM cash GROUP BY customerid
		) b ON (b.customerid = c.id) '
		.(!empty($smstable) ? $smstable : '')
		. ($tarifftype ? $tarifftable : '')
		.'WHERE deleted = ' . $deleted
		. ($consent ? ' AND c.mailingnotice = 1' : '')
		.($type == MSG_MAIL ? ' AND email != \'\'' : '')
		.($group!=0 ? ' AND status = '.$group : '')
		.($network ? ' AND c.id IN (SELECT ownerid FROM nodes WHERE 
			(netid = ' . $net['id'] . ' AND ipaddr > ' . $net['address'] . ' AND ipaddr < ' . $net['broadcast'] . ')
			OR (ipaddr_pub > '.$net['address'].' AND ipaddr_pub < '.$net['broadcast'].'))' : '')
		.($customergroup ? ' AND c.id IN (SELECT customerid FROM customerassignments
			WHERE customergroupid IN (' . $customergroup . '))' : '')
		.($nodegroup ? ' AND c.id IN (SELECT ownerid FROM nodes
			JOIN nodegroupassignments ON (nodeid = nodes.id)
			WHERE nodegroupid = ' . $nodegroup . ')' : '')
		.($linktype != '' ? ' AND c.id IN (SELECT ownerid FROM nodes
			WHERE linktype = ' . $linktype . ')' : '')
		.($disabled ? ' AND EXISTS (SELECT 1 FROM nodes WHERE ownerid = c.id
			GROUP BY ownerid HAVING (SUM(access) != COUNT(access)))' : '')
		.($indebted ? ' AND COALESCE(b.value, 0) < 0' : '')
		.($notindebted ? ' AND COALESCE(b.value, 0) >= 0' : '')
		. ($tarifftype ? ' AND NOT EXISTS (SELECT id FROM assignments
			WHERE customerid = c.id AND tariffid = 0 AND liabilityid = 0
				AND (datefrom = 0 OR datefrom < ?NOW?)
				AND (dateto = 0 OR dateto > ?NOW?))' : '')
		.' ORDER BY customername');

	return $recipients;
}

function GetRecipient($customerid, $type=MSG_MAIL)
{
	global $LMS;

	if($type == MSG_SMS)
	{
		$smstable = 'JOIN (SELECT ' . $LMS->DB->GroupConcat('phone') . ' AS phone, customerid
				FROM customercontacts 
				WHERE customerid = '.$customerid.'
					AND (type & '.CONTACT_MOBILE.') = '.CONTACT_MOBILE.'
				GROUP BY customerid
			) x ON (x.customerid = c.id) ';
	}

	return $LMS->DB->GetAll('SELECT c.id, email, pin, '
		.($type==MSG_SMS ? 'x.phone, ': '')
		.$LMS->DB->Concat('c.lastname', "' '", 'c.name').' AS customername,
		COALESCE((SELECT SUM(value) FROM cash WHERE customerid = c.id), 0) AS balance
		FROM customersview c '
		.(!empty($smstable) ? $smstable : '')
		.'WHERE c.id = '.$customerid
		.($type == MSG_MAIL ? ' AND email != \'\'' : ''));
}

function BodyVars(&$body, $data)
{
	global $LMS, $LANGDEFS;

	$body = str_replace('%customer', $data['customername'], $body);
	$body = str_replace('%balance', $data['balance'], $body);
	$body = str_replace('%cid', $data['id'], $body);
	$body = str_replace('%pin', $data['pin'], $body);
	if (strpos($body, '%bankaccount') !== false)
		$body = str_replace('%bankaccount', format_bankaccount(bankaccount($data['id'])), $body);

	if(!(strpos($body, '%last_10_in_a_table') === FALSE))
	{
		$last10 = '';
		if($last10_array = $LMS->DB->GetAll('SELECT comment, time, value 
			FROM cash WHERE customerid = ?
			ORDER BY time DESC LIMIT 10', array($data['id'])))
		{
			foreach($last10_array as $r)
			{
				$last10 .= date("Y/m/d | ", $r['time']);
				$last10 .= sprintf("%20s | ", sprintf($LANGDEFS[$LMS->ui_lang]['money_format'], $r['value']));
				$last10 .= $r['comment']."\n";
			}
		}
		$body = str_replace('%last_10_in_a_table', $last10, $body);
	}
}

$layout['pagetitle'] = trans('Message Add');

if(isset($_POST['message']))
{
	$message = $_POST['message'];

	if ($message['type'] == MSG_MAIL)
		$message['type'] == MSG_MAIL;
	elseif ($message['type'] == MSG_SMS)
		$message['type'] == MSG_SMS;
	elseif ($message['type'] == MSG_ANYSMS)
		$message['type'] == MSG_ANYSMS;
	else
		$message['type'] == MSG_WWW;

	if(empty($message['customerid']) && ($message['group'] < 0 || $message['group'] > 7))
		$error['group'] = trans('Incorrect customers group!');

	if ($message['type'] == MSG_MAIL) {
		$message['body'] = $message['mailbody'];
		if ($message['sender'] == '')
			$error['sender'] = trans('Sender e-mail is required!');
		elseif (!check_email($message['sender']))
			$error['sender'] = trans('Specified e-mail is not correct!');
		if ($message['from'] == '')
			$error['from'] = trans('Sender name is required!');
	} elseif ($message['type'] == MSG_WWW)
		$message['body'] = $message['mailbody'];
	else {
		$message['body'] = $message['smsbody'];
		$message['sender'] = '';
		$message['from'] = '';
		$phonenumbers = array();
		if ($message['type'] == MSG_ANYSMS)
		{
			$message['phonenumber'] = preg_replace('/[ \t]/', '', $message['phonenumber']);
			if (preg_match('/^[\+]?[0-9]+(,[\+]?[0-9]+)*$/', $message['phonenumber']))
				$phonenumbers = preg_split('/,/', $message['phonenumber']);
			if (count($message['users']))
				$phonenumbers = array_merge($phonenumbers, $message['users']);
			if (empty($phonenumbers))
				$error['phonenumber'] = trans('Specified phone number is not correct!');
		}
	}

	$msgtmplid = intval($message['tmplid']);
	$msgtmploper = intval($message['tmploper']);
	$msgtmplname = $message['tmplname'];
	if ($msgtmploper > 1) {
		switch ($message['type']) {
			case MSG_MAIL:
				$msgtmpltype = TMPL_MAIL;
				break;
			case MSG_SMS:
			case MSG_ANYSMS:
				$msgtmpltype = TMPL_SMS;
				break;
			case MSG_WWW:
				$msgtmpltype = TMPL_WWW;
				break;
		}
		switch ($msgtmploper) {
			case 2:
				if (empty($msgtmplid))
					break;
				$LMS->UpdateMessageTemplate($msgtmplid, $msgtmpltype, null, $message['body']);
				break;
			case 3:
				if (!strlen($msgtmplname))
					break;
				$LMS->AddMessageTemplate($msgtmpltype, $msgtmplname, $message['body']);
				break;
		}
	}

	if($message['subject']=='')
		$error['subject'] = trans('Message subject is required!');

	if($message['body']=='')
		$error['body'] = trans('Message body is required!');

	if($filename = $_FILES['file']['name'])
	{
		if(is_uploaded_file($_FILES['file']['tmp_name']) && $_FILES['file']['size'])
		{
			$file = '';
			$fd = fopen($_FILES['file']['tmp_name'], 'r');
			if($fd)
			{
				while(!feof($fd))
					$file .= fread($fd,256);
				fclose($fd);
			}
		} 
		else // upload errors
			switch($_FILES['file']['error'])
			{
				case 1:
				case 2: $error['file'] = trans('File is too large.'); break;
				case 3: $error['file'] = trans('File upload has finished prematurely.'); break;
				case 4: $error['file'] = trans('Path to file was not specified.'); break;
				default: $error['file'] = trans('Problem during file upload.'); break;
			}
	}

	if(!$error)
	{
		$recipients = array();
		if(empty($message['customerid']))
			if ($message['type'] != MSG_ANYSMS)
				$recipients = GetRecipients($message, $message['type']);
			else
				foreach ($phonenumbers as $phone)
					$recipients[]['phone'] = $phone;
		else
			$recipients = GetRecipient($message['customerid'], $message['type']);

		if(!$recipients)
			$error['subject'] = trans('Unable to send message. No recipients selected!');
	}

	if(!$error)
	{
		set_time_limit(0);

		$message['body'] = str_replace("\r", '', $message['body']);

		if($message['type'] == MSG_MAIL)
			$message['body'] = wordwrap($message['body'],76,"\n");

		$SMARTY->assign('message', $message);
		$SMARTY->assign('recipcount', sizeof($recipients));
		$SMARTY->display('messagesend.html');

		$DB->BeginTrans();

		$DB->Execute('INSERT INTO messages (type, cdate, subject, body, userid, sender)
			VALUES (?, ?NOW?, ?, ?, ?, ?)', array(
				$message['type'],
				$message['subject'],
				$message['body'],
				$AUTH->id,
				$message['type'] == MSG_MAIL ? '"' . $message['from'] . '" <' . $message['sender'] . '>' : '',
			));

		$msgid = $DB->GetLastInsertID('messages');

		foreach ($recipients as $key => $row) {
			if ($message['type'] == MSG_MAIL)
				$recipients[$key]['destination'] = explode(',', $row['email']);
			elseif ($message['type'] == MSG_WWW)
				$recipients[$key]['destination'] = array(trans('www'));
			else
				$recipients[$key]['destination'] = explode(',', $row['phone']);

			foreach ($recipients[$key]['destination'] as $destination)
				$DB->Execute('INSERT INTO messageitems (messageid, customerid,
					destination, status)
					VALUES (?, ?, ?, ?)', array(
						$msgid,
						isset($row['id']) ? $row['id'] : 0,
						$destination,
						MSG_NEW,
					));
		}

		$DB->CommitTrans();

		if($message['type'] == MSG_MAIL)
		{
			$files = NULL;
			if (isset($file))
			{
				$files[0]['content_type'] = $_FILES['file']['type'];
				$files[0]['filename'] = $filename;
				$files[0]['data'] = $file;
			}

			if(!empty($CONFIG['mail']['debug_email']))
				echo '<B>'.trans('Warning! Debug mode (using address $a).',$CONFIG['mail']['debug_email']).'</B><BR>';

			$headers['From'] = '"'.$message['from'].'" <'.$message['sender'].'>';
			$headers['Subject'] = $message['subject'];
			$headers['Reply-To'] = $headers['From'];
			if (!empty($message['wysiwyg']))
				$headers['X-LMS-Format'] = 'html';
		} elseif ($message['type'] != MSG_WWW) {
			if (!empty($CONFIG['sms']['debug_phone']))
				echo '<B>'.trans('Warning! Debug mode (using phone $a).',$CONFIG['sms']['debug_phone']).'</B><BR>';
		}

		foreach ($recipients as $key => $row) {
			$body = $message['body'];

			BodyVars($body, $row);

			foreach ($row['destination'] as $destination) {
				$orig_destination = $destination;
				if ($message['type'] == MSG_MAIL) {
					$headers['To'] = '<' . $destination . '>';
					echo '<img src="img/mail.gif" border="0" align="absmiddle" alt=""> ';
				} elseif ($message['type'] == MSG_WWW)
					echo '<img src="img/network.gif" border="0" align="absmiddle" alt=""> ';
				else {
					$destination = preg_replace('/[^0-9]/', '', $destination);
					echo '<img src="img/sms.gif" border="0" align="absmiddle" alt=""> ';
				}

				echo trans('$a of $b ($c) $d:', ($key + 1), sizeof($recipients),
				sprintf('%02.1f%%', round((100 / sizeof($recipients)) * ($key + 1), 1)),
					$row['customername'] . ' &lt;' . $destination . '&gt;');
				flush();

				if ($message['type'] == MSG_MAIL)
					$result = $LMS->SendMail($destination, $headers, $body, $files);
				elseif ($message['type'] == MSG_WWW)
					$result = MSG_NEW;
				else
					$result = $LMS->SendSMS($destination, $body, $msgid);

				if (is_string($result))
					echo " <font color=red>$result</font>";
				else if ($result == MSG_SENT)
					echo ' ['.trans('sent').']';
				else 
					echo ' ['.trans('added').']';

				echo "<BR>\n";

				if (!is_int($result) || $result == MSG_SENT)
					$DB->Execute('UPDATE messageitems SET status = ?, lastdate = ?NOW?,
						error = ? WHERE messageid = ? AND customerid = ?
							AND destination = ?',
						array(
							is_int($result) ? $result : MSG_ERROR,
							is_int($result) ? null : $result,
							$msgid,
							$row['id'],
							$orig_destination,
						));
			}
		}

		$SMARTY->display('footer.html');
		$SESSION->close();
		die;
	}
	else if (!empty($message['customerid']))
	{
		$message['customer'] = $DB->GetOne('SELECT '
			.$DB->Concat('UPPER(lastname)',"' '",'name').'
			FROM customersview
			WHERE id = ?', array($message['customerid']));
	}

	$SMARTY->assign('error', $error);
	$SMARTY->assign('message', $message);
}
else if (!empty($_GET['customerid']))
{
	$message = $DB->GetRow('SELECT id AS customerid, '
		.$DB->Concat('UPPER(lastname)',"' '",'name').' AS customer
		FROM customersview
		WHERE id = ?', array($_GET['customerid']));

	$SMARTY->assign('message', $message);
}

if (isset($message['type'])) {
	switch ($message['type']) {
		case MSG_MAIL:
			$msgtmpltype = TMPL_MAIL;
			break;
		case MSG_SMS:
		case MSG_ANYSMS:
			$msgtmpltype = TMPL_SMS;
			break;
		case MSG_WWW:
			$msgtmpltype = TMPL_WWW;
			break;
	}
} else
	$msgtmpltype = TMPL_MAIL;
$SMARTY->assign('messagetemplates', $LMS->GetMessageTemplates($msgtmpltype));
$SMARTY->assign('networks', $LMS->GetNetworks());
$SMARTY->assign('customergroups', $LMS->CustomergroupGetAll());
$SMARTY->assign('nodegroups', $LMS->GetNodeGroupNames());
$SMARTY->assign('userinfo', $LMS->GetUserInfo($AUTH->id));
$SMARTY->assign('users', $DB->GetAll('SELECT name, phone FROM users WHERE phone <> \'\' ORDER BY name'));
$SMARTY->display('messageadd.html');

?>
