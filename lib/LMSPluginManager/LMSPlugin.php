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

use Phine\Observer\ObserverInterface;
use Phine\Observer\SubjectInterface;

/**
 * LMSPlugin
 * 
 * Abstractaction for all plugin classes
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
abstract class LMSPlugin implements ObserverInterface
{
    protected $handlers;
    
    public function __construct()
    {
        $this->registerHandlers();
    }
    
    /**
     * Registres hooks handlers
     */
    abstract function registerHandlers();
    
    /**
     * Receives notification from plugin manager and processes it.
     * 
     * @param SubjectInterface $lms_plugin_manager Plugin manager
     */
    public function receiveUpdate(SubjectInterface $lms_plugin_manager)
    {
        $hook_name = $lms_plugin_manager->getHookName();
        $hook_data = $lms_plugin_manager->getHookData();
        $new_hook_data = $this->dispatcher($hook_name, $hook_data);
        $lms_plugin_manager->setHookData($new_hook_data);
    }
    
    /**
     * Processes hook
     * 
     * @param string $hook_name Hook name
     * @param mixed $hook_data Hook data
     * @return mixed Modified hook data
     * @throws Exception
     */
    protected function dispatcher($hook_name, $hook_data)
    {
        if ($hook_name === null) {
            throw new Exception('Hook name must be set!');
        }
        if (!is_array($this->handlers)) {
            throw new Exception('Handlers must be set!');
        }
        if (array_key_exists($hook_name, $this->handlers)) {
            if (!is_array($this->handlers[$hook_name])) {
                throw new Exception("Wrong handler configuration format for hook '$hook_name'!");
            }
            $handler_class = $this->getHandlerClass($hook_name);
            $handler_method = $this->getHandlerMethod($hook_name);
            $handler = new $handler_class();
            return $handler->$handler_method($hook_data);
        } else {
            return $hook_data;
        }
        
    }
    
    /**
     * Returns handler class name for hook
     * 
     * @param string $hook_name Hook name
     * @return string Handler class name
     * @throws Exception
     */
    protected function getHandlerClass($hook_name)
    {
        if (!isset($this->handlers[$hook_name]['class']) || !is_string($this->handlers[$hook_name]['class'])) {
            throw new Exception("Wrong handler configuration format for hook '$hook_name'!");
        } else {
            return $this->handlers[$hook_name]['class'];
        }
    }
    
    /**
     * Returns handler method name for hook
     * 
     * @param string $hook_name Hook name
     * @return string Handler method name
     * @throws Exception
     */
    protected function getHandlerMethod($hook_name)
    {
        if (!isset($this->handlers[$hook_name]['method']) || !is_string($this->handlers[$hook_name]['method'])) {
            throw new Exception("Wrong handler configuration format for hook '$hook_name'!");
        } else {
            return $this->handlers[$hook_name]['method'];
        }
    }
}
