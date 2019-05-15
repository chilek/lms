<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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
$userlist = $LMS->getUserList();
unset($userlist['total']);

if (isset($_POST['queue'])) {
    $queue = $_POST['queue'];

    if ($queue['name']=='' && $queue['email']=='' && $queue['description']=='') {
        $SESSION->redirect('?m=rtqueuelist');
    }

    if ($queue['name'] == '') {
        $error['name'] = trans('Queue name must be defined!');
    }

    if ($queue['name'] != '' && $LMS->GetQueueIdByName($queue['name'])) {
        $error['name'] = trans('Queue with specified name already exists!');
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

    if ($queue['verifierticketsubject'] && !$queue['verifierticketbody']) {
        $error['verifierticketbody'] = trans('Verifier ticket body should not be empty if you set verifier ticket subject!');
    } elseif (!$queue['verifierticketsubject'] && $queue['verifierticketbody']) {
        $error['verifierticketsubject'] = trans('Verifier ticket subject should not be empty if you set verifier ticket body!');
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
            'INSERT INTO rtqueues (name, email, description, newticketsubject, newticketbody,
				newmessagesubject, newmessagebody, resolveticketsubject, resolveticketbody, verifierticketsubject, verifierticketbody, verifierid)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            array(trim($queue['name']), $queue['email'], $queue['description'],
                    $queue['newticketsubject'], $queue['newticketbody'],
                    $queue['newmessagesubject'], $queue['newmessagebody'],
                    $queue['resolveticketsubject'], $queue['resolveticketbody'], $queue['verifierticketsubject'],
                    $queue['verifierticketbody'],
            !empty($queue['verifierid']) ? $queue['verifierid'] : null )
        );

        $id = $DB->GetLastInsertId('rtqueues');

        if ($queue['rights'] && $id) {
            foreach ($queue['rights'] as $right) {
                if ($right['rights']) {
                    $DB->Execute(
                        'INSERT INTO rtrights(queueid, userid, rights) VALUES(?, ?, ?)',
                        array($id, $right['id'], $right['rights'])
                    );
                }
            }
        }

        foreach ($categories as $category) {
            if ($category['checked']) {
                $DB->Execute(
                    'INSERT INTO rtqueuecategories (queueid, categoryid) VALUES (?, ?)',
                    array($id, $category['id'])
                );
            }
        }

        $SESSION->redirect('?m=rtqueueinfo&id='.$id);
    }
} else {
    $categories = $LMS->GetUserCategories(Auth::GetCurrentUser());
}

$users = $LMS->GetUserNames();

foreach ($users as $user) {
    $user['rights'] = isset($queue['users'][$user['id']]) ? $queue['users'][$user['id']] : null;
    $queue['nrights'][] = $user;
}
$queue['rights'] = $queue['nrights'];

$layout['pagetitle'] = trans('New Queue');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('queue', $queue);
$SMARTY->assign('categories', $categories);
$SMARTY->assign('userlist', $userlist);
$SMARTY->assign('error', $error);
$SMARTY->display('rt/rtqueueadd.html');
