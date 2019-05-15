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

if (isset($_POST['customergroupadd'])) {
    $customergroupadd = $_POST['customergroupadd'];
    
    foreach ($customergroupadd as $key => $value) {
        $customergroupadd[$key] = trim($value);
    }

    if ($customergroupadd['name']=='' && $customergroupadd['description']=='') {
        $SESSION->redirect('?m=customergrouplist');
    }

    if ($customergroupadd['name'] == '') {
        $error['name'] = trans('Group name required!');
    } elseif (strlen($customergroupadd['name']) > 255) {
        $error['name'] = trans('Group name is too long!');
    } elseif (!preg_match('/^[._a-z0-9-]+$/i', $customergroupadd['name'])) {
        $error['name'] = trans('Invalid chars in group name!');
    } elseif ($LMS->CustomergroupGetId($customergroupadd['name'])) {
        $error['name'] = trans('Group with name $a already exists!', $customergroupadd['name']);
    }

    if (!$error) {
        $SESSION->redirect('?m=customergrouplist&id='.$LMS->CustomergroupAdd($customergroupadd));
    }

    $SMARTY->assign('error', $error);
    $SMARTY->assign('customergroupadd', $customergroupadd);
}

$layout['pagetitle'] = trans('New Group');

$SMARTY->display('customer/customergroupadd.html');
