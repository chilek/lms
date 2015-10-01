<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2013 LMS Developers
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

/**
 * InitHandler
 *
 * @author Jarosław Dziubek <yaro@perfect.net.pl>
 */
class MikrotikPluginInit
{
	/**
	 * Sets plugin Smarty templates directory
	 * 
	 * @param Smarty $hook_data Hook data
	 * @return \Smarty Hook data
	 */
	public function smartyInit(Smarty $hook_data)
	{
		
		$template_dirs = $hook_data->getTemplateDir();
		$plugin_templates = PLUGINS_DIR . '/MikrotikPlugin/templates';
		array_unshift($template_dirs, $plugin_templates);
		$hook_data->setTemplateDir($template_dirs);
		return $hook_data;
	}
	/**
	 * Sets plugin Smarty modules directory
	 * 
	 * @param array $hook_data Hook data
	 * @return array Hook data
	 */
	public function modulesDirInit(array $hook_data = array())
	{
		$plugin_modules = PLUGINS_DIR . '/MikrotikPlugin/modules';
		array_unshift($hook_data, $plugin_modules);
		return $hook_data;
	}
	/**
	 * Modifies access table
	 *
	 * @param array $hook_data contains access['table'] data
	 * @return array $hook_data with modified access['table']
	 */
	public function accessTableInit() {
		$access = AccessRights::getInstance();
		$permission=$access->getPermission('node_management');
		$permission->addRegExp('^(nodesignals)$',1);
	}
}
