<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

$lang = Localisation::getCurrentUiLanguage();

foreach ($ExecStack->_MODINFO as $modulename => $modinfo) {
    if (isset($modinfo['menus'])) {
        foreach ($modinfo['menus'] as $menuinfo) {
            if (!isset($menuinfo['id'])) {
                $menuinfo['id'] = $modulename;
            }

            $menu[$menuinfo['id']] = array(
            'name' => $menuinfo['text'][$lang],
            'img' => $menuinfo['img'],
            'tip' => $menuinfo['tip'][$lang],
            'link' => '?m='.$modulename,
            'submenu' => array(),
            );
        }
    }
}

foreach ($ExecStack->_MODINFO as $modulename => $modinfo) {
    if (isset($modinfo['actions'])) {
        foreach ($modinfo['actions'] as $actionname => $actioninfo) {
            if (isset($actioninfo['menuname']) && ! $actioninfo['notpublic'] && ! $actioninfo['hidden']) {
                if (! isset($actioninfo['menu'])) {
                    $actioninfo['menu'] = $modulename;
                }

                $args = isset($actioninfo['args']) ? $actioninfo['args'] : '';

                $menu[$actioninfo['menu']]['submenu'][] = array(
                'name' => $actioninfo['menuname'][$lang],
                'link' => '?m='.$modulename.'&a='.$actionname.$args,
                'tip' => $actioninfo['tip'][$lang],
                );
            }
        }
    }
}

$SMARTY->assign('menu', $menu);

unset($menu);
