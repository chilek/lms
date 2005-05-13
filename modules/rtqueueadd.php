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

$queue = $_POST['queue'];

if(isset($queue))
{
	if($queue['name']=='' && $queue['email']=='' && $queue['description']=='')
	{
		$SESSION->redirect('?m=rtqueuelist');
	}

	if($queue['name'] == '')
		$error['name'] = trans('Queue must have its name!');

	if($queue['name'] != '' && $LMS->GetQueueIdByName($queue['name']))
		$error['name'] = trans('Queue with specified name already exists!');

	if($queue['email']!='' && !check_email($queue['email']))
		$error['email'] = trans('Incorrect email!');

	if(isset($queue['admins']))
		foreach($queue['admins'] as $key => $value)
			$queue['rights'][] = array('id' => $key, 'rights' => $value, 'name' => $queue['adminnames'][$key]);

	if(!$error)
	{
		$id = $LMS->QueueAdd($queue);
		$SESSION->redirect('?m=rtqueueinfo&id='.$id);
	}
}

$admins = $LMS->GetAdminNames();

foreach($admins as $admin) 
{
	$admin['rights'] = $queue['admins'][$admin['id']];
	$queue['nrights'][] = $admin;
}
$queue['rights'] = $queue['nrights'];

$layout['pagetitle'] = trans('New Queue');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('queue', $queue);
$SMARTY->assign('error', $error);
$SMARTY->display('rtqueueadd.html');

?>
