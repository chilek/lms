<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

function smarty_function_currency_selection($params, $template)
{
    $elementname = isset($params['elementname']) ? $params['elementname'] : 'currency';
    $selected = isset($params['selected']) && isset($GLOBALS['CURRENCIES'][$params['selected']])
        ? $params['selected'] : null;
    $locked = isset($params['locked']) && $params['locked'];
    if (function_exists('get_currency_value') && !$locked) {
        $result = '<select name="' . $elementname . '" ' . Utils::tip(array('text' => 'Select currency'), $template)
            . (isset($params['form']) ? ' form="' . $params['form'] . '"' : '') . '>';
        foreach ($GLOBALS['CURRENCIES'] as $currency) {
            $result .= '<option value="' . $currency . '"'
                . ($currency == $selected ? ' selected' : '') . '>' . $currency . '</option>';
        }
        $result .= '</select>';
    } else {
        $result = LMS::$currency . '<input type="hidden" name="' . $elementname . '"'
            . (isset($params['form']) ? ' form="' . $params['form'] . '"' : '') . '" value="' . LMS::$currency . '">';
    }

    return $result;
}
