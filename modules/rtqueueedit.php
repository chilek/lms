<?php

/*
 * LMS version 1.5-cvs
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

if(! $LMS->QueueExists($_GET['id']))
{
	header('Location: ?m=rtqueuelist');
	die;
}

$queue = $_POST['queue'];

if(isset($queue))
{
	$queue['id'] = $_GET['id'];
	
	if($queue['name'] == '')
		$error['name'] = "Kolejka musi posiadaæ nazwê!";

	if($queue['email']!='' && !check_email($queue['email']))
		$error['email'] = 'Podany email nie wydaje siê byæ poprawny!';

	if(isset($queue['admins']))
		foreach($queue['admins'] as $key => $value)
			$queue['rights'][] = array('id' => $key, 'rights' => $value, 'name' => $queue['adminnames'][$key]);

	if(!$error)
	{
		$LMS->QueueUpdate($queue);
		header("Location: ?m=rtqueueinfo&id=".$queue['id']);
		die;
	}
}
else
	$queue = $LMS->GetQueue($_GET['id']);

$layout['pagetitle'] = 'Edycja kolejki: '.$queue['name'];

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

$SMARTY->assign('queue', $queue);
$SMARTY->assign('error', $error);
$SMARTY->display('rtqueueedit.html');

?>
