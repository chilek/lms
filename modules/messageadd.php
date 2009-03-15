<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2009 LMS Developers
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

function GetRecipients($filter, $type='mail')
{
	global $DB, $LMS;
	
	$group = $filter['group'];
	$network = $filter['network'];
	$customergroup = $filter['customergroup'];
	$nodegroup = $filter['nodegroup'];
	$linktype = $filter['linktype'];
	
	switch($type)
	{
		case 'sms': $type = 'sms'; break;
		default: $type = 'mail'; break;
	}
	
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
	
	$recipients = $DB->GetAll('SELECT c.id, email, pin, '
		.$DB->Concat('c.lastname', "' '", 'c.name').' AS customername,
		COALESCE(b.value, 0) AS balance
		FROM customersview c 
		LEFT JOIN (
			SELECT SUM(value) AS value, customerid
			FROM cash GROUP BY customerid
		) b ON (b.customerid = c.id)
		WHERE deleted = '.$deleted
		.' AND email != \'\''
		.($group!=0 ? ' AND status = '.$group : '')
		.($network ? ' AND c.id IN (SELECT ownerid FROM nodes WHERE 
			(ipaddr > '.$net['address'].' AND ipaddr < '.$net['broadcast'].') 
			OR (ipaddr_pub > '.$net['address'].' AND ipaddr_pub < '.$net['broadcast'].'))' : '')
		.($customergroup ? ' AND c.id IN (SELECT customerid FROM customerassignments
			WHERE customergroupid = '.intval($customergroup).')' : '')
		.($nodegroup ? ' AND c.id IN (SELECT ownerid FROM nodes
			JOIN nodegroupassignments ON (nodeid = nodes.id)
			WHERE nodegroupid = '.intval($nodegroup).')' : '')
		.($linktype != '' ? ' AND c.id IN (SELECT ownerid FROM nodes
			WHERE linktype = '.intval($linktype).')' : '')
		.($disabled ? ' AND EXISTS (SELECT 1 FROM nodes WHERE ownerid = c.id
			GROUP BY ownerid HAVING (SUM(access) != COUNT(access)))' : '')
		.($indebted ? ' AND COALESCE(b.value, 0) < 0' : '')
		.($notindebted ? ' AND COALESCE(b.value, 0) >= 0' : '')
		.' ORDER BY customername');

	return $recipients;
}

function BodyVars(&$body, $data)
{
	$body = str_replace('%customer', $data['customername'], $body);
	$body = str_replace('%balance', $data['balance'], $body);
	$body = str_replace('%cid', $data['id'], $body);
	$body = str_replace('%pin', $data['pin'], $body);

	if(!(strpos($body, '%last_10_in_a_table') === FALSE))
	{
		$last10 = '';
		if($last10_array = $DB->GetAll('SELECT comment, time, value 
			FROM cash WHERE customerid = ?
			ORDER BY time DESC LIMIT 10', array($data['id'])))
		{
			foreach($last10_array as $r)
			{
				$last10 .= date("Y/m/d | ", $r['time']);
				$last10 .= sprintf("%20s | ", sprintf($LANGDEFS[$LMS->lang][money_format],$r['value']));
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

	if($message['group'] < 0 || $message['group'] > 7)
		$error['group'] = trans('Incorrect customers group!');

	if($message['sender']=='')
		$error['sender'] = trans('Sender e-mail is required!');
	elseif(!check_email($message['sender']))
		$error['sender'] = trans('Specified e-mail is not correct!');

	if($message['from']=='')
		$error['from'] = trans('Sender name is required!');

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
		$recipients = GetRecipients($message);
		
		if(!$recipients)
			$error['subject'] = trans('Unable to send message. No recipients selected!');
	}

	if(!$error)
	{	
		set_time_limit(0);

		$message['body'] = wordwrap($message['body'],76,"\n");
		$message['body'] = str_replace("\r", '', $message['body']);
		
		$layout['nomenu'] = TRUE;
		$SMARTY->assign('message', $message);
		$SMARTY->assign('recipcount', sizeof($recipients));
		$SMARTY->display('messagesend.html');

		$DB->BeginTrans();

		$DB->Execute('INSERT INTO messages (type, cdate, subject, body, userid, sender)
			VALUES (?, ?NOW?, ?, ?, ?, ?)', array(
				MSG_MAIL,
				$message['subject'],
				$message['body'],
				$AUTH->id,
				'"'.$message['from'].'" <'.$message['sender'].'>',
			));
		
		$msgid = $DB->GetLastInsertID('messages');

		foreach($recipients as $key => $row)
		{
			$recipients[$key]['destination'] = !empty($CONFIG['phpui']['debug_email']) ? $CONFIG['phpui']['debug_email'] : $row['email'];
			$DB->Execute('INSERT INTO messageitems (messageid, customerid,
				destination, status)
				VALUES (?, ?, ?, ?)', array(
					$msgid,
					$row['id'],
					$recipients[$key]['destination'],
					MSG_NEW,
				));
		}
		
		$DB->CommitTrans();
		
		$files = NULL;
		if (isset($file))
		{
			$files[0]['content_type'] = $_FILES['file']['type'];
			$files[0]['filename'] = $filename;
			$files[0]['data'] = $file;
		}

		if(!empty($CONFIG['phpui']['debug_email']))
			echo '<B>'.trans('Warning! Debug mode (using address $0).',$CONFIG['phpui']['debug_email']).'</B><BR>';
			
		$headers['Date'] = date('r');
		$headers['From'] = '"'.$message['from'].'" <'.$message['sender'].'>';
		$headers['Subject'] = $message['subject'];
		$headers['Reply-To'] = $headers['From'];
			
		foreach($recipients as $key => $row)
		{
			$body = $message['body'];
				
			BodyVars($body, $row);
				
			$headers['To'] = '<'.$row['destination'].'>';

			echo '<img src="img/mail.gif" border="0" align="absmiddle" alt=""> ';
			echo trans('$0 of $1 ($2): $3 &lt;$4&gt;',
				($key+1),
				sizeof($recipients),
				sprintf('%02.1f%%',round((100/sizeof($recipients))*($key+1),1)),
				$row['customername'],
				$row['destination']);
				
			flush();
			$error = $LMS->SendMail($row['destination'], $headers, $body, $files);
				
			echo ($error ? " <font color=red>$error</font>" : '[OK]')."<BR>\n";

			$DB->Execute('UPDATE messageitems SET status = ?, lastdate = ?NOW?,
				error = ? WHERE messageid = ? AND customerid = ?',
				array(
					$error ? MSG_ERROR : MSG_SENT,
					$error ? $error : null,
					$msgid,
					$row['id'],
				));
		}
		
		echo '<P><B><A HREF="javascript:window.close();">'.trans('You can close this window now.').'</A></B></P>';
		echo '</BLOCKQUOTE>';
		
		$SMARTY->display('footer.html');
		$SESSION->close();
		die;
	}

	$SMARTY->assign('message', $message);
	$SMARTY->assign('error', $error);
}

$SMARTY->assign('networks', $LMS->GetNetworks());
$SMARTY->assign('customergroups', $LMS->CustomergroupGetAll());
$SMARTY->assign('nodegroups', $LMS->GetNodeGroupNames());
$SMARTY->assign('userinfo', $LMS->GetUserInfo($AUTH->id));
$SMARTY->display('messageadd.html');

?>
