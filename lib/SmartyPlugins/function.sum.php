<?php

/*
 * LMS version 1.11-git
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

function smarty_function_sum($params, $template)
{
    $array = $params['array'];
    $format = ($params['string_format'] ?? '%d');
    $default = ($params['default'] ?? 0);
    $result = 0;

    $alreadyAssocArray = false;

    if ($array) {
        foreach ($array as $row) {
            if (is_array($row)) {
                if (isset($params['column'], $row[$params['column']])) {
                    $result += $row[$params['column']];
                    $alreadyAssocArray = true;
                }
            } elseif (!$alreadyAssocArray) {
                $result += floatval($row);
            }
        }
    }

    $result = $result ?? $default;

    if (isset($params['assign'])) {
        $template->assign($params['assign'], $result);
    } else {
        return sprintf($format, $result);
    }
}
