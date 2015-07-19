<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2014 LMS Developers
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
class LMSPluginManager extends Subject implements SubjectInterface
{
    protected $hook_name;
    protected $hook_data;
	private $plugins = array();

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
				$plugin_info = array(
					'name' => $plugin_name,
					'enabled' => false,
					'new_style' => true,
					'dbschversion' => null,
				);
				if (array_key_exists($plugin_name, $plugin_priorities)) {
					$plugin = new $plugin_name();
					if (!($plugin instanceof LMSPlugin))
						throw new Exception("Plugin object must be instance of LMSPlugin class");

					$plugin_info = array_merge($plugin_info,
						array(
							'enabled' => true,
							'priority' => $plugin_priorities[$plugin_name],
							'dbschversion' => $plugin->getDbSchemaVersion(),
						)
					);

					$this->registerObserver($plugin, $plugin_priority);
				}
				$this->plugins[$plugin_name] = $plugin_info;
			} else {
				writesyslog("Unknown plugin $plugin_name at position $position", LOG_ERR);
				continue;
			}

		$old_plugins = array_diff($plugins_tuples, array_keys($this->plugins));
		if (empty($old_plugins))
			return;
		foreach ($old_plugins as $plugin)
			$this->plugins[$plugin] = array(
				'name' => $plugin,
				'enabled' => true,
				'new_style' => false,
			);
	}

	/**
	 * Returns info about all plugins
	 *
	 * @return array of all plugin info
	 */
	public function getAllPluginInfo() {
		return $this->plugins;
	}

	/**
	 * Enables/Disables plugin
	 *
	 * @param string plugin name
	 * @param bool enable plugin flag
	 */
	public function enablePlugin($name, $enable) {
		$this->plugins[$name]['enabled'] = $enable;
		$plugins_config = array();
		foreach ($this->plugins as $plugin_name => $plugin)
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
