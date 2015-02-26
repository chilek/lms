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
    
    /**
     * Loads plugins
     * 
     * @throws Exception Throws exception if plugin not found
     */
    public function __construct()
    {
        $plugins_config = ConfigHelper::getConfig('phpui.plugins');
        if ($plugins_config) {
            $plugins_tuples = explode(';', $plugins_config);
            foreach ($plugins_tuples as $position => $plugin_tuple) {
                list($plugin_name, $plugin_priority) = explode(":", $plugin_tuple);
                if (!class_exists($plugin_name)) {
                    throw new Exception("Unknown plugin $plugin_name at position $position");
                }
                $plugin = new $plugin_name();
                if (!($plugin instanceof LMSPlugin)) {
                    throw new Exception("Plugin object must be instance of LMSPlugin class");
                }
                if ($plugin_priority === null) {
                    $plugin_priority = SubjectInterface::LAST_PRIORITY;
                }
                $this->registerObserver($plugin, $plugin_priority);
            }
        }
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
