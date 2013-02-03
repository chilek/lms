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

if(!$LMS->UserExists($_GET['id']))
{
	$SESSION->redirect('?m=userlist');
}

$userinfo = isset($_POST['userinfo']) ? $_POST['userinfo'] : FALSE;

if($userinfo)
{
	$acl = $_POST['acl'];
	$userinfo['id'] = $_GET['id'];

	foreach($userinfo as $key => $value)
	    if (!is_array($value))
    		$userinfo[$key] = trim($value);

	if($userinfo['login'] == '')
		$error['login'] = trans('Login can\'t be empty!');
	elseif(!preg_match('/^[a-z0-9._-]+$/i', $userinfo['login']))
		$error['login'] = trans('Login contains forbidden characters!');
	elseif($LMS->GetUserIDByLogin($userinfo['login']) && $LMS->GetUserIDByLogin($userinfo['login']) != $_GET['id'])
		$error['login'] = trans('User with specified login exists or that login was used in the past!');

	if($userinfo['name'] == '')
		$error['name'] = trans('You have to enter first and lastname!');

	if($userinfo['email']!='' && !check_email($userinfo['email']))
		$error['email'] = trans('E-mail isn\'t correct!');

	if($userinfo['accessfrom'] == '')
		$accessfrom = 0;
	elseif(preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/',$userinfo['accessfrom']))
	{
		list($y, $m, $d) = explode('/', $userinfo['accessfrom']);
		if(checkdate($m, $d, $y))
			$accessfrom = mktime(0, 0, 0, $m, $d, $y);
		else
			$error['accessfrom'] = trans('Incorrect charging time!');
	}
	else
		$error['accessfrom'] = trans('Incorrect charging time!');

	if($userinfo['accessto'] == '')
		$accessto = 0;
	elseif(preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $userinfo['accessto']))
	{
		list($y, $m, $d) = explode('/', $userinfo['accessto']);
		if(checkdate($m, $d, $y))
			$accessto = mktime(23, 59, 59, $m, $d, $y);
		else
			$error['accessto'] = trans('Incorrect charging time!');
	}
	else
		$error['accessto'] = trans('Incorrect charging time!');

	if($accessto < $accessfrom && $accessto != 0 && $accessfrom != 0)
		$error['accessto'] = trans('Incorrect date range!');
	
	// let's make an ACL mask...
	$mask = '';
	$outmask = '';

	for($i=0;$i<256;$i++)
		$mask .= '0';

	foreach($access['table'] as $idx => $row)
		if(isset($acl[$idx]))
			$mask[255-$idx] = '1';

	for($i=0;$i<256;$i += 4)
		$outmask = $outmask . dechex(bindec(substr($mask,$i,4)));

	$userinfo['rights'] = preg_replace('/^[0]*(.*)$/','\1',$outmask);

    if (!empty($userinfo['ntype'])) {
        $userinfo['ntype'] = array_sum(array_map('intval', $userinfo['ntype']));
    }

	if(!$error)
	{
		$userinfo['accessfrom'] = $accessfrom;
		$userinfo['accessto'] = $accessto;
		$LMS->UserUpdate($userinfo);

		$DB->Execute('DELETE FROM excludedgroups WHERE userid = ?', array($userinfo['id']));
		if(isset($_POST['selected']))
		        foreach($_POST['selected'] as $idx => $name)
				$DB->Execute('INSERT INTO excludedgroups (customergroupid, userid)
				    		VALUES(?, ?)', array($idx, $userinfo['id']));

		$SESSION->redirect('?m=userinfo&id='.$userinfo['id']);
	}
	else
	{
		$userinfo['selected'] = array();
		if(isset($_POST['selected']))
		{
		        foreach($_POST['selected'] as $idx => $name)
			{
			        $userinfo['selected'][$idx]['id'] = $idx;
			    	$userinfo['selected'][$idx]['name'] = $name;
			}
		}

		foreach($access['table'] as $idx => $row)
		{
			$row['id'] = $idx;
			if(isset($acl[$idx]))
				$row['enabled'] = TRUE;

			$accesslist[] = $row;
		}
	}
}
else
{
	$rights = $LMS->GetUserRights($_GET['id']);

	foreach($access['table'] as $idx => $row)
	{
		$row['id'] = $idx;
		foreach($rights as $right)
			if($right == $idx)
				$row['enabled'] = TRUE;
		$accesslist[] = $row;
	}
}

foreach($LMS->GetUserInfo($_GET['id']) as $key => $value)
	if(!isset($userinfo[$key]))
		$userinfo[$key] = $value;

if(!isset($userinfo['selected']))
	$userinfo['selected'] = $DB->GetAllByKey('SELECT g.id, g.name 
		FROM customergroups g, excludedgroups
	        WHERE customergroupid = g.id AND userid = ?
		ORDER BY name', 'id', array($userinfo['id']));

$layout['pagetitle'] = trans('User Edit: $a', $userinfo['login']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('accesslist', $accesslist);
$SMARTY->assign('available', $DB->GetAllByKey('SELECT id, name FROM customergroups ORDER BY name', 'id'));
$SMARTY->assign('userinfo', $userinfo);
$SMARTY->assign('error', $error);

$SMARTY->display('useredit.html');

?>
