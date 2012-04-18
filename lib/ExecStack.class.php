<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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
		      
class ExecStack
{
	var $version = '1.11-cvs';
	var $_MODINFO = array();
	var $_BINDTABLE = array();
	var $_EXECSTACK = array();
	var $_STATUS = array();
	var $module;
	var $action;
	var $modules_dir;

	function ExecStack($directory = 'modules/', $module, $action)
	{
		$this->module = $module;
		$this->action = $action;

		$this->loadModules($directory);
		$this->buildExecStack($module, $action);
	}
	
	function loadModules($modules_dir)
	{
		$this->modules_dir = $modules_dir;
		$this->_MODINFO = array();
		$priority_table = array();
		if($handle = opendir($this->modules_dir))
		{
			while (false !== ($file = readdir($handle)))
			{
				if(is_dir($this->modules_dir.'/'.$file) && is_readable($this->modules_dir.'/'.$file.'/modinfo.php'))
				{
					include($this->modules_dir.'/'.$file.'/modinfo.php');
					foreach($_MODINFO as $module_name => $modinfo)
					{
						$this->_MODINFO[$module_name] = $modinfo;
						if(! isset($this->_MODINFO[$module_name]['hidden']))
							$this->_MODINFO[$module_name]['hidden'] = FALSE;
						if(! isset($this->_MODINFO[$module_name]['notpublic']))
							$this->_MODINFO[$module_name]['notpublic'] = FALSE;
						if(! isset($this->_MODINFO[$module_name]['default']))
							$this->_MODINFO[$module_name]['default'] = FALSE;

						if(isset($this->_MODINFO[$module_name]['actions']) && is_array($this->_MODINFO[$module_name]['actions']))
							foreach($this->_MODINFO[$module_name]['actions'] as $actionname => $action_info)
							{
								if(! isset($action_info['hidden']))
									$this->_MODINFO[$module_name]['actions'][$actionname]['hidden'] = FALSE;
								if(! isset($action_info['notpublic']))
									$this->_MODINFO[$module_name]['actions'][$actionname]['notpublic'] = FALSE;
								if(! isset($action_info['dontexec']))
									$this->_MODINFO[$module_name]['actions'][$actionname]['dontexec'] = FALSE;
								if(! isset($action_info['notemplate']))
									$this->_MODINFO[$module_name]['actions'][$actionname]['notemplate'] = FALSE;
								if(! isset($action_info['template']) && ! $this->_MODINFO[$module_name]['actions'][$actionname]['notemplate'])
									$this->_MODINFO[$module_name]['actions'][$actionname]['template'] = $actionname;
								if(! isset($action_info['bindings']))
									$this->_MODINFO[$module_name]['actions'][$actionname]['bindings'] = array();
								if(! isset($action_info['default']))
									$this->_MODINFO[$module_name]['actions'][$actionname]['default'] = FALSE;
							}
					}
				}
			}
			closedir($handle);
		}

		foreach($this->_MODINFO as $module_name => $modinfo)
		{
			$priority_table['priority'][] = (isset($modinfo['priority']) ? $modinfo['priority'] : 255);
			$priority_table['module'][] = $module_name;
		}

		array_multisort($priority_table['priority'], SORT_NUMERIC, SORT_ASC, $priority_table['module'], SORT_ASC);

		foreach($priority_table['module'] as $module_name)
			$_MODINFO_tmp[$module_name] = $this->_MODINFO[$module_name];

		$this->_MODINFO = $_MODINFO_tmp;
		
		return $this->_MODINFO;
	}

	function getDefaultModule()
	{
		if($this->_MODINFO)
			foreach($this->_MODINFO as $module_name => $module_info)
				if($module_info['default'] === TRUE)
					return $module_name;
	}

	function getDefaultAction($module)
	{
		if(isset($this->_MODINFO[$module]) && $this->_MODINFO[$module]['actions'])
			foreach($this->_MODINFO[$module]['actions'] as $action_name => $action_info)
				if($action_info['default'] === TRUE)
					return $action_name;
	}					

	function buildBindTable()
	{
		$this->_BINDTABLE = array(
			'pre/*:*' => array(),
			'post/*:*' => array(),
			);
			
		if($this->_MODINFO)
			foreach($this->_MODINFO as $module_name => $module_info)
				if(isset($module_info['actions']))
					foreach($module_info['actions'] as $action_name => $action_info)
						if(isset($action_info['bindings']))
							foreach($action_info['bindings'] as $bind)
								$this->_BINDTABLE[$bind][] = $module_name.':'.$action_name;
								
		return $this->_BINDTABLE;
	}

	function needExec($module, $action)
	{
		return ! $this->_MODINFO[$module]['actions'][$action]['dontexec'];
	}

	function needTemplate($module, $action)
	{
		return ! $this->_MODINFO[$module]['actions'][$action]['notemplate'];
	}
	
	function getTemplate($module, $action)
	{
		return $this->_MODINFO[$module]['actions'][$action]['template'];
	}

