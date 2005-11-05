<?php

/*
 * LMS version 1.9-cvs
 *
 *  (C) Copyright 2004 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is licensed under LMS Public License. Please, see
 *  doc/LICENSE.en file for information about copyright notice.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  LMS Public License for more details.
 *
 *  $Id$
 */

// this is menu building module. also, it's propably example how to build
// 'closed for world' modules.

// execstack lacks language info so gain it from LMS object:

$lang = $LMS->lang;

foreach($ExecStack->_MODINFO as $modulename => $modinfo)
{
	if(isset($modinfo['menus']))
		foreach($modinfo['menus'] as $menuinfo)
		{
			if(!isset($menuinfo['id']))
				$menuinfo['id'] = $modulename;

			$menu[$menuinfo['id']] = array(
				'name' => $menuinfo['text'][$lang],
				'img' => $menuinfo['img'],
				'tip' => $menuinfo['tip'][$lang],
				'submenu' => array(),
			);
		}
}

foreach($ExecStack->_MODINFO as $modulename => $modinfo)
{
	if(isset($modinfo['actions']))
		foreach($modinfo['actions'] as $actionname => $actioninfo)
		{
			if(! $actioninfo['notpublic'] && ! $actioninfo['hidden'])
			{
				if(! isset($actioninfo['menu']))
					$actioninfo['menu'] = $modulename;

				$menu[$actioninfo['menu']]['submenu'][] = array(
					'name' => $actioninfo['menuname'][$lang],
					'link' => '?m='.$modulename.'&a='.$actionname,
					'tip' => $actioninfo['tip'][$lang],
				);
			}
		}
}

$SMARTY->assign('menu', $menu);

?>
