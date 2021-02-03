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

if (!$LMS->UserExists($_GET['id'])) {
    $SESSION->redirect('?m=users&a=list');
}

$userinfo = isset($_POST['userinfo']) ? $_POST['userinfo'] : false;

if ($userinfo) {
    $userinfo['id'] = $_GET['id'];
    
    foreach ($userinfo as $key => $value) {
        $userinfo[$key] = trim($value);
    }

    if ($userinfo['login'] == '') {
        $error['login'] = trans('Login can\'t be empty!');
    } elseif (!eregi('^[a-z0-9.-_]+$', $userinfo['login'])) {
        $error['login'] = trans('Login contains forbidden characters!');
    } elseif ($LMS->GetUserIDByLogin($userinfo['login']) && $LMS->GetUserIDByLogin($userinfo['login']) != $_GET['id']) {
        $error['login'] = trans('User with specified login exists or that login was used in the past!');
    }

    if ($userinfo['name'] == '') {
        $error['name'] = trans('You have to enter first and lastname!');
    }

    if ($userinfo['email']!='' && !check_email($userinfo['email'])) {
        $error['email'] = trans('E-mail isn\'t correct!');
    }
                
    $userinfo['rights'] = '';

    if (!$error) {
        $LMS->UserUpdate($userinfo);

        $REDIRECT = '?m=users&a=info&id='.$userinfo['id'];
                return;
    }
}

foreach ($LMS->GetUserInfo($_GET['id']) as $key => $value) {
    if (!isset($userinfo[$key])) {
        $userinfo[$key] = $value;
    }
}

$layout['pagetitle'] = trans('User Edit: $a', $userinfo['login']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('userinfo', $userinfo);
$SMARTY->assign('unlockedit', true);
$SMARTY->assign('error', $error);