	function replaceTemplate($src_mod, $src_tpl, $dst_mod, $dst_tpl)
	{
		foreach($this->_EXECSTACK['templates'] as $idx => $template)
			if($template['module'] == $src_mod && $template['template'] == $src_tpl)
			{
				$this->_EXECSTACK['templates'][$idx]['module'] = $dst_mod;
				$this->_EXECSTACK['templates'][$idx]['template'] = $dst_tpl;
			}
	}

	function dropTemplate($module, $template)
	{
		$templates = $this->_EXECSTACK['templates'];
		foreach($templates as $idx => $tpl)
			if($tpl['module'] == $module && $tpl['template'] == $template)
			{
				unset($this->_EXECSTACK['templates'][$idx]);
				break;
			}
	}

	function dropAction($module, $action)
	{
		$actions = $this->_EXECSTACK['actions'];
		foreach($actions as $idx => $act)
			if($act['module'] == $module && $act['action'] == $action)
			{
				unset($this->_EXECSTACK['actions'][$idx]);
				break;
			}
	}

	function moduleExists($module)
	{
		return isset($this->_MODINFO[$module]) && is_array($this->_MODINFO[$module]) && is_readable($this->modules_dir.'/'.$module.'/modinfo.php');
	}

	function moduleIsPublic($module)
	{
		return ! $this->_MODINFO[$module]['notpublic'];
	}

	function actionExists($module, $action)
	{
		return is_array($this->_MODINFO[$module]['actions'][$action]) && ($this->needExec($module, $action) ? is_readable($this->modules_dir.'/'.$module.'/actions/'.$action.'.php') : TRUE);
	}

	function actionIsPublic($module, $action)
	{
		return ! $this->_MODINFO[$module]['actions'][$action]['notpublic'];
	}

	function buildExecStack($module, $action, $depth = 0)
	{
		if($depth == 0)
		{	
			if($module == '')
				$module = $this->getDefaultModule();
			if($action == '')
				$action = $this->getDefaultAction($module);

			$this->module = $module;
			$this->action = $action;

			// TODO: consider to make functions that will find
			// actions suitable for situations described below
			
			if(! $this->moduleExists($module))
			{
				$module = 'core';
				$action = 'err_modulenotfound';
			}
			elseif(! $this->moduleIsPublic($module))
			{
				$module = 'core';
				$action = 'err_modulenotpublic';
			}
			elseif(! $this->actionExists($module, $action))
			{
				$module = 'core';
				$action = 'err_actionnotfound';
			}
			elseif(! $this->actionIsPublic($module, $action))
			{
				$module = 'core';
				$action = 'err_actionnotpublic';
			}
		}
				
		if($depth > 15)
			return NULL;

		if($this->_BINDTABLE == array())
			$this->buildBindTable();
	
		$stack = array();

		if($depth == 0 && $this->_BINDTABLE['pre/*:*'])
			foreach($this->_BINDTABLE['pre/*:*'] as $bind)
			{
				list($tmodule, $taction) = explode(':', $bind);
				foreach($this->buildExecStack($tmodule, $taction, $depth + 1) as $tbind)
					array_push($stack, $tbind);
			}
		
		if(isset($this->_BINDTABLE['pre/'.$module.':'.$action]))
			foreach($this->_BINDTABLE['pre/'.$module.':'.$action] as $bind)
			{
				list($tmodule, $taction) = explode(':', $bind);
				foreach($this->buildExecStack($tmodule, $taction, $depth + 1) as $tbind)
					array_push($stack, $tbind);
			}
		
		array_push($stack, $module.':'.$action);
	
		if(isset($this->_BINDTABLE['post/'.$module.':'.$action]))
			foreach($this->_BINDTABLE['post/'.$module.':'.$action] as $bind)
			{
				list($tmodule, $taction) = explode(':', $bind);
				foreach($this->buildExecStack($tmodule, $taction, $depth + 1) as $tbind)
					array_push($stack, $tbind);
			}
		
		if($depth == 0 && $this->_BINDTABLE['post/*:*'])
			foreach($this->_BINDTABLE['post/*:*'] as $bind)
			{
				list($tmodule, $taction) = explode(':', $bind);
				foreach($this->buildExecStack($tmodule, $taction, $depth + 1) as $tbind)
					array_push($stack, $tbind);
			}
		
		if($stack && $depth == 0)
		{
			$this->_EXECSTACK = array();
			foreach($stack as $stackitem)
			{
				list($module, $action) = explode(':', $stackitem);
				$this->_EXECSTACK['actions'][] = array( 'module' => $module, 'action' => $action, );
				if($this->needTemplate($module, $action))
					$this->_EXECSTACK['templates'][] = array( 'module' => $module, 'template' => $this->getTemplate($module, $action), );
			}
			return $this->_EXECSTACK;
		}
		else
			$this->_EXECSTACK = $stack;

		return $this->_EXECSTACK;
	}
			
}

?>
