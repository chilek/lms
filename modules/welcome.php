<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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

@include(LIB_DIR.'/locale/'.$_language.'/fortunes.php');

$layout['pagetitle'] = 'LAN Management System';

$layout['dbversion'] = $DB->GetDBVersion();
$layout['dbtype'] = ConfigHelper::getConfig('database.type');

if (ConfigHelper::checkConfig('privileges.superuser')) {
    $content = $LMS->CheckUpdates();

    if (isset($content['newer_version'])) {
        list($v, ) = preg_split('/\s+/', LMS::SOFTWARE_VERSION);

        if (version_compare($content['newer_version'], $v) > 0) {
            $SMARTY->assign('newer_version', $content['newer_version']);
        }
    }

    $SMARTY->assign('regdata', $LMS->GetRegisterData());
}

$SMARTY->assign('_dochref', is_dir('doc/html/'.$LMS->ui_lang) ? 'doc/html/'.$LMS->ui_lang.'/' : 'doc/html/en/');
$SMARTY->assign('rtstats', $LMS->RTStats());

if (ConfigHelper::checkConfig('privileges.superuser') || !ConfigHelper::checkConfig('privileges.hide_sysinfo')) {
    $SI = new Sysinfo;
    $SMARTY->assign('sysinfo', $SI->get_sysinfo());
}

if (ConfigHelper::checkConfig('privileges.superuser') || !ConfigHelper::checkConfig('privileges.hide_summaries')) {
    $SMARTY->assign('customerstats', $LMS->CustomerStats());
    $SMARTY->assign('nodestats', $LMS->NodeStats());
    $documentsnotapproved=$DB->GetOne('SELECT COUNT(id) AS sum FROM documents WHERE type < 0 AND closed = 0');
    $SMARTY->assign('documentsnotapproved', ( $documentsnotapproved ? $documentsnotapproved : 0));

    if (file_exists(ConfigHelper::getConfig('directories.userpanel_dir') . DIRECTORY_SEPARATOR . 'index.php')) {
        $customerschanges=$DB->GetOne('SELECT COUNT(id) FROM up_info_changes');
        $SMARTY->assign('customerschanges', ( $customerschanges ? $customerschanges : 0));
    }
}

$layout['plugins'] = $plugin_manager->getAllPluginInfo();

$SMARTY->assign('welcome_sortable_order', json_encode($SESSION->get_persistent_setting('welcome-sortable-order')));
$SMARTY->display('welcome/welcome.html');
