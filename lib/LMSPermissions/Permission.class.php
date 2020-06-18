<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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

class Permission
{
    const REGEXP_ALLOW = 1;
    const REGEXP_DENY = 2;

    const MENU_ALL = 1;

    private $name;
    private $label;
    private $allow_regexps;
    private $deny_regexps;
    private $allow_menu_items;
    private $deny_menu_items;

    public function __construct(
        $name,
        $label,
        $allow_regexp = null,
        $deny_regexp = null,
        $allow_menu_items = null,
        $deny_menu_items = null
    ) {
        $this->name = $name;
        $this->label = $label;
        $this->allow_regexps = is_null($allow_regexp) ? array() : array($allow_regexp);
        $this->deny_regexps = is_null($deny_regexp) ? array() : array($deny_regexp);
        $this->allow_menu_items = is_null($allow_menu_items) ? array() : $allow_menu_items;
        $this->deny_menu_items = is_null($deny_menu_items) ? array() : $deny_menu_items;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function addRegExp($regexp, $mode)
    {
        if (!in_array($mode, array(self::REGEXP_DENY, self::REGEXP_ALLOW))) {
            throw new Exception(__METHOD__ . ': illegal mode');
        }
        if ($mode == self::REGEXP_DENY) {
            $this->deny_regexps[] = $regexp;
        } else {
            $this->allow_regexps[] = $regexp;
        }
    }

    public function checkPermission($module, $mode)
    {
        if (!in_array($mode, array(self::REGEXP_DENY, self::REGEXP_ALLOW))) {
            throw new Exception(__METHOD__ . ': illegal mode');
        }
        $regexps = $mode == self::REGEXP_DENY ? $this->deny_regexps : $this->allow_regexps;
        $result = false;
        foreach ($regexps as $regexp) {
            $result |= ((bool) preg_match("/$regexp/i", $module));
        }
        return $result;
    }

    public function getMenuPermissions()
    {
        return array(
            'allow_menu_items' => $this->allow_menu_items,
            'deny_menu_items' => $this->deny_menu_items,
        );
    }
}
