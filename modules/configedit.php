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

$error = array();
$action = ($_GET['action'] ?? ($_POST['action'] ?? null));

switch ($action) {
    case 'init':
        $SESSION->add_history_entry();
        break;
    case 'cancel':
        if ($SESSION->get_history_entry() != 'm=configlist') {
            $SESSION->remove_history_entry();
            $SESSION->redirect_to_history_entry();
        }
        break;
    case 'save':
            $SESSION->remove_history_entry();
        break;
}

if (isset($_GET['s']) && isset($_GET['v'])) {
    $params = array(
        'section' => $_GET['s'],
        'variable' => $_GET['v'],
    );
    if (isset($_GET['u'])) {
        $params['userid'] = $_GET['u'];
    }
    if (isset($_GET['d'])) {
        $params['divisionid'] = $_GET['d'];
    }
} elseif (isset($_GET['id'])) {
    $params['id'] = $_GET['id'];
} else {
    $SESSION->redirect_to_history_entry();
}

if (!($id = $LMS->ConfigOptionExists($params))) {
    $SESSION->redirect_to_history_entry();
}

if (isset($_GET['statuschange'])) {
    $LMS->toggleConfigOption($id);
    $SESSION->redirect_to_history_entry();
}

$config = $DB->GetRow('SELECT * FROM uiconfig WHERE id = ?', array($id));
$option = $config['section'] . '.' . $config['var'];
$config['type'] = ($config['type'] == CONFIG_TYPE_AUTO) ? $LMS->GetConfigDefaultType($option) : $config['type'];

$reftype = null;
$refconfigid = null;
$divisioninfo = null;
$userinfo = null;
if (!empty($config['configid'])) {
    if (!empty($config['divisionid']) && empty($config['userid'])) {
        $reftype = 'division';
        $refconfigid = $config['configid'];
        $divisioninfo = $LMS->GetDivision($config['divisionid']);
    } elseif (!empty($config['divisionid']) && !empty($config['userid'])) {
        $reftype = 'divisionuser';
        $refconfigid = $config['configid'];
        $parentOption = $LMS->getParentOption($config['id']);
        $divisionid = $parentOption['divisionid'];
        $divisioninfo = $LMS->GetDivision($divisionid);
        $userinfo = $LMS->GetUserInfo($config['userid']);
    } elseif (empty($config['divisionid']) && !empty($config['userid'])) {
        $reftype = 'user';
        $refconfigid = $config['configid'];
        $userinfo = $LMS->GetUserInfo($config['userid']);
    }
}

$relatedOptions = $LMS->getOptionHierarchy($config['id']);

