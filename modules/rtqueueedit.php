<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

if (! $LMS->QueueExists($_GET['id'])) {
    $SESSION->redirect('?m=rtqueuelist');
}

if (isset($_GET['unread'])) {
    $LMS->MarkQueueAsRead($_GET['id']);
    $SESSION->redirect('?' . $SESSION->get('backto'));
}

if (isset($_POST['queue'])) {
    $queue = $_POST['queue'];

    $queue['id'] = $_GET['id'];
    
    if ($queue['name'] == '') {
        $error['name'] = trans('Queue name must be defined!');
    }

    if ($queue['email']!='' && !check_email($queue['email'])) {
        $error['email'] = trans('Incorrect email!');
    }

    if (isset($queue['users'])) {
        foreach ($queue['users'] as $key => $value) {
            $queue['rights'][] = array('id' => $key, 'rights' => array_sum($value), 'name' => $queue['usernames'][$key]);
        }
    }

    if ($queue['newticketsubject'] && !$queue['newticketbody']) {
        $error['newticketbody'] = trans('New ticket body should not be empty if you set new ticket subject!');
    } elseif (!$queue['newticketsubject'] && $queue['newticketbody']) {
        $error['newticketsubject'] = trans('New ticket subject should not be empty if you set new ticket body!');
    }

    if ($queue['newmessagesubject'] && !$queue['newmessagebody']) {
        $error['newmessagebody'] = trans('New message body should not be empty if you set new message subject!');
    } elseif (!$queue['newmessagesubject'] && $queue['newmessagebody']) {
        $error['newmessagesubject'] = trans('New message subject should not be empty if you set new message body!');
    }

    if ($queue['resolveticketsubject'] && !$queue['resolveticketbody']) {
        $error['resolveticketbody'] = trans('Resolve ticket body should not be empty if you set resolve ticket subject!');
    } elseif (!$queue['resolveticketsubject'] && $queue['resolveticketbody']) {
        $error['resolveticketsubject'] = trans('Resolve ticket subject should not be empty if you set resolve ticket body!');
    }

    $categories = $LMS->GetUserCategories(Auth::GetCurrentUser());
    if (isset($queue['categories'])) {
        foreach ($categories as &$category) {
            if (isset($queue['categories'][$category['id']])) {
                $category['checked'] = 1;
            }
        }
        unset($category);
    }

    if (!$error) {
        $DB->Execute(
            'UPDATE rtqueues SET name=?, email=?, description=?,
				newticketsubject=?, newticketbody=?,
				newmessagesubject=?, newmessagebody=?,
				resolveticketsubject=?, resolveticketbody=?, verifierticketsubject=?, verifierticketbody=?, verifierid=? WHERE id=?',
            array(trim($queue['name']),
                    $queue['email'], $queue['description'],
                    $queue['newticketsubject'], $queue['newticketbody'],
                    $queue['newmessagesubject'], $queue['newmessagebody'],
                    $queue['resolveticketsubject'], $queue['resolveticketbody'], $queue['verifierticketsubject'], $queue['verifierticketbody'],
                    !empty($queue['verifierid']) ? $queue['verifierid'] : null,
                    $queue['id'])
        );

        $DB->Execute('DELETE FROM rtrights WHERE queueid=?', array($queue['id']));
        
        if ($queue['rights']) {
            foreach ($queue['rights'] as $right) {
                if ($right['rights']) {
                    $DB->Execute(
                        'INSERT INTO rtrights(queueid, userid, rights) VALUES(?, ?, ?)',
                        array($queue['id'], $right['id'], $right['rights'])
                    );
                }
            }
        }

        foreach ($categories as $category) {
            if ($category['checked']) {
                if (!$DB->GetOne(
                    'SELECT id FROM rtqueuecategories WHERE queueid = ? AND categoryid = ?',
                    array($queue['id'], $category['id'])
                )) {
                    $DB->Execute(
                        'INSERT INTO rtqueuecategories (queueid, categoryid) VALUES (?, ?)',
                        array($queue['id'], $category['id'])
                    );
                }
            } else {
                $DB->Execute(
                    'DELETE FROM rtqueuecategories WHERE queueid = ? AND categoryid = ?',
                    array($queue['id'], $category['id'])
                );
            }
        }

        $SESSION->redirect('?m=rtqueueinfo&id='.$queue['id']);
    }
} else {
    $queue = $LMS->GetQueue($_GET['id']);
    $categories = $LMS->GetUserCategories(Auth::GetCurrentUser());
    $queuecategories = $LMS->GetQueueCategories($_GET['id']);
    if (empty($categories)) {
        $categories = array();
    }
    foreach ($categories as &$category) {
        if (isset($queuecategories[$category['id']])) {
            $category['checked'] = 1;
        }
    }
    unset($category);
}

$userlist = $LMS->getUserList();
unset($userlist['total']);

$layout['pagetitle'] = trans('Queue Edit: $a', $queue['name']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('queue', $queue);
$SMARTY->assign('userlist', $userlist);
$SMARTY->assign('categories', $categories);
$SMARTY->assign('error', $error);
$SMARTY->display('rt/rtqueueedit.html');
