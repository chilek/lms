<?php

/*
 * LMS version 1.5-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

function GetEmails($group, $network=NULL, $usergroup=NULL)
{
	global $LMS;
	
	if($group == 4)
	{
		$deleted = 1;
		$network = NULL;
		$usergroup = NULL;
	}
	else
		$deleted = 0;
	
	$disabled = ($group == 5) ? 1 : 0;
	$indebted = ($group == 6) ? 1 : 0;
	
	if($group>3) $group = 0;
	
	if($network) 
		$net = $LMS->GetNetworkParams($network);
	
	if($emails = $LMS->DB->GetAll('SELECT users.id AS id, email, '.$LMS->DB->Concat('lastname', "' '", 'users.name').' AS username, '
		.'COALESCE(SUM((type * -2 + 7) * value), 0.00) AS balance '
		.'FROM users LEFT JOIN cash ON (users.id=cash.userid AND (type=3 OR type=4)) '
		.($network ? 'LEFT JOIN nodes ON (user.id=ownerid) ' : '')
		.($usergroup ? 'LEFT JOIN userassignments ON (users.id=userassignments.userid) ' : '')
		.' WHERE deleted = '.$deleted
		.' AND email != \'\''
		.($group!=0 ? ' AND status = '.$group : '')
		.($network ? ' AND (ipaddr > '.$net['address'].' AND ipaddr < '.$net['broadcast'].')' : '')
		.($usergroup ? ' AND usergroupid='.$usergroup : '')
		.' GROUP BY email, lastname, users.name, users.id ORDER BY username'))
	{
		if($disabled)
			$access = $LMS->DB->GetAllByKey('SELECT ownerid AS id FROM nodes GROUP BY ownerid HAVING (SUM(access) != COUNT(access))','id'); 
			
		foreach($emails as $idx => $row)
		{
			if($disabled && $access[$row['id']])
				$emails2[] = $row;
			elseif($indebted)
				if($row['balance'] < 0)
					$emails2[] = $row;
		}
	
		if($disabled || $indebted)
			$emails = $emails2;
	}

	return $emails;
}

$layout['pagetitle'] = trans('Serial Mail');

if($mailing = $_POST['mailing'])
{
	if($mailing['group'] < 0 || $mailing['group'] > 6)
		$error['group'] = trans('Incorrect customers group!');

	if($mailing['sender']=='')
		$error['sender'] = trans('Sender e-mail is required!');
	elseif(!check_email($mailing['sender']))
		$error['sender'] = trans('Specified e-mail isn\'t correct!');

	if($mailing['from']=='')
		$error['from'] = trans('Sender name is required!');

	if($mailing['subject']=='')
		$error['subject'] = trans('Message subject is required!');

	if($mailing['body']=='')
		$error['body'] = trans('Message body is required!');

	if(!$error)
	{
		$layout['nomenu'] = TRUE;
		$mailing['body'] = textwrap($mailing['body']);
		$mailing['body'] = str_replace("\r", '', $mailing['body']);
		$SMARTY->assign('mailing', $mailing);
		$SMARTY->display('header.html');
		$SMARTY->display('mailingsend.html');
		
		if($emails = GetEmails($mailing['group'], $mailing['network'], $mailing['usergroup']))
		{
			if($LMS->CONFIG['phpui']['debug_email'])
				echo '<B>'.trans('Warning! Debug mode (using address $0).',$LMS->CONFIG['phpui']['debug_email']).'</B><BR>';

			foreach($emails as $key => $row)
			{
				if($LMS->CONFIG['phpui']['debug_email'])
					$row['email'] = $LMS->CONFIG['phpui']['debug_email'];

				mail (
					$row['username'].' <'.$row['email'].'>',
					$mailing['subject'],
					$mailing['body'],
					'From: '.$mailing['from'].' <'.$mailing['sender'].">\n".'Content-Type: text/plain; charset='.$LANGDEFS[$LMS->lang]['charset'].";\n".'X-Mailer: LMS-'.$LMS->_version.'/PHP-'.phpversion()."\n".'X-Remote-IP: '.$_SERVER['REMOTE_ADDR']."\n".'X-HTTP-User-Agent: '.$_SERVER['HTTP_USER_AGENT']."\n"
				);
				
				echo '<img src="img/mail.gif" border="0" align="absmiddle" alt=""> '.trans('$0 of $1 ($2): $3 &lt;$4&gt;', ($key+1), sizeof($emails), sprintf('%02.2f',round((100/sizeof($emails))*($key+1),2)), $row['username'], $row['email'])."<BR>\n";
				flush();
			}
		}
		
		$SMARTY->display("mailingsend-footer.html");
		$SMARTY->display('footer.html');
		die;
	}
}

$SMARTY->assign('error', $error);
$SMARTY->assign('mailing', $mailing);
$SMARTY->assign('networks', $LMS->GetNetworks());
$SMARTY->assign('usergroups', $LMS->UsergroupGetAll());
$SMARTY->assign('admininfo', $LMS->GetAdminInfo($SESSION->id));
$SMARTY->display('mailing.html');

?>
