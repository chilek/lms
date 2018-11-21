<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2015 LMS Developers
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

use Phine\Observer\SubjectInterface;
use Phine\Observer\Subject;

/**
 * LMSPluginManager
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class LMSPluginManager extends Subject implements SubjectInterface {
	const NEW_STYLE = 1;
	const OLD_STYLE = 2;
	const ALL_STYLES = 3;

	protected $hook_name;
	protected $hook_data;
	private $new_style_plugins = array();
	private $old_style_plugins = array();

	/**
	 * Loads plugins
	 *
	 * @throws Exception Throws exception if plugin not found
	*/
	public function __construct() {
		$dirs = getdir(PLUGINS_DIR, '^[0-9a-zA-Z]+$');
		if (empty($dirs))
			return;
		asort($dirs);

		$plugins_config = ConfigHelper::getConfig('phpui.plugins');
		$plugins_tuples = empty($plugins_config) ? array() : preg_split('/[;,\s\t\n]+/', $plugins_config, -1, PREG_SPLIT_NO_EMPTY);

		$plugin_priorities = array();
		foreach ($plugins_tuples as $idx => $plugin_tuple) {
			$plugin_props = explode(':', $plugin_tuple);
			$plugin_priorities[$plugin_props[0]] = count($plugin_props) == 2 ? intval($plugin_props[1]) : SubjectInterface::LAST_PRIORITY;
			$plugins_tuples[$idx] = $plugin_props[0];
		}

		foreach ($dirs as $plugin_name)
			if (class_exists($plugin_name)) {
				$plugin_name::loadLocales();
				$plugin_info = array(
					'name' => $plugin_name,
					'enabled' => false,
					'new_style' => true,
					'dbcurrschversion' => null,
					'dbschversion' => defined($plugin_name . '::PLUGIN_DBVERSION') ? constant($plugin_name . '::PLUGIN_DBVERSION') : null,
					'fullname' => defined($plugin_name . '::PLUGIN_NAME') ? trans(constant($plugin_name . '::PLUGIN_NAME')) : null,
					'description' => defined($plugin_name . '::PLUGIN_DESCRIPTION') ? trans(constant($plugin_name . '::PLUGIN_DESCRIPTION')) : null,
					'author' => defined($plugin_name . '::PLUGIN_AUTHOR') ? constant($plugin_name . '::PLUGIN_AUTHOR') : null,
				);
				if (array_key_exists($plugin_name, $plugin_priorities)) {
					$plugin = new $plugin_name();
					if (!($plugin instanceof LMSPlugin))
						throw new Exception("Plugin object must be instance of LMSPlugin class");

					$plugin_info = array_merge($plugin_info,
						array(
							'enabled' => true,
							'priority' => $plugin_priorities[$plugin_name],
							'dbcurrschversion' => $plugin->getDbSchemaVersion(),
						)
					);

					$this->registerObserver($plugin, $plugin_info['priority']);
				}
				$this->new_style_plugins[$plugin_name] = $plugin_info;
			} else {
				writesyslog("Unknown plugin $plugin_name at position $position", LOG_ERR);
				continue;
			}

		$files = getdir(LIB_DIR . DIRECTORY_SEPARATOR . 'plugins', '^[0-9a-zA-Z_\-]+\.php$');
		if (empty($files))
			return;
		asort($files);

		$old_plugins = array_diff($plugins_tuples, array_keys($this->new_style_plugins));
		foreach ($files as $plugin_name) {
			if (!is_readable(LIB_DIR . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $plugin_name))
				continue;
			$plugin_name = str_replace('.php', '', $plugin_name);
			$plugin_info = array(
				'name' => $plugin_name,
				'enabled' => false,
				'new_style' => false,
			);
			if (array_key_exists($plugin_name, $plugin_priorities))
				$plugin_info['enabled'] = true;
			$this->old_style_plugins[$plugin_name] = $plugin_info;
		}
	}

	/**
	 * Returns info about selected style plugins
	 *
	 * @param integer $style selected style plugins
	 * @return array of all plugin info
	 */
	public function getAllPluginInfo($style = self::ALL_STYLES) {
		$plugins = ($style & self::NEW_STYLE) ? $this->new_style_plugins : array();
		if ($style & self::OLD_STYLE)
			$plugins = array_merge($plugins, $this->old_style_plugins);
		return $plugins;
	}

	/**
	 * Enables/Disables plugin
	 *
	 * @param string $name plugin name
	 * @param bool $enable enable plugin flag
	 */
	public function enablePlugin($name, $enable) {
		$this->executeHook(($enable ? 'enable' : 'disable') .'_plugin', $name);
		if (in_array($name, $this->new_style_plugins))
			$this->new_style_plugins[$name]['enabled'] = $enable;
		else
			$this->old_style_plugins[$name]['enabled'] = $enable;
		$plugins_config = array();
		foreach (array_merge($this->new_style_plugins, $this->old_style_plugins) as $plugin_name => $plugin)
			if ($plugin['enabled'])
				$plugins_config[] = $plugin_name
					. (isset($plugin['priority']) && $plugin['priority'] != SubjectInterface::LAST_PRIORITY ? ':' . $plugin['priority'] : '');
		LMSDB::getInstance()->Execute("UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?",
			array(implode(' ', $plugins_config), 'phpui', 'plugins'));
	}

    /**
     * Executes hook
     * 
     * @param mixed $hook_name Hook name
     * @param mixed $hook_data Hook data
     * @return mixed Modified hook data
     */
    public function executeHook($hook_name, $hook_data = null)
    {
        $this->hook_name = $hook_name;
        $this->hook_data = $hook_data;
        $this->notifyObservers();
        return $this->hook_data;
    }
    
    /**
     * Returns hook name
     * 
     * @return string Hook name
     */
    public function getHookName()
    {
        return $this->hook_name;
    }
    
    /**
     * Returns hook data
     * 
     * @return mixed Hook data
     */
    public function getHookData()
    {
        return $this->hook_data;
    }
    
    /**
     * Sets hook data
     * 
     * @param mixed $hook_data Hook data
     */
    public function setHookData($hook_data)
    {
        $this->hook_data = $hook_data;
    }
    
}
