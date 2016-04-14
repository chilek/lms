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

if(! $LMS->QueueExists($_GET['id']))
{
	$SESSION->redirect('?m=rtqueuelist');
}

if(isset($_POST['queue']))
{
	$queue = $_POST['queue'];

	$queue['id'] = $_GET['id'];
	
	if($queue['name'] == '')
		$error['name'] = trans('Queue name must be defined!');

	if($queue['email']!='' && !check_email($queue['email']))
		$error['email'] = trans('Incorrect email!');

	if(isset($queue['users']))
		foreach($queue['users'] as $key => $value)
			$queue['rights'][] = array('id' => $key, 'rights' => array_sum($value), 'name' => $queue['usernames'][$key]);

	if (!$error) {
		$DB->Execute('UPDATE rtqueues SET name=?, email=?, description=?,
				newticketsubject=?, newticketbody=?,
				newmessagesubject=?, newmessagebody=?,
				resolveticketsubject=?, resolveticketbody=? WHERE id=?',
				array(trim($queue['name']),
					$queue['email'], $queue['description'],
					$queue['newticketsubject'], $queue['newticketbody'],
					$queue['newmessagesubject'], $queue['newmessagebody'],
					$queue['resolveticketsubject'], $queue['resolveticketbody'],
					$queue['id']));

		$DB->Execute('DELETE FROM rtrights WHERE queueid=?', array($queue['id']));
		
		if($queue['rights'])
		        foreach($queue['rights'] as $right)
		                if($right['rights'])
					$DB->Execute('INSERT INTO rtrights(queueid, userid, rights) VALUES(?, ?, ?)',
				                array($queue['id'], $right['id'], $right['rights']));

		$SESSION->redirect('?m=rtqueueinfo&id='.$queue['id']);
	}
}
else
	$queue = $LMS->GetQueue($_GET['id']);

$layout['pagetitle'] = trans('Queue Edit: $a', $queue['name']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('queue', $queue);
$SMARTY->assign('error', $error);
$SMARTY->display('rt/rtqueueedit.html');

?>
