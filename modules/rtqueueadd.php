<?php

/*
 * LMS version 1.4-cvs
 *
 *  (C) Copyright 2001-2004 LMS Developers
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

$queue = $_POST['queue'];

if(isset($queue))
{
	if($queue['name']=='' && $queue['email']=='' && $queue['description']=='')
	{
		header('Location: ?m=rtqueuelist');
		die;
	}

	if($queue['name'] == '')
		$error['name'] = 'Kolejka musi posiadaæ nazwê!';

	if($queue['name'] != '' && $LMS->GetQueueIdByName($queue['name']))
		$error['name'] = 'Kolejka o podanej nazwie jest ju¿ w bazie!';

	if($queue['email']!='' && !check_email($queue['email']))
		$error['email'] = 'Podany email nie wydaje siê byæ poprawny!';

	if(isset($queue['admins']))
		foreach($queue['admins'] as $key => $value)
			$queue['rights'][] = array('id' => $key, 'rights' => $value, 'name' => $queue['adminnames'][$key]);

	if(!$error)
	{
		$id = $LMS->QueueAdd($queue);
		header("Location: ?m=rtqueueinfo&id=".$id);
		die;
	}
}

$admins = $LMS->GetAdminNames();

foreach($admins as $admin) 
{
	$admin['rights'] = $queue['admins'][$admin['id']];
	$queue['nrights'][] = $admin;
}
$queue['rights'] = $queue['nrights'];

$layout['pagetitle'] = 'Nowa kolejka';

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

$SMARTY->assign('queue', $queue);
$SMARTY->assign('error', $error);
$SMARTY->display('rtqueueadd.html');

?>
