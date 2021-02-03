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

if (isset($_POST['nodegroupadd'])) {
    $nodegroupadd = $_POST['nodegroupadd'];
    
    foreach ($nodegroupadd as $key => $value) {
        $nodegroupadd[$key] = trim($value);
    }

    if ($nodegroupadd['name']=='' && $nodegroupadd['description']=='') {
        $SESSION->redirect('?m=nodegrouplist');
    }
    
    if ($nodegroupadd['name'] == '') {
        $error['name'] = trans('Group name required!');
    } elseif (strlen($nodegroupadd['name']) > 255) {
        $error['name'] = trans('Group name is too long!');
    } elseif (!preg_match('/^[._a-z0-9-]+$/i', $nodegroupadd['name'])) {
        $error['name'] = trans('Invalid chars in group name!');
    } elseif ($DB->GetOne('SELECT 1 FROM nodegroups WHERE name = ?', array($nodegroupadd['name']))) {
        $error['name'] = trans('Group with name $a already exists!', $nodegroupadd['name']);
    }

    if (!$error) {
        $prio = $DB->GetOne('SELECT MAX(prio)+1 FROM nodegroups');
        $args = array(
            'name' => $nodegroupadd['name'],
            'description' => $nodegroupadd['description'],
            'prio' => ($prio != null ? $prio : 1)
        );
        $DB->Execute('INSERT INTO nodegroups (name, description, prio)
				VALUES (?, ?, ?)', array_values($args));
        $id = $DB->GetLastInsertID('nodegroups');
        if ($SYSLOG) {
            $args[SYSLOG::RES_NODEGROUP] = $id;
            $SYSLOG->AddMessage(SYSLOG::RES_NODEGROUP, SYSLOG::OPER_ADD, $args);
        }

        if (isset($nodegroupadd['reuse'])) {
            unset($nodegroupadd);
            $nodegroupadd['reuse'] = 1;
            $SMARTY->assign('nodegroupadd', $nodegroupadd);
            $SMARTY->display('node/nodegroupadd.html');
        }

        $SESSION->redirect('?m=nodegrouplist&id='.$id);
    }
    
    $SMARTY->assign('error', $error);
    $SMARTY->assign('nodegroupadd', $nodegroupadd);
}

$layout['pagetitle'] = trans('New Group');

$SMARTY->display('node/nodegroupadd.html');
