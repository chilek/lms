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

$layout['pagetitle'] = trans('New Config Option');

$config = isset($_POST['config']) ? $_POST['config'] : array();

if (count($config)) {
    foreach ($config as $key => $val) {
        if ($key != 'wysiwyg') {
            $config[$key] = trim($val);
        }
    }

    if (!($config['var'] || $config['value'] || $config['description'])) {
        $SESSION->redirect('?m=configlist');
    }

    if ($config['var']=='') {
        $error['var'] = trans('Option name is required!');
    } elseif (strlen($config['var'])>64) {
        $error['var'] = trans('Option name is too long (max.64 characters)!');
    } elseif (!preg_match('/^[a-z0-9_-]+$/', $config['var'])) {
        $error['var'] = trans('Option name contains forbidden characters!');
    } elseif ($LMS->ConfigOptionExists(array('section' => $config['section'], 'variable' => $config['var']))) {
        $error['var'] = trans('Option exists!');
    }

    $section = $config['section'];
    if (empty($section)) {
        $error['section'] = trans('Section name can\'t be empty!');
    } elseif (!preg_match('/^[a-z0-9_-]+$/', $section)) {
        $error['section'] = trans('Section name contains forbidden characters!');
    }

    $option = $config['section'] . '.' . $config['var'];
    if (!ConfigHelper::checkPrivilege('superuser') || $config['type'] == CONFIG_TYPE_AUTO) {
        $config['type'] = $LMS->GetConfigDefaultType($option);
    }

    if ($msg = $LMS->CheckOption($option, $config['value'], $config['type'])) {
        $error['value'] = $msg;
    }

    if (!isset($config['disabled'])) {
        $config['disabled'] = 0;
    }

    if (!$error) {
        $args = array(
            'section' => $section,
            'var' => $config['var'],
            'value' => $config['value'],
            'description' => $config['description'],
            'disabled' => $config['disabled'],
            'type' => $config['type']
        );
        $DB->Execute(
            'INSERT INTO uiconfig (section, var, value, description, disabled, type) VALUES (?, ?, ?, ?, ?, ?)',
            array_values($args)
        );

        if ($SYSLOG) {
            $args[SYSLOG::RES_UICONF] = $DB->GetLastInsertID('uiconfig');
            $SYSLOG->AddMessage(SYSLOG::RES_UICONF, SYSLOG::OPER_ADD, $args);
        }

        if (!isset($config['reuse'])) {
            $SESSION->redirect('?m=configlist');
        }

        unset($config['var']);
        unset($config['value']);
        unset($config['description']);
        unset($config['disabled']);
    }

    $config['documentation'] = Utils::MarkdownToHtml(Utils::LoadMarkdownDocumentation($option));
} elseif (isset($_GET['id'])) {
    $config = $LMS->GetConfigVariable($_GET['id']);
    unset($config['id']);
    $config['section'] = trans('$a-clone', $config['section']);
} elseif (isset($_GET['section']) && isset($_GET['new-section'])) {
    if (!preg_match('/^[a-z0-9_-]+$/', $_GET['section'])
        || !preg_match('/^[a-z0-9_-]+$/', $_GET['new-section'])) {
        die;
    }
    $LMS->CloneConfigSection($_GET['section'], $_GET['new-section'], isset($_GET['userid']) ? intval($_GET['userid']) : null);
    $SESSION->redirect('?m=configlist');
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if (isset($_GET['section'])) {
    $config['section'] = $_GET['section'];
}

$SMARTY->assign('sections', $LMS->GetConfigSections());
$SMARTY->assign('error', $error);
$SMARTY->assign('config', $config);
$SMARTY->display('config/configadd.html');
