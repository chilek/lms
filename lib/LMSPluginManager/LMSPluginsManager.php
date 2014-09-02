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

use Phine\Observer\SubjectInterface;
use Phine\Observer\Subject;

/**
 * LMSPluginsManager
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class LMSPluginsManager extends Subject implements SubjectInterface
{
    protected $hook_name;
    protected $hook_data;
    
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
                if ($plugin_priority === null) {
                    $plugin_priority = SubjectInterface::LAST_PRIORITY;
                }
                $this->registerObserver(new $plugin_name(), $plugin_priority);
            }
        }
    }
    
    public function executeHook($hook_name, &$hook_data)
    {
        error_log('hook executed' . $hook_name);
        $this->hook_name = $hook_name;
        $this->hook_data = $hook_data;
        $this->notifyObservers();
    }
    
    public function getHookName()
    {
        return $this->hook_name;
    }
    
    public function getHookData()
    {
        return $this->hook_data;
    }
    
}
