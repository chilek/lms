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

$layout['pagetitle'] = trans('Plugin List');

$plugins_config = ConfigHelper::getConfig('phpui.plugins', null, true);
if (is_null($plugins_config)) {
    $DB->Execute(
        "INSERT INTO uiconfig (section, var, value) VALUES (?, ?, ?)",
        array('phpui', 'plugins', '')
    );
}

if (isset($_POST['plugins'])) {
    $postdata = $_POST['plugins'];
    $plugin_manager->enablePlugin($postdata['name'], $postdata['toggle'] ? true : false);
    $SESSION->redirect('?m=pluginlist');
}

$SMARTY->assign('plugins', $plugin_manager->getAllPluginInfo());
$SMARTY->display('pluginlist.html');
