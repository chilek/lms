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

$adminadd = $_POST['adminadd'];
$acl = $_POST['acl'];

if(isset($adminadd))
{
	foreach($adminadd as $key => $value)
		$adminadd[$key] = trim($value);
	
	if($adminadd['login']=='' && $adminadd['name']=='' && $adminadd['password']=='' && $adminadd['confirm']=='')
	{
		header('Location: ?m=adminadd');
		die;
	}
	
	if($LMS->GetAdminIDByLogin($adminadd['login']))
		$error['login'] = trans('Account with specified login exists!');
	elseif(!eregi("^[a-z0-9.-_]+$",$adminadd['login']))
		$error['login'] = trans('Login contains forbidden characters!');

	if($adminadd['email']!='' && !check_email($adminadd['email']))
		$error['email'] = trans('E-mail isn\'t correct!');

	if($adminadd['name']=='')
		$error['name'] = trans('You must enter name and surname!');

	if($adminadd['password']=='')
		$error['password'] = trans('Empty password not allowed!');
	elseif($adminadd['password']!=$adminadd['confirm'])
		$error['password'] = trans('Passwords do not match!');

	// zróbmy maskê ACL...

	for($i=0;$i<256;$i++)
		$mask .= '0';

	foreach($access['table'] as $idx => $row)
		if($acl[$idx]=='1')
			$mask[255-$idx] = '1';

	for($i=0;$i<256;$i += 4)
		$outmask = $outmask . dechex(bindec(substr($mask,$i,4)));

	$adminadd['rights'] = ereg_replace('^[0]*(.*)$','\1',$outmask);

	if(!$error)
	{
		header('Location: ?m=admininfo&id='.$LMS->AdminAdd($adminadd));
		die;
	}
}
foreach($access['table'] as $idx => $row)
{
	$row['id'] = $idx;
	if($acl[$idx] == '1')
		$row['enabled'] = TRUE;
	$accesslist[] = $row;
}

$layout['pagetitle'] = trans('New User');
$SMARTY->assign('adminadd', $adminadd);
$SMARTY->assign('error', $error);
$SMARTY->assign('accesslist', $accesslist);
$SMARTY->display('adminadd.html');

?>
