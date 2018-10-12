<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

class LMSSmarty extends Smarty {
	private $plugin_manager;
	private static $smarty = null;

	public function __construct() {
		parent::__construct();
		self::$smarty = $this;
	}

	public static function getInstance() {
		return self::$smarty;
	}

	public function setPluginManager(LMSPluginManager $plugin_manager) {
		$this->plugin_manager = $plugin_manager;
	}

	public function display($template = null, $cache_id = null, $compile_id = null, $parent = null) {
		$layout = $this->getTemplateVars('layout');
		if (!empty($layout))
			if (array_key_exists('module', $layout))
				$this->plugin_manager->ExecuteHook($layout['module'] . '_before_module_display',
					array('smarty' => $this));
			elseif (array_key_exists('userpanel_module', $layout) && array_key_exists('userpanel_function', $layout))
				$this->plugin_manager->ExecuteHook('userpanel_' . $layout['userpanel_module'] . '_' . $layout['userpanel_function'] . '_before_module_display',
					array('smarty' => $this));
		parent::display($template, $cache_id, $compile_id, $parent);
	}
}

?>
