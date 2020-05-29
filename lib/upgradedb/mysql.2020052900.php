<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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
 */

function findCssProperty($text, $property)
{
    if (($pos = stripos($text, $property . ':')) === false) {
        return null;
    }
    $text = substr($text, $pos + strlen($property) + 1);
    if (preg_match('/^\s*(?<value>[^;}\r\n]+)/', $text, $m)) {
        return $m['value'];
    }
    return null;
}

$this->BeginTrans();

$categories = $this->GetAll('SELECT id, style FROM rtcategories');
if (!empty($categories)) {
    foreach ($categories as $category) {
        $color = findCssProperty($category['style'], 'background-color');
        if (!isset($color)) {
            $color = findCssProperty($category['style'], 'background');
            if (!isset($color)) {
                $color = 'white';
            }
        }
        $this->Execute("UPDATE rtcategories SET style = ? WHERE id = ?", array($color, $category['id']));
    }
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2020052900', 'dbversion'));

$this->CommitTrans();
