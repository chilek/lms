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
$instance = $DB->GetRow('SELECT id, name, hostid, description, module, crontab, priority, disabled FROM daemoninstances WHERE id=?', array($id));

$layout['pagetitle'] = trans('Instance Edit: $a', $instance['name']);

if (isset($_POST['instance'])) {
    $instedit = $_POST['instance'];
    foreach ($instedit as $idx => $key) {
        $instedit[$idx] = trim($key);
    }

    $instedit['id'] = $instance['id'];
    
    if ($instedit['name'] == '') {
        $error['name'] = trans('Instance name is required!');
    } elseif ($instedit['name']!=$instance['name']) {
        if ($DB->GetOne('SELECT id FROM daemoninstances WHERE name=? AND hostid=?', array($instedit['name'], $instedit['hostid']))) {
            $error['name'] = trans('Instance with specified name exists on that host!');
        }
    }
    
    if ($instedit['module'] == '') {
        $error['module'] = trans('Instance module is required!');
    }
        
    if (!$instedit['hostid']) {
        $error['hostid'] = trans('Instance host is required!');
    }
    
    if ($instedit['crontab'] != '' && !preg_match('/^[0-9\/\*,-]+[ \t][0-9\/\*,-]+[ \t][0-9\/\*,-]+[ \t][0-9\/\*,-]+[ \t][0-9\/\*,-]+$/', $instedit['crontab'])) {
        $error['crontab'] = trans('Incorrect crontab format!');
    }
    
    if (!isset($instedit['disabled'])) {
        $instedit['disabled'] = 0;
    }

    if ($instedit['priority'] == '') {
        $instedit['priority'] = 0;
    } elseif (!is_numeric($instedit['priority'])) {
        $error['priority'] = trans('Priority must be integer!');
    }

    if (!$error) {
        $args = array(
            'name' => $instedit['name'],
            SYSLOG::RES_HOST => $instedit['hostid'],
            'description' => $instedit['description'],
            'module' => $instedit['module'],
            'crontab' => $instedit['crontab'],
            'priority' => $instedit['priority'],
            'disabled' => $instedit['disabled'],
            SYSLOG::RES_DAEMONINST => $instedit['id']
        );
        $DB->Execute(
            'UPDATE daemoninstances SET name=?, hostid=?, description=?, module=?, crontab=?, priority=?, disabled=? WHERE id=?',
            array_values($args)
        );
        if ($SYSLOG) {
            $SYSLOG->AddMessage(SYSLOG::RES_DAEMONINST, SYSLOG::OPER_UPDATE, $args);
        }

        $SESSION->redirect('?m=daemoninstancelist');
    }
} elseif (isset($_GET['statuschange'])) {
    if ($SYSLOG) {
        $args = array(
            SYSLOG::RES_DAEMONINST => $id,
            SYSLOG::RES_HOST => $instance['hostid'],
            'disabled' => $instance['disabled'] ? 0 : 1
        );
        $SYSLOG->AddMessage(SYSLOG::RES_DAEMONINST, SYSLOG::OPER_UPDATE, $args);
    }
    if ($instance['disabled']) {
        $DB->Execute('UPDATE daemoninstances SET disabled=0 WHERE id=?', array($id));
    } else {
        $DB->Execute('UPDATE daemoninstances SET disabled=1 WHERE id=?', array($id));
    }
    $SESSION->redirect('?m=daemoninstancelist');
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('error', $error);
$SMARTY->assign('instance', isset($instedit) ? $instedit : $instance);
$SMARTY->assign('hosts', $DB->GetAll('SELECT id, name FROM hosts ORDER BY name'));
$SMARTY->display('daemon/daemoninstanceedit.html');
