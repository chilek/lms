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

if (isset($_GET['s']) && isset($_GET['v'])) {
    $params = array(
        'section' => $_GET['s'],
        'variable' => $_GET['v'],
    );
} else {
    $params['id'] = $_GET['id'];
}

if (!($id = $LMS->ConfigOptionExists($params))) {
    $SESSION->redirect($backurl);
}

if (isset($_GET['statuschange'])) {
    $LMS->toggleConfigOption($id);
    $SESSION->redirect($backurl);
}

$config = $DB->GetRow('SELECT * FROM uiconfig WHERE id = ?', array($id));
$option = $config['section'] . '.' . $config['var'];
$config['type'] = ($config['type'] == CONFIG_TYPE_AUTO) ? $LMS->GetConfigDefaultType($option) : $config['type'];

$reftype = null;
$refconfigid = null;
$divisioninfo = null;
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
    } elseif (empty($config['divisionid']) && !empty($config['userid'])) {
        $reftype = 'user';
        $refconfigid = $config['configid'];
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
    } elseif (!preg_match('/^[a-z0-9_-]+$/', $cfg['var'])) {
        $error['var'] = trans('Option name contains forbidden characters!');
    }

    if (($cfg['var']!=$config['var'] || $cfg['section']!=$config['section'])
        && $LMS->ConfigOptionExists(array('section' => $cfg['section'], 'variable' => $cfg['var']))
            && empty($config['reftype'])
    ) {
        $error['var'] = trans('Option exists!');
    }

    if (!preg_match('/^[a-z0-9_-]+$/', $cfg['section']) && $cfg['section']!='') {
        $error['section'] = trans('Section name contains forbidden characters!');
    }

    $option = $cfg['section'] . '.' . $cfg['var'];
    if (!isset($config['reftype']) || empty($config['reftype'])) {
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
        if (!isset($cfg['refconfigid']) || empty($cfg['refconfigid'])) {
            $error['refconfigid'] = trans('Referenced option does not exists!');
        }
        switch ($cfg['reftype']) {
            case 'division':
                if (!isset($cfg['divisionid']) || empty($cfg['divisionid'])) {
                    $error['divisionid'] = trans('Division is required!');
                }
                $refOption = $LMS->GetConfigVariable($cfg['refconfigid']);
                if (!$refOption) {
                    $error['divisionid'] = trans('Referenced option does not exists!');
                }
                break;
            case 'divisionuser':
                if (!isset($cfg['userid']) || empty($cfg['userid'])) {
                    $error['userid'] = trans('User is required!');
                }
                if (!isset($cfg['divisionid']) || empty($cfg['divisionid'])) {
                    $error['divisionid'] = trans('Division is required!');
                }
                $refOption = $LMS->GetConfigVariable($cfg['refconfigid']);
                if (!$refOption) {
                    $error['divisionid'] = trans('Referenced option does not exists!');
                }
                $divisionaccess = $LMS->CheckDivisionsAccess(array('divisions' => $cfg['divisionid'], 'user_id' => $cfg['userid']));
                if (!$divisionaccess) {
                    $error['userid'] = trans('User is not asigned to the division!');
                }
                break;
            case 'user':
                if (!isset($cfg['userid']) || empty($cfg['userid'])) {
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

            $SESSION->redirect($backurlsave);
        } else {
            $SESSION->redirect('?'.$_SERVER['QUERY_STRING']);
        }
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

$config['documentation'] = Utils::MarkdownToHtml(Utils::LoadMarkdownDocumentation($option));

$SMARTY->assign('backurl', $backurl);
$SMARTY->assign('reftype', $reftype);
$SMARTY->assign('refconfigid', $refconfigid);
$SMARTY->assign('divisioninfo', $divisioninfo);
$SMARTY->assign('sections', $LMS->GetConfigSections());
$SMARTY->assign('error', $error);
$SMARTY->assign('config', $config);
$SMARTY->assign('relatedoptions', $relatedOptions);
$SMARTY->display('config/configedit.html');
