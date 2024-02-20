<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

function getUsers($alldivisions, $selecteddivisions)
{
    $LMS = LMS::getInstance();

    if (empty($selecteddivisions)) {
        $divisions = $alldivisions;
    } else {
        $divisions = array_filter(
            $alldivisions,
            function ($division) use ($selecteddivisions) {
                return isset($selecteddivisions[$division['id']]);
            }
        );
    }

    $users = $LMS->GetUsers(array(
        'divisions' => implode(',', array_keys($divisions)),
        'order' => 'rname,asc',
    ));
    if (empty($users)) {
        $users = array();
    }

    return $users;
}

if (isset($_GET['op']) && $_GET['op'] == 'updateusers') {
    header('Content-Type: application/json');
    die(json_encode(getUsers(
        $LMS->GetDivisions(),
        empty($_POST['divisions']) ? array() : array_flip($_POST['divisions'])
    )));
}

$numberplan = $LMS->getNumberPlan($_GET['id']);
if (empty($numberplan)) {
    access_denied();
}

$template = $numberplan['template'];

$numberplanedit = $_POST['numberplanedit'] ?? null;

if (is_array($numberplanedit) && count($numberplanedit)) {
    $numberplanedit['template'] = trim($numberplanedit['template']);
    $numberplanedit['id'] = $numberplan['id'];

    if ($numberplanedit['template'] == '') {
        $error['template'] = trans('Number template is required!');
    } elseif (!preg_match('/%[1-9]{0,1}N/', $numberplanedit['template'])
        && !preg_match('/%[1-9]{0,1}C/', $numberplanedit['template'])) {
        $error['template'] = trans('Template must contain "%N" or "%C" specifier!');
    }

    if (!isset($numberplanedit['isdefault'])) {
        $numberplanedit['isdefault'] = 0;
    }

    if ($numberplanedit['doctype'] == 0) {
        $error['doctype'] = trans('Document type is required!');
    }

    if ($numberplanedit['period'] == 0) {
        $error['period'] = trans('Numbering period is required!');
    }

    $result = $LMS->validateNumberPlan($numberplanedit);
    $error = array_merge($error ?: array(), $result);

    if (!$error) {
        $LMS->updateNumberPlan($numberplanedit);

        $SESSION->redirect('?m=numberplanlist');
    } else {
        $numberplanedit['divisions'] = array_flip($numberplanedit['divisions'] ?: array());
        $numberplanedit['users'] = array_flip($numberplanedit['users'] ?: array());
    }
    $numberplan = $numberplanedit;
}

$layout['pagetitle'] = trans('Numbering Plan Edit: $a', $template);

$SESSION->add_history_entry();

$divisions = $LMS->GetDivisions();
$users = getUsers($divisions, $numberplan['divisions']);

$SMARTY->assign('numberplanedit', $numberplan);
$SMARTY->assign('divisions', $divisions);
$SMARTY->assign('users', $users);
$SMARTY->assign('error', $error);
$SMARTY->display('numberplan/numberplanedit.html');
