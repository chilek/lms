<?php

/*
 * LMS version 1.7-cvs
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

if(!$LMS->AdminExists($_GET['id']))
{
	$SESSION->redirect('?m=adminlist');
}

$admininfo = isset($_POST['admininfo']) ? $_POST['admininfo'] : FALSE;

if($admininfo)
{
	$acl = $_POST['acl'];
	$admininfo['id'] = $_GET['id'];
	
	foreach($admininfo as $key => $value)
		$admininfo[$key] = trim($value);

	if($admininfo['login'] == '')
		$error['login'] = trans('Login can\'t be empty!');
	elseif(!eregi('^[a-z0-9.-_]+$',$admininfo['login']))
		$error['login'] = trans('Login contains forbidden characters!');
	elseif($LMS->GetAdminIDByLogin($admininfo['login']) && $LMS->GetAdminIDByLogin($admininfo['login']) != $_GET['id'])
		$error['login'] = trans('User with specified login exists or that login was used in the past!');

	if($admininfo['name'] == '')
		$error['name'] = trans('You have to enter first and lastname!');

	if($admininfo['email']!='' && !check_email($admininfo['email']))
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

	$admininfo['rights'] = ereg_replace('^[0]*(.*)$','\1',$outmask);

	if(!$error)
	{
		$LMS->AdminUpdate($admininfo);
		$SESSION->redirect('?m=admininfo&id='.$admininfo['id']);
	}

}

foreach($LMS->GetAdminInfo($_GET['id']) as $key => $value)
	if(!isset($admininfo[$key]))
		$admininfo[$key] = $value;

$layout['pagetitle'] = trans('User Edit: $0', $admininfo['login']);

$rights = $LMS->GetAdminRights($_GET['id']);

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
$SMARTY->assign('admininfo',$admininfo);
$SMARTY->assign('unlockedit',TRUE);
$SMARTY->assign('error',$error);
$SMARTY->display('admininfo.html');

?>
