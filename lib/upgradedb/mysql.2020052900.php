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

function parseCssProperties($text)
{
    $result = array();
    $text = preg_replace('/\s/', '', $text);
    $properties = explode(';', $text);
    if (!empty($properties)) {
        foreach ($properties as $property) {
            list ($name, $value) = explode(':', $property);
            $result[$name] = $value;
        }
    }
    return $result;
}

$this->BeginTrans();

$categories = $this->GetAll('SELECT id, style FROM rtcategories');
if (!empty($categories)) {
    foreach ($categories as $category) {
        $cssProperties = parseCssProperties($category['style']);
        if (isset($cssProperties['background'])) {
            $background_color = $cssProperties['background'];
        } elseif (isset($cssProperties['background-color'])) {
            $background_color = $cssProperties['background-color'];
        } else {
            $background_color = '#ffffff';
        }
        if (isset($cssProperties['color'])) {
            $color = $cssProperties['color'];
        } else {
            $color = '#000000';
        }
        $this->Execute(
            "UPDATE rtcategories SET style = ? WHERE id = ?",
            array('background-color:' . $background_color . ';' . 'color:' . $color, $category['id'])
        );
    }
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2020052900', 'dbversion'));

$this->CommitTrans();
