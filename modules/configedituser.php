<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

$id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$id) {
    $SESSION->redirect('?'.$SESSION->get('backto'));
}

function ConfigOptionExists($confid)
{
    $DB = LMSDB::getInstance();
    return $DB->GetOne('SELECT id FROM uiconfig WHERE id = ?', array($confid));
}

$id = ConfigOptionExists($id);
if (empty($id)) {
    $SESSION->redirect('?'.$SESSION->get('backto'));
}

$config = $DB->GetRow('SELECT c.*, u.login, u.firstname, u.lastname 
    FROM uiconfig c
    LEFT JOIN users u on c.userid = u.id
    WHERE c.id = ?', array($id));
$option = $config['section'] . '.' . $config['var'];
$section = $config['section'];
$config['typetext'] = $CONFIG_TYPES[$config['type']];

$layout['pagetitle'] = trans('Edition config option \'$a\' for user \'$b\'', $option, $config['login']);

$userconfig = isset($_POST['userconfig']) ? $_POST['userconfig'] : array();

if (count($userconfig)) {
    foreach ($userconfig as $key => $val) {
        if ($key != 'wysiwyg') {
            $userconfig[$key] = trim($val);
        }
    }

    if ($msg = $LMS->CheckOption($option, $userconfig['value'], $config['type'])) {
        $error['value'] = $msg;
    }

    if (!isset($userconfig['disabled'])) {
        $userconfig['disabled'] = 0;
    }

    if (!$error) {
        $args = array(
            'value' => $userconfig['value'],
            'description' => $userconfig['description'],
            'disabled' => $userconfig['disabled'],
            'id' => $config['id']
        );
        $DB = LMSDB::getInstance();

        $DB->Execute(
            'UPDATE uiconfig SET value = ?, description = ?, disabled = ? WHERE id = ?',
            array_values($args)
        );

        if ($SYSLOG) {
            $SYSLOG->AddMessage(SYSLOG::RES_UICONF, SYSLOG::OPER_UPDATE, $args);
        }

        unset($userconfig['value']);
        unset($userconfig['description']);
        unset($userconfig['disabled']);

        $SESSION->redirect('?'.$SESSION->get('backto'));
    }
    $config = $userconfig;
}

$SMARTY->assign('error', $error);
$SMARTY->assign('config', $config);
$SMARTY->display('config/configedituser.html');
