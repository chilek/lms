<?php

/*
 *  LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

class USERPANEL
{
	var $DB;
	var $SESSION;
	var $CONFIG;
	var $MODULES = array();
        var $_version = '1.11-git'; 
        var $_revision = '$Revision$';
			
	function USERPANEL(&$DB, &$SESSION, &$CONFIG) // ustawia zmienne klasy
	{
	        $this->_revision = preg_replace('/^.Revision: ([0-9.]+).*/i', '\1', $this->_revision);
		$this->DB = &$DB;
		$this->SESSION = &$SESSION;
		$this->CONFIG = &$CONFIG;
	}

	function _postinit()
	{
		return TRUE;
	}

	function AddModule($name = '', $module = '', $tip = '', $prio = 99, $description = '', $submenu = NULL)
	{
		if($name != '')
		{
			$this->MODULES[$module] = array('name' => $name, 'tip' => $tip, 'prio' => $prio, 'description' => $description, 'selected' => false, 'module' => $module, 'submenu' => $submenu);
			if (!function_exists('cmp')) {
			    function cmp($a, $b)
			    {
				if ($a['prio'] == $b['prio']) 
				{
				    return 0;
				}
				return ($a['prio'] < $b['prio']) ? -1 : 1;
			    }
			}
			uasort($this->MODULES,'cmp');
			return TRUE;
		}
		return FALSE;
	}
	
	function GetCustomerRights($id)
	{
		$result = NULL;
		
		$rights = $this->DB->GetAll('SELECT name, module 
					FROM up_rights
					LEFT JOIN up_rights_assignments ON up_rights.id=up_rights_assignments.rightid
		            		WHERE customerid=?', array($id));
		
		if(!$rights)
			$rights = $this->DB->GetAll('SELECT name, module FROM up_rights WHERE setdefault=1');
		
		if($rights)
			foreach($rights as $right)
				$result[$right['module']][$right['name']] = true;
		
		return $result;
	}
}

?>
