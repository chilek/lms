<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

$id = intval($_GET['id']);

$oldinv = $LMS->GetProject($id);

if (!empty($_POST['invprojectedit'])) {
    $invproject = $_POST['invprojectedit'];
    $invproject['id'] = $oldinv['id'];
            
    if ($invproject['name']=='') {
        $error['name'] = trans('Investment project name is required!');
    } elseif ($oldinv['name'] != $invproject['name'] && $LMS->ProjectByNameExists($invproject['name'])) {
        $error['name'] = trans('Investment project with specified name already exists!');
    }

    if (!$error) {
        $LMS->UpdateProject($invproject['id'], array(
            'projectname' => $invproject['name'],
            'divisionid' => $invproject['divisionid'],
        ));
        $SESSION->redirect('?m=invprojectlist');
    }
}

$layout['pagetitle'] = trans('Edit investment project: $a', $oldinv['name']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);
$SMARTY->assign('invprojectedit', !empty($invproject) ? $invproject : $oldinv);
$SMARTY->assign('divisions', $LMS->GetDivisions());
$SMARTY->assign('error', $error);
$SMARTY->display('invproject/invprojectedit.html');
