<?php

/*
 * LMS version 1.9-cvs
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

if(!$LMS->UserExists($_GET['id']))
{
	$SESSION->redirect('?m=core&a=userlist');
}

$userinfo = isset($_POST['userinfo']) ? $_POST['userinfo'] : FALSE;

if($userinfo)
{
	$acl = $_POST['acl'];
	$userinfo['id'] = $_GET['id'];
	
	foreach($userinfo as $key => $value)
		$userinfo[$key] = trim($value);

	if($userinfo['login'] == '')
		$error['login'] = trans('Login can\'t be empty!');
	elseif(!eregi('^[a-z0-9.-_]+$',$userinfo['login']))
		$error['login'] = trans('Login contains forbidden characters!');
	elseif($LMS->GetUserIDByLogin($userinfo['login']) && $LMS->GetUserIDByLogin($userinfo['login']) != $_GET['id'])
		$error['login'] = trans('User with specified login exists or that login was used in the past!');

	if($userinfo['name'] == '')
		$error['name'] = trans('You have to enter first and lastname!');

	if($userinfo['email']!='' && !check_email($userinfo['email']))
		$error['email'] = trans('E-mail isn\'t correct!');
				
	// zróbmy maskê ACL...

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

	$userinfo['rights'] = ereg_replace('^[0]*(.*)$','\1',$outmask);

	if(!$error)
	{
		$LMS->UserUpdate($userinfo);
		$SESSION->redirect('?m=core&a=userinfo&id='.$userinfo['id']);
	}

}

foreach($LMS->GetUserInfo($_GET['id']) as $key => $value)
	if(!isset($userinfo[$key]))
		$userinfo[$key] = $value;

$layout['pagetitle'] = trans('User Edit: $0', $userinfo['login']);

$rights = $LMS->GetUserRights($_GET['id']);

foreach($access['table'] as $idx => $row)
{
	$row['id'] = $idx;
	foreach($rights as $right)
		if($right == $idx)
			$row['enabled']=TRUE;
	$accesslist[] = $row;
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('accesslist',$accesslist);
$SMARTY->assign('userinfo',$userinfo);
$SMARTY->assign('unlockedit',TRUE);
$SMARTY->assign('error',$error);

?>
