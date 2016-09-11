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

if(isset($_POST['queue']))
{
	$queue = $_POST['queue'];

	if($queue['name']=='' && $queue['email']=='' && $queue['description']=='')
	{
		$SESSION->redirect('?m=rtqueuelist');
	}

	if($queue['name'] == '')
		$error['name'] = trans('Queue name must be defined!');

	if($queue['name'] != '' && $LMS->GetQueueIdByName($queue['name']))
		$error['name'] = trans('Queue with specified name already exists!');

	if($queue['email']!='' && !check_email($queue['email']))
		$error['email'] = trans('Incorrect email!');

	if(isset($queue['users']))
		foreach($queue['users'] as $key => $value)
			$queue['rights'][] = array('id' => $key, 'rights' => array_sum($value), 'name' => $queue['usernames'][$key]);

	if (!$error) {
		$DB->Execute('INSERT INTO rtqueues (name, email, description, newticketsubject, newticketbody,
				newmessagesubject, newmessagebody, resolveticketsubject, resolveticketbody)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
				array(trim($queue['name']), $queue['email'], $queue['description'],
					$queue['newticketsubject'], $queue['newticketbody'],
					$queue['newmessagesubject'], $queue['newmessagebody'],
					$queue['resolveticketsubject'], $queue['resolveticketbody']));

		$id = $DB->GetLastInsertId('rtqueues');

		if($queue['rights'] && $id)
			foreach($queue['rights'] as $right)
			        if($right['rights'])
					$DB->Execute('INSERT INTO rtrights(queueid, userid, rights) VALUES(?, ?, ?)', 
						array($id, $right['id'], $right['rights']));

		$SESSION->redirect('?m=rtqueueinfo&id='.$id);
	}
}

$users = $LMS->GetUserNames();

foreach($users as $user) 
{
	$user['rights'] = isset($queue['users'][$user['id']]) ? $queue['users'][$user['id']] : NULL;
	$queue['nrights'][] = $user;
}
$queue['rights'] = $queue['nrights'];

$layout['pagetitle'] = trans('New Queue');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('queue', $queue);
$SMARTY->assign('error', $error);
$SMARTY->display('rt/rtqueueadd.html');

?>
