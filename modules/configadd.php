<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

if ($SESSION->is_set('backto', true)) {
    $backto = $SESSION->get('backto', true);
} elseif ($SESSION->is_set('backto')) {
    $backto = $SESSION->get('backto');
} else {
    $backto = '';
}
$backurl = $backto ? '?' . $backto : '?m=configlist';

if ($SESSION->is_set('backtosave', true)) {
    $backtosave = $SESSION->get('backtosave', true);
} elseif ($SESSION->is_set('backtosave')) {
    $backtosave = $SESSION->get('backtosave');
} else {
    $backtosave = '';
}
$backurlsave = $backtosave ? '?' . $backtosave : '?m=configlist';

$config = isset($_POST['config']) ? $_POST['config'] : array();

if (!empty($config)) {
    foreach ($config as $key => $val) {
        if ($key != 'wysiwyg') {
            $config[$key] = trim($val);
        }
    }

    if (!($config['var'] || $config['value'] || $config['description'])) {
        $SESSION->redirect($backurl);
    }

    if ($config['var'] == '') {
        $error['var'] = trans('Option name is required!');
    } elseif (strlen($config['var']) > 64) {
        $error['var'] = trans('Option name is too long (max.64 characters)!');
    } elseif (!preg_match('/^[a-z0-9_-]+$/', $config['var'])) {
        $error['var'] = trans('Option name contains forbidden characters!');
    } elseif ($LMS->ConfigOptionExists(array('section' => $config['section'], 'variable' => $config['var'])) && empty($config['reftype'])) {
        $error['var'] = trans('Option exists!');
    }

    $section = $config['section'];
    if (empty($section)) {
        $error['section'] = trans('Section name can\'t be empty!');
    } elseif (!preg_match('/^[a-z0-9_-]+$/', $section)) {
        $error['section'] = trans('Section name contains forbidden characters!');
    }

    if (!isset($config['reftype']) || empty($config['reftype'])) {
        $option = $config['section'] . '.' . $config['var'];
        if (!ConfigHelper::checkPrivilege('superuser') || $config['type'] == CONFIG_TYPE_AUTO) {
            $config['type'] = $LMS->GetConfigDefaultType($option);
        }
    }

    if ($msg = $LMS->CheckOption($option, $config['value'], $config['type'])) {
        $error['value'] = $msg;
    }

    if (!isset($config['disabled'])) {
        $config['disabled'] = 0;
    }

    if (!empty($config['reftype'])) {
        if (!isset($config['refconfigid']) || empty($config['refconfigid'])) {
            $error['refconfigid'] = trans('Referenced option does not exists!');
        }
        switch ($config['reftype']) {
            case 'division':
                if (!isset($config['divisionid']) || empty($config['divisionid'])) {
                    $error['divisionid'] = trans('Division is required!');
                }
                $refOption = $LMS->GetConfigVariable($config['refconfigid']);
                if (!$refOption) {
                    $error['divisionid'] = trans('Referenced option does not exists!');
                }
                break;
            case 'divisionuser':
                if (!isset($config['userid']) || empty($config['userid'])) {
                    $error['userid'] = trans('User is required!');
                }
                if (!isset($config['divisionid']) || empty($config['divisionid'])) {
                    $error['divisionid'] = trans('Division is required!');
                }
                $refOption = $LMS->GetConfigVariable($config['refconfigid']);
                if (!$refOption) {
                    $error['divisionid'] = trans('Referenced option does not exists!');
                }
                $divisionaccess = $LMS->CheckDivisionsAccess(array('divisions' => $config['divisionid'], 'user_id' => $config['userid']));
                if (!$divisionaccess) {
                    $error['userid'] = trans('User is not asigned to the division!');
                }
                break;
            case 'user':
                if (!isset($config['userid']) || empty($config['userid'])) {
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
            'userid' => (isset($config['userid']) && !empty($config['userid']) ? $config['userid'] : null),
            'divisionid' => (isset($config['divisionid']) && !empty($config['divisionid']) ? $config['divisionid'] : null),
            'configid' => (isset($config['refconfigid']) && !empty($config['refconfigid']) ? $config['refconfigid'] : null)
        );

        $configid = $LMS->addConfigOption($args);

        if (!isset($config['reuse'])) {
            if (!empty($config['reftype'])) {
                switch ($config['reftype']) {
                    case 'division':
                        if (!isset($config['divisionid']) || empty($config['divisionid'])) {
                            $error['divisionid'] = trans('Division is required!');
                        }
                        break;
                    case 'divisionuser':
                    case 'user':
                        if (!isset($config['userid']) || empty($config['userid'])) {
                            $error['userid'] = trans('User is required!');
                        }
                        break;
                    default:
                        $SESSION->redirect('?'.$_SERVER['QUERY_STRING']);
                        break;
                }
            }
            $SESSION->redirect($backurlsave);
        }
        $SESSION->redirect($backurlsave);

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
} elseif (isset($_GET['target-section'])) {
    if (!preg_match('/^[a-z0-9_-]+$/', $_GET['target-section'])) {
        die;
    }

    $variables = (isset($_POST['marks']) && !empty($_POST['marks']) ? $_POST['marks'] : null);
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
    $SESSION->redirect('?m=configlist');
}

if (!empty($reftype)) {
    $params['id'] = $refconfigid;
    if (!($LMS->ConfigOptionExists($params))) {
        $SESSION->redirect($backurl);
    }
    unset($params['id']);

    $config = $LMS->GetConfigVariable($refconfigid);

    // line below will allow to select from all divisions and users
    // $params['superuser'] = (ConfigHelper::checkPrivilege('superuser') ? 1 : 0);

    switch ($reftype) {
        case 'division':
            $layout['pagetitle'] = trans('Overriding config option for division');

            $params['exludedDivisions'] = implode(',', array_keys($LMS->getRelatedDivisions($refconfigid)));
            $divisionslist = $LMS->getDivisionList($params);
            $SMARTY->assign('divisionslist', $divisionslist);
            break;
        case 'divisionuser':
            $layout['pagetitle'] = trans('Overriding config option for user in division');

            $divisioninfo = $LMS->GetDivision($divisionid);
            $SMARTY->assign('divisioninfo', $divisioninfo);

            $params['excludedUsers'] = implode(',', array_keys($LMS->getRelatedUsers($refconfigid, $divisionid)));
            $params['divisions'] = $divisionid;
            $userslist = $LMS->GetUsers($params);
            $SMARTY->assign('userslist', $userslist);
            break;
        case 'user':
            $layout['pagetitle'] = trans('Overriding config option for user');

            $params['excludedUsers'] = implode(',', array_keys($LMS->getRelatedUsers($refconfigid)));
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

if ($backurl == '?m=configlist') {
    $SESSION->save('backto', $_SERVER['QUERY_STRING']);
    $SESSION->save('backto', $_SERVER['QUERY_STRING'], true);
    $SESSION->save('backtosave', 'm=configlist');
    $SESSION->save('backtosave', 'm=configlist', true);
} else {
    $SESSION->save('backto', 'm=configlist');
    $SESSION->save('backto', 'm=configlist', true);
    $SESSION->save('backtosave', ltrim($backurl, '?'));
    $SESSION->save('backtosave', ltrim($backurl, '?'), true);
}

$SMARTY->assign('backurl', $backurl);
$SMARTY->assign('reftype', $reftype);
$SMARTY->assign('refconfigid', $refconfigid);
$SMARTY->assign('config', $config);
$SMARTY->assign('error', $error);
$SMARTY->display('config/configadd.html');
