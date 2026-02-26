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

$reftype = isset($_GET['reftype']) && ($_GET['reftype'] === 'division' || $_GET['reftype'] === 'divisionuser' || $_GET['reftype'] === 'user')  ? $_GET['reftype'] : null;
$refconfigid = isset($_GET['refconfigid']) ? intval($_GET['refconfigid']) : null;
$divisionid = isset($_GET['divisionid']) ? intval($_GET['divisionid']) : null;
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

$config = $_POST['config'] ?? array();

if (!empty($config)) {
    foreach ($config as $key => $val) {
        if ($key != 'wysiwyg') {
            $config[$key] = trim($val);
        }
    }

    if ($config['var'] == '') {
        $error['var'] = trans('Option name is required!');
    } elseif (strlen($config['var']) > 64) {
        $error['var'] = trans('Option name is too long (max.64 characters)!');
    } elseif (!preg_match('/^[a-z0-9_-]+$/i', $config['var'])) {
        $error['var'] = trans('Option name contains forbidden characters!');
    } elseif ($LMS->ConfigOptionExists(array('section' => $config['section'], 'variable' => $config['var'])) && empty($config['reftype'])) {
        $error['var'] = trans('Option exists!');
    }

    $section = $config['section'];
    if (empty($section)) {
        $error['section'] = trans('Section name can\'t be empty!');
    } elseif (!preg_match('/^[a-z0-9_-]+(-[a-z0-9_]+:[a-z0-9_-]+)?$/', $section)) {
        $error['section'] = trans('Section name contains forbidden characters!');
    }

    if (empty($config['reftype'])) {
        $option = $config['section'] . '.' . $config['var'];
        if ($msg = $LMS->CheckOption($option, $config['value'], $config['type'])) {
            $error['value'] = $msg;
        }
        if (!isset($error['value']) && (!ConfigHelper::checkPrivilege('superuser') || $config['type'] == CONFIG_TYPE_AUTO)) {
            $config['type'] = $LMS->GetConfigDefaultType($option);
        }
    }

    if (!isset($config['disabled'])) {
        $config['disabled'] = 0;
    }

    if (!empty($config['reftype'])) {
        if (empty($config['refconfigid'])) {
            $error['refconfigid'] = trans('Referenced option does not exists!');
        }
        switch ($config['reftype']) {
            case 'division':
                if (empty($config['divisionid'])) {
                    $error['divisionid'] = trans('Division is required!');
                }
                $refOption = $LMS->GetConfigVariable($config['refconfigid']);
                if (!$refOption) {
                    $error['divisionid'] = trans('Referenced option does not exists!');
                }
                break;
            case 'divisionuser':
                if (empty($config['userid'])) {
                    $error['userid'] = trans('User is required!');
                }
                if (empty($config['divisionid'])) {
                    $error['divisionid'] = trans('Division is required!');
                }
                $refOption = $LMS->GetConfigVariable($config['refconfigid']);
                if (!$refOption) {
                    $error['divisionid'] = trans('Referenced option does not exists!');
                }
                $divisionaccess = $LMS->CheckDivisionsAccess(array('divisions' => $config['divisionid'], 'user_id' => $config['userid']));
                if (!$divisionaccess) {
                    $error['userid'] = trans('User is not assigned to the division!');
                }
                break;
            case 'user':
                if (empty($config['userid'])) {
                    $error['userid'] = trans('User is required!');
                }
                $refOption = $LMS->GetConfigVariable($config['refconfigid']);
                if (!$refOption) {
                    $error['userid'] = trans('Referenced option does not exists!');
                }
                break;
        }
    }

    if (!$error) {
        $args = array(
            'section' => $section,
            'var' => $config['var'],
            'value' => $config['value'],
            'description' => $config['description'],
            'disabled' => $config['disabled'],
            'type' => $config['type'],
            'userid' => (!empty($config['userid']) ? $config['userid'] : null),
            'divisionid' => (!empty($config['divisionid']) ? $config['divisionid'] : null),
            'configid' => (!empty($config['refconfigid']) ? $config['refconfigid'] : null)
        );

        $DB->BeginTrans();
        $configid = $LMS->addConfigOption($args);
        $DB->CommitTrans();

        if ($args['section'] == 'ksef' && $args['var'] == 'delay') {
            $ksef = new \Lms\KSeF\KSeF($DB, $LMS);
            $ksef->updateDelays();
        }

        if (isset($config['reuse'])) {
            $SESSION->redirect_to_history_entry($_SERVER['HTTP_REFERER']);
        } else if (!empty($config['reftype'])) {
            switch ($config['reftype']) {
                case 'division':
                    if (empty($config['divisionid'])) {
                        $error['divisionid'] = trans('Division is required!');
                    }
                    break;
                case 'divisionuser':
                case 'user':
                    if (empty($config['userid'])) {
                        $error['userid'] = trans('User is required!');
                    }
                    break;
                default:
                    $SESSION->redirect_to_history_entry($_SERVER['HTTP_REFERER']);
                    break;
            }
        }
        unset($config['var']);
        unset($config['value']);
        unset($config['description']);
        unset($config['disabled']);
        $SESSION->redirect_to_history_entry();
    }
} elseif (isset($_GET['id'])) {
    $config = $LMS->GetConfigVariable($_GET['id']);
    unset($config['id']);
    $config['section'] = trans('$a-clone', $config['section']);
} elseif (isset($_GET['target-section'])) {
    if (!preg_match('/^[a-z0-9_-]+$/', $_GET['target-section'])) {
        die;
    }

    $variables = (!empty($_POST['marks']) ? $_POST['marks'] : null);
    $DB->BeginTrans();
    $LMS->cloneConfigs(
        array(
        'variables' => $variables,
        'targetSection' => $_GET['target-section'],
        'withchildbindings' => (isset($_GET['withchildbindings']) ? intval($_GET['withchildbindings']) : null),
        'withparentbindings' => (isset($_GET['withparentbindings']) ? intval($_GET['withparentbindings']) : null),
        'targetUser' => (isset($_GET['target-user']) ? intval($_GET['target-user']) : null),
        'targetDivision' => (isset($_GET['target-division']) ? intval($_GET['target-division']) : null),
        'override' => (isset($_GET['override']) ? intval($_GET['override']) : null)
        )
    );
    $DB->CommitTrans();
    $SESSION->redirect_to_history_entry();
}

