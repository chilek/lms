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

$dirs = getdir(PLUGINS_DIR, '^[a-zA-Z0-9]+$');
$plugins = array();
if (!empty($dirs)) {
	asort($dirs, SORT_STRING);

	$plugins_config = ConfigHelper::getConfig('phpui.plugins', null, true);
	if (is_null($plugins_config))
		$DB->Execute("INSERT INTO uiconfig (section, var, value) VALUES (?, ?, ?)",
			array('phpui', 'plugins', ''));
	$plugins_config = empty($plugins_config) ? array() : preg_split('/[;,\s\t\n]+/', $plugins_config, -1, PREG_SPLIT_NO_EMPTY);

	foreach ($dirs as $name)
		$plugins[$name] = array('enabled' => in_array($name, $plugins_config) !== FALSE);
	if (isset($_POST['plugins'])) {
		$data = $_POST['plugins'];
		$toggle = intval($data['toggle']) ? true : false;
		$name = $data['name'];
		if ($toggle)
			$plugins_config[] = $name;
		else
			$plugins_config = array_diff($plugins_config, array($name));

		$plugins_config = array_unique($plugins_config);
		$DB->Execute("UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?",
			array(implode(' ',$plugins_config), 'phpui', 'plugins'));

		$SESSION->redirect('?m=pluginlist');
	}
}

$SMARTY->assign('plugins', $plugins);
$SMARTY->display('pluginlist.html');

?>
