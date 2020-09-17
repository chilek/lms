<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

function smarty_function_tax_category_selection($params, $template)
{
    $elementname = isset($params['elementname']) ? $params['elementname'] : 'taxcategory';
    $selected = isset($params['selected']) ? $params['selected'] : null;
    $data_attributes = '';
    foreach ($params as $name => $value) {
        if (strpos($name, 'data_') === 0) {
            $data_attributes .= ' ' . str_replace('_', '-', $name) . '="' . $value . '"';
        }
    }
    $result = '<select name="' . $elementname . '"' . (isset($params['id']) ? ' id="' . $params['id'] . '"' : '')
        . (isset($params['class']) ? ' class="' . $params['class'] . '"' : '')
        . (isset($params['form']) ? ' form="' . $params['form'] . '"' : '')
        . (isset($params['tip']) ? ' ' . LMSSmartyPlugins::tipFunction(array('text' => $params['tip'], 'trigger' => isset($params['id']) ? $params['id'] : $elementname), $template) : '')
        . (isset($params['visible']) && !$params['visible'] ? ' style="display: none;"' : '')
        . $data_attributes . '>';
    $result .= '<option value="0">' . trans("- none -") . '</option>';
    foreach ($GLOBALS['TAX_CATEGORIES'] as $categoryid => $category) {
        $result .= '<option value="' . $categoryid . '"'
            . ($categoryid == $selected ? ' selected' : '') . ' '
            . LMSSmartyPlugins::tipFunction(array('text' => $category['description']), $template) . '>'
            . $category['label'] . ' (' . sprintf('%02d', $categoryid) . ')' . '</option>';
    }
    $result .= '</select>';

    return $result;
}
