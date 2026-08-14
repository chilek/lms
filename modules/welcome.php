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

if (!ConfigHelper::checkConfig('phpui.hide_fortunes')) {
    @include(LIB_DIR.'/locale/' . Localisation::getCurrentUiLanguage() . '/fortunes.php');
}

$welcome_visible_panels = ConfigHelper::getConfig('phpui.welcome_visible_panels', '', true);
$welcome_visible_panels = preg_split('/([\s]+|[\s]*[,;][\s]*)/', strtolower($welcome_visible_panels), -1, PREG_SPLIT_NO_EMPTY);
$welcome_visible_panels = array_flip($welcome_visible_panels);
$SMARTY->assign('welcome_visible_panels', $welcome_visible_panels);

$layout['pagetitle'] = 'LAN Management System';

$layout['dbversion'] = $DB->GetDBVersion();
$layout['dbtype'] = ConfigHelper::getConfig('database.type');

if (ConfigHelper::checkConfig('privileges.superuser') && (empty($welcome_visible_panels) || isset($welcome_visible_panels['reginfo']))) {
    $content = $LMS->CheckUpdates();

    if (isset($content['newer_version'])) {
        [$v, ] = preg_split('/\s+/', LMS::SOFTWARE_VERSION);

        if (version_compare($content['newer_version'], $v) > 0) {
            $SMARTY->assign('newer_version', $content['newer_version']);
        }
    }

    $SMARTY->assign('regdata', $LMS->GetRegisterData());
}

$__ui_lang = substr(Localisation::getCurrentUiLanguage(), 0, 2);
$software_documentation_url = str_replace(
    '%lang%',
    $__ui_lang,
    LMS::SOFTWARE_DOCUMENTATION_URL
);
if (!preg_match('/^https?:\/\//', $software_documentation_url) && !is_dir($software_documentation_url)) {
    $software_documentation_url = str_replace(
        '%lang%',
        'en',
        LMS::SOFTWARE_DOCUMENTATION_URL
    );
}

$SMARTY->assign('_dochref', $software_documentation_url);
if (empty($welcome_visible_panels) || isset($welcome_visible_panels['helpdesk'])) {
    $SMARTY->assign('rtstats', $LMS->RTStats());
}

if ((ConfigHelper::checkConfig('privileges.superuser') || !ConfigHelper::checkConfig('privileges.hide_sysinfo'))
    && (empty($welcome_visible_panels) || isset($welcome_visible_panels['sysinfo']))) {
    $SI = new Sysinfo;
    $SMARTY->assign('sysinfo', $SI->get_sysinfo());
}

if (ConfigHelper::checkConfig('privileges.superuser') || !ConfigHelper::checkConfig('privileges.hide_summaries')) {
    if (empty($welcome_visible_panels) || isset($welcome_visible_panels['customers'])) {
        $SMARTY->assign('customerstats', $LMS->CustomerStats());
    }

    if (empty($welcome_visible_panels) || isset($welcome_visible_panels['nodes'])) {
        $SMARTY->assign('nodestats', $LMS->NodeStats());
    }

    if (empty($welcome_visible_panels) || isset($welcome_visible_panels['customers'])) {
        $documentsnotapproved = $DB->GetOne('SELECT COUNT(id) AS sum FROM documents WHERE type < 0 AND closed = 0');
        $SMARTY->assign('documentsnotapproved', ($documentsnotapproved ?: 0));

        if (file_exists(ConfigHelper::getConfig('directories.userpanel_dir') . DIRECTORY_SEPARATOR . 'index.php')) {
            $customerschanges = $DB->GetOne('SELECT COUNT(id) FROM up_info_changes');
            $SMARTY->assign('customerschanges', ($customerschanges ?: 0));
        }
    }
}

$layout['plugins'] = $plugin_manager->getAllPluginInfo();

$SMARTY->assign('welcome_sortable_order', json_encode($SESSION->get_persistent_setting('welcome-sortable-order')));
$SMARTY->display('welcome/welcome.html');