if (!empty($reftype)) {
    $params['id'] = $refconfigid;
    if (!($LMS->ConfigOptionExists($params))) {
        $SESSION->redirect_to_history_entry();
    }
    unset($params['id']);

    $config = $LMS->GetConfigVariable($refconfigid);

    // line below will allow to select from all divisions and users
    // $params['superuser'] = (ConfigHelper::checkPrivilege('superuser') ? 1 : 0);

    switch ($reftype) {
        case 'division':
            $layout['pagetitle'] = trans('Overriding config option for division');

            $params['exludedDivisions'] = implode(',', array_keys($LMS->getRelatedDivisions($refconfigid) ?: array()));
            $divisionslist = $LMS->getDivisionList($params);
            $SMARTY->assign('divisionslist', $divisionslist);
            break;
        case 'divisionuser':
            $layout['pagetitle'] = trans('Overriding config option for user in division');

            $divisioninfo = $LMS->GetDivision($divisionid);
            $SMARTY->assign('divisioninfo', $divisioninfo);

            $params['excludedUsers'] = implode(',', array_keys($LMS->getRelatedUsers($refconfigid, $divisionid) ?: array()));
            $params['divisions'] = $divisionid;
            $userslist = $LMS->GetUsers($params);
            $SMARTY->assign('userslist', $userslist);
            break;
        case 'user':
            $layout['pagetitle'] = trans('Overriding config option for user');

            $params['excludedUsers'] = implode(',', array_keys($LMS->getRelatedUsers($refconfigid) ?: array()));
            $userslist = $LMS->GetUsers($params);
            $SMARTY->assign('userslist', $userslist);
            break;
    }
} else {
    $layout['pagetitle'] = trans('New Config Option');
    if (isset($_GET['section'])) {
        $config['section'] = $_GET['section'];
    }

    $SMARTY->assign('sections', $LMS->GetConfigSections());
}

if (!empty($option)) {
    $config['documentation'] = Utils::MarkdownToHtml(Utils::LoadMarkdownDocumentation($option));
}

$SMARTY->assign('reftype', $reftype);
$SMARTY->assign('refconfigid', $refconfigid);
$SMARTY->assign('config', $config);
$SMARTY->assign('error', $error);
$SMARTY->display('config/configadd.html');
