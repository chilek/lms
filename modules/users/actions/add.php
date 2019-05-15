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

$useradd = isset($_POST['useradd']) ? $_POST['useradd'] : array();

if (count($useradd)) {
    foreach ($useradd as $key => $value) {
        $useradd[$key] = trim($value);
    }
    
    if ($useradd['login']=='' && $useradd['name']=='' && $useradd['password']=='' && $useradd['confirm']=='') {
        $SESSION->redirect('?m=users&a=add');
    }
    
    if ($useradd['login']=='') {
        $error['login'] = trans('Login can\'t be empty!');
    } elseif (!eregi('^[a-z0-9.-_]+$', $useradd['login'])) {
        $error['login'] = trans('Login contains forbidden characters!');
    } elseif ($LMS->GetUserIDByLogin($useradd['login'])) {
        $error['login'] = trans('User with specified login exists or that login was used in the past!');
    }
    
    if ($useradd['email']!='' && !check_email($useradd['email'])) {
        $error['email'] = trans('E-mail isn\'t correct!');
    }

    if ($useradd['name']=='') {
        $error['name'] = trans('You have to enter first and lastname!');
    }

    if ($useradd['password']=='') {
        $error['password'] = trans('Empty passwords are not allowed!');
    } elseif ($useradd['password']!=$useradd['confirm']) {
        $error['password'] = trans('Passwords does not match!');
    }

    $useradd['rights'] = '';
    
    if (!$error) {
        $id = $LMS->UserAdd($useradd);
        
        $REDIRECT = '?m=users&a=info&id='.$id;
        return;
    }
}

$layout['pagetitle'] = trans('New User');

$SMARTY->assign('useradd', $useradd);
$SMARTY->assign('error', $error);
