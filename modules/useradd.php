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

$acl = isset($_POST['acl']) ? $_POST['acl'] : array();
$useradd = isset($_POST['useradd']) ? $_POST['useradd'] : array();

if(sizeof($useradd))
{
    
        $error = array();
    
	foreach($useradd as $key => $value)
	    if (!is_array($value))
		    $useradd[$key] = trim($value);

	if($useradd['login']=='' && $useradd['name']=='' && $useradd['password']=='' && $useradd['confirm']=='')
	{
		$SESSION->redirect('?m=useradd');
	}

	if($useradd['login']=='')
		$error['login'] = trans('Login can\'t be empty!');
	elseif(!preg_match('/^[a-z0-9.-_]+$/i', $useradd['login']))
		$error['login'] = trans('Login contains forbidden characters!');
	elseif($LMS->GetUserIDByLogin($useradd['login']))
		$error['login'] = trans('User with specified login exists or that login was used in the past!');

	if($useradd['email']!='' && !check_email($useradd['email']))
		$error['email'] = trans('E-mail isn\'t correct!');

	if($useradd['name']=='')
		$error['name'] = trans('You have to enter first and lastname!');

	if ($useradd['password'] == '')
		$error['password'] = trans('Empty passwords are not allowed!');
	elseif ($useradd['password'] != $useradd['confirm'])
		$error['password'] = trans('Passwords does not match!');
	elseif (!check_password_strength($useradd['password']))
		$error['password'] = trans('The password should contain at least one capital letter, one lower case letter, one digit and should consist of at least 8 characters!');

	if($useradd['accessfrom'] == '')
		$accessfrom = 0;
	elseif(preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/',$useradd['accessfrom']))
	{
		list($y, $m, $d) = explode('/', $useradd['accessfrom']);
		if(checkdate($m, $d, $y))
			$accessfrom = mktime(0, 0, 0, $m, $d, $y);
		else
			$error['accessfrom'] = trans('Incorrect charging time!');
	}
	else
		$error['accessfrom'] = trans('Incorrect charging time!');

	if($useradd['accessto'] == '')
		$accessto = 0;
	elseif(preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $useradd['accessto']))
	{
		list($y, $m, $d) = explode('/', $useradd['accessto']);
		if(checkdate($m, $d, $y))
			$accessto = mktime(23, 59, 59, $m, $d, $y);
		else
			$error['accessto'] = trans('Incorrect charging time!');
	}
	else
		$error['accessto'] = trans('Incorrect charging time!');

	if($accessto < $accessfrom && $accessto != 0 && $accessfrom != 0)
		$error['accessto'] = trans('Incorrect date range!');

	// ACL mask...
	$mask = '';
	$outmask = '';

	for($i=0;$i<256;$i++)
		$mask .= '0';

	foreach($access['table'] as $idx => $row)
		if(isset($acl[$idx]))
			if($acl[$idx]=='1')
				$mask[255-$idx] = '1';

	for($i=0;$i<256;$i += 4)
		$outmask = $outmask . dechex(bindec(substr($mask,$i,4)));

	$useradd['rights'] = preg_replace('/^[0]*(.*)$/','\1',$outmask);

	if (!empty($useradd['ntype']))
		$useradd['ntype'] = array_sum(array_map('intval', $useradd['ntype']));

        $hook_data = $LMS->executeHook('useradd_validation_before_submit', array('useradd' => $useradd,
                                                                                 'error' => $error));
        $useradd = $hook_data['useradd'];
        $error   = $hook_data['error'];

	if (!$error) {
		$useradd['accessfrom'] = $accessfrom;
		$useradd['accessto'] = $accessto;
		$id = $LMS->UserAdd($useradd);

		if (isset($_POST['selected']))
			foreach ($_POST['selected'] as $idx => $name) {
				$DB->Execute('INSERT INTO excludedgroups (customergroupid, userid)
					VALUES(?, ?)', array($idx, $id));
				if ($SYSLOG) {
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_EXCLGROUP] =>
							$DB->GetLastInsertID('excludedgroups'),
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUSTGROUP] => $idx,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => $id
					);
					$SYSLOG->AddMessage(SYSLOG_RES_EXCLGROUP, SYSLOG_OPER_ADD,
						$args, array_keys($args));
				}
			}

                $LMS->executeHook('useradd_after_submit', $id);
		$SESSION->redirect('?m=userinfo&id='.$id);
	} elseif (isset($_POST['selected']))
		foreach ($_POST['selected'] as $idx => $name) {
			$useradd['selected'][$idx]['id'] = $idx;
			$useradd['selected'][$idx]['name'] = $name;
		}
} else
	$useradd['ntype'] = MSG_MAIL | MSG_SMS;

foreach($access['table'] as $idx => $row)
{
	$row['id'] = $idx;
	if(isset($acl[$idx]))
		if($acl[$idx] == '1')
			$row['enabled'] = TRUE;
	$accesslist[] = $row;
}

if($AUTH->nousers == TRUE)           // if there is no users
    $accesslist[0][enabled]=1;       // then new users should have "full privileges" checked to make new installation more human error proof.

$layout['pagetitle'] = trans('New User');

$SMARTY->assign('useradd', $useradd);
$SMARTY->assign('error', $error);
$SMARTY->assign('accesslist', $accesslist);
$SMARTY->assign('available', $DB->GetAllByKey('SELECT id, name FROM customergroups ORDER BY name', 'id'));

$SMARTY->display('useradd.html');

?>
