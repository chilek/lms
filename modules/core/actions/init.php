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

// Include required files (including sequence is important)

require_once(LIB_DIR.'/unstrip.php');
require_once(LIB_DIR.'/definitions.php');
require_once(LIB_DIR.'/checkip.php');
require_once(LIB_DIR.'/common.php');
require_once(LIB_DIR.'/Session.class.php');
require_once(LIB_DIR.'/Auth.class.php');
require_once(LIB_DIR.'/LMS.class.php');

// Initialize main classes

$SESSION = new Session($DB, ConfigHelper::getConfig('phpui.timeout'));
$AUTH = new Auth($DB, $SESSION);
$LMS = new LMS($DB, $AUTH);
$LMS->ui_lang = $_ui_language;
$LMS->lang = $_language;

$SMARTY->assignByRef('_LANG', $_LANG);
$SMARTY->assignByRef('LANGDEFS', $LANGDEFS);
$SMARTY->assignByRef('_ui_language', $LMS->ui_lang);
$SMARTY->assignByRef('_language', $LMS->lang);
$SMARTY->assign('_dochref', is_dir('doc/html/'.$LMS->ui_lang) ? 'doc/html/'.$LMS->ui_lang.'/' : 'doc/html/en/');

$layout['logname'] = $AUTH->logname;
$layout['lmsdbv'] = $DB->GetVersion();
$layout['smarty_version'] = $SMARTY->_version;
$layout['hostname'] = hostname();
$layout['lmsv'] = '1.11-git';
$layout['lmsvr'] = $LMS->_revision;
$layout['dberrors'] =& $DB->GetErrors();
$layout['popup'] = isset($_GET['popup']) ? true : false;

$SMARTY->assignByRef('layout', $layout);
$SMARTY->assign('_module', $ExecStack->module);
$SMARTY->assign('_action', $ExecStack->action);

header('X-Powered-By: LMS/'.$layout['lmsv']);

$error = null; // initialize error variable needed for (almost) all modules

if ($AUTH->islogged !== true) {
    $SMARTY->assign('error', $AUTH->error);
    $SMARTY->display('../modules/core/templates/login.html');
    die;
}

// core plugins
register_plugin('menu-menuend', '../modules/core/templates/logout.html');