if (isset($_POST['config'])) {
    $cfg = $_POST['config'];
    $cfg['id'] = $id;

    foreach ($cfg as $key => $val) {
        if ($key != 'wysiwyg') {
            $cfg[$key] = trim($val);
        }
    }

    if (!ConfigHelper::checkPrivilege('superuser')) {
        $cfg['type'] = $config['type'];
    }

    if ($cfg['var']=='') {
        $error['var'] = trans('Option name is required!');
    } elseif (strlen($cfg['var'])>64) {
        $error['var'] = trans('Option name is too long (max.64 characters)!');
    } elseif (!preg_match('/^[a-z0-9_-]+$/i', $cfg['var'])) {
        $error['var'] = trans('Option name contains forbidden characters!');
    }

    if (($cfg['var']!=$config['var'] || $cfg['section']!=$config['section'])
        && $LMS->ConfigOptionExists(array('section' => $cfg['section'], 'variable' => $cfg['var']))
            && empty($config['reftype'])
    ) {
        $error['var'] = trans('Option exists!');
    }

    if (!preg_match('/^[a-z0-9_-]+(-[a-z0-9_]+:[a-z0-9_-]+)?$/', $cfg['section']) && $cfg['section']!='') {
        $error['section'] = trans('Section name contains forbidden characters!');
    }

    $option = $cfg['section'] . '.' . $cfg['var'];
    if (empty($config['reftype'])) {
        if ($cfg['type'] == CONFIG_TYPE_AUTO) {
            $cfg['type'] = $LMS->GetConfigDefaultType($option);
        }
    }

    if ($msg = $LMS->CheckOption($option, $cfg['value'], $cfg['type'])) {
        $error['value'] = $msg;
    }

    if (!isset($cfg['disabled'])) {
        $cfg['disabled'] = 0;
    }

    if (!empty($cfg['reftype'])) {
        if (empty($cfg['refconfigid'])) {
            $error['refconfigid'] = trans('Referenced option does not exists!');
        }
        switch ($cfg['reftype']) {
            case 'division':
                if (empty($cfg['divisionid'])) {
                    $error['divisionid'] = trans('Division is required!');
                }
                $refOption = $LMS->GetConfigVariable($cfg['refconfigid']);
                if (!$refOption) {
                    $error['divisionid'] = trans('Referenced option does not exists!');
                }
                break;
            case 'divisionuser':
                if (empty($cfg['userid'])) {
                    $error['userid'] = trans('User is required!');
                }
                if (empty($cfg['divisionid'])) {
                    $error['divisionid'] = trans('Division is required!');
                }
                $refOption = $LMS->GetConfigVariable($cfg['refconfigid']);
                if (!$refOption) {
                    $error['divisionid'] = trans('Referenced option does not exists!');
                }
                $divisionaccess = $LMS->CheckDivisionsAccess(array('divisions' => $cfg['divisionid'], 'user_id' => $cfg['userid']));
                if (!$divisionaccess) {
                    $error['userid'] = trans('User is not assigned to the division!');
                }
                break;
            case 'user':
                if (empty($cfg['userid'])) {
                    $error['userid'] = trans('User is required!');
                }
                $refOption = $LMS->GetConfigVariable($cfg['refconfigid']);
                if (!$refOption) {
                    $error['userid'] = trans('Referenced option does not exists!');
                }
                break;
        }
    }

    if (!$error) {
        if (isset($_POST['richtext'])) {
            $cfg['type'] = CONFIG_TYPE_RICHTEXT;
        }

        if ($cfg['section'] != $config['section']
            || $cfg['var'] != $config['var']
            || $cfg['type'] != $config['type']
            || $cfg['value'] != $config['value']
            || $cfg['description'] != $config['description']
            || $cfg['disabled'] != $config['disabled']) {
            $args = array(
                'section' => ($cfg['section'] != $config['section'] ? $cfg['section'] : $config['section']),
                'var' => ($cfg['var'] != $config['var'] ? $cfg['var'] : $config['var']),
                'value' => ($cfg['value'] != $config['value'] ? $cfg['value'] : $config['value']),
                'description' => ($cfg['description'] != $config['description'] ? $cfg['description'] : $config['description']),
                'statuschange' => ($cfg['disabled'] != $config['disabled'] ? 1 : 0),
                'type' => ($cfg['type'] != $config['type'] ? $cfg['type'] : $config['type']),
                'id' => $cfg['id'],
                'relatedOptions' => $relatedOptions
            );

            $DB->BeginTrans();
            $configid = $LMS->editConfigOption($args);
            $DB->CommitTrans();

            if ($args['section'] == 'ksef' && $args['var'] == 'delay') {
                $ksef = new \Lms\KSeF\KSeF($DB, $LMS);
                $ksef->updateDelays();
            }
        }
        $SESSION->redirect_to_history_entry();
    }
    $config = $cfg;
}

if (!empty($reftype)) {
    switch ($reftype) {
        case 'division':
            $layout['pagetitle'] = trans('Division option edit: $a', $option);
            break;
        case 'divisionuser':
            $layout['pagetitle'] = trans('User in division option edit: $a', $option);
            break;
        case 'user':
            $layout['pagetitle'] = trans('User option edit: $a', $option);
            break;
    }
} else {
    $layout['pagetitle'] = trans('Global option edit: $a', $option);
}

$config['documentation'] = Utils::MarkdownToHtml(Utils::LoadMarkdownDocumentation($option));

$SMARTY->assign('reftype', $reftype);
$SMARTY->assign('refconfigid', $refconfigid);
$SMARTY->assign('divisioninfo', $divisioninfo);
$SMARTY->assign('userinfo', $userinfo);
$SMARTY->assign('sections', $LMS->GetConfigSections());
$SMARTY->assign('error', $error);
$SMARTY->assign('config', $config);
$SMARTY->assign('relatedoptions', $relatedOptions);
$SMARTY->display('config/configedit.html');
