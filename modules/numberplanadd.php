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
    die(json_encode(getUsers($LMS->GetDivisions(), array_flip($_POST['divisions']))));
}

$numberplanadd = $_POST['numberplanadd'] ?? null;

if (!empty($numberplanadd) && count($numberplanadd)) {
    $numberplanadd['template'] = trim($numberplanadd['template']);

    if ($numberplanadd['template'] == '') {
        $error['template'] = trans('Number template is required!');
    } elseif (!preg_match('/%[1-9]{0,1}N/', $numberplanadd['template'])
        && !preg_match('/%[1-9]{0,1}C/', $numberplanadd['template'])) {
        $error['template'] = trans('Template must contain "%N" or "%C" specifier!');
    }

    if ($numberplanadd['doctype'] == 0) {
        $error['doctype'] = trans('Document type is required!');
    }

    if ($numberplanadd['period'] == 0) {
        $error['period'] = trans('Numbering period is required!');
    }

    $result = $LMS->validateNumberPlan($numberplanadd);
    $error = array_merge($error ?: array(), $result);

    if (!$error) {
        $LMS->addNumberPlan($numberplanadd);

        if (!isset($numberplanadd['reuse'])) {
            $SESSION->redirect('?m=numberplanlist');
        }

        unset($numberplanadd['template']);
        unset($numberplanadd['period']);
        unset($numberplanadd['doctype']);
        unset($numberplanadd['isdefault']);
        $numberplanadd['divisions'] = array();
    } else {
        $numberplanadd['divisions'] = array_flip($numberplanadd['divisions'] ?: array());
        $numberplanadd['users'] = array_flip(empty($numberplanadd['users']) ? array() : $numberplanadd['users']);
    }
}

$layout['pagetitle'] = trans('New Numbering Plan');

$SESSION->add_history_entry();

$divisions = $LMS->GetDivisions(array('status' => 0));
$users = getUsers($divisions, $numberplanadd['divisions'] ?? array());

$SMARTY->assign('numberplanadd', $numberplanadd);
$SMARTY->assign('divisions', $divisions);
$SMARTY->assign('users', $users);
$SMARTY->assign('error', $error);
$SMARTY->display('numberplan/numberplanadd.html');
