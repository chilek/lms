<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

function smarty_function_tax_rate_selection(array $params, $template)
{
    $lms = LMS::getInstance();
    $taxratelist = $lms->GetTaxes();

    $id = isset($params['id']) ? ' id="' . $params['id'] . '"' : null;
    $name = isset($params['name']) ? ' name="' . $params['name'] . '"' : null;
    $selected = $params['selected'] ?? ConfigHelper::getConfig('phpui.default_taxrate', 23.00);
    $class = isset($params['class']) ? ' class="'. $params['class'] . '"' : null;
    $form = isset($params['form']) ? ' form="'. $params['form'] . '"' : null;
    $trigger = $params['trigger'] ?? null;
    $tip = $params['tip'] ?? '- select tax rate -';
    $tip_text = LMSSmartyPlugins::tipFunction(
        array(
            'text' => trans($tip),
            'trigger' => $trigger,
        ),
        $template
    );
    $visible = isset($params['visible']) && !$params['visible'] ? ' style="display: none;"' : '';
    $required = isset($params['required']) ? ' required' : null;
    $icon = '<i class="' . (strpos($params['icon'], 'lms-ui-icon-') === 0 ? $params['icon'] : 'lms-ui-icon-' . $params['icon']) . '"></i>';

    $data_attributes = '';
    foreach ($params as $name => $value) {
        if (strpos($name, 'data_') === 0) {
            $data_attributes .= ' ' . str_replace('_', '-', $name) . '="' . $value . '"';
        }
    }

    $options = '';
    if (sizeof($taxratelist) == '0') {
        $options .= '<option selected value="">' . trans('- no tax rates defined -') . '</option>';
    } else {
        foreach ($taxratelist as $tax) {
            $options .= '<option value="' . $tax['id'] . '" data-taxratevalue="' . $tax['value'] . '"'
            . LMSSmartyPlugins::tipFunction(array('text' => $tax['label']), $template)
            . ($tax['value'] == $selected ? ' selected' : null) . '>' . $tax['label'] . ' (' . $tax['value'] . '%)</option>';
        }
    }

    return $icon . '<select ' . $name . $id . $class . $form . $tip_text . $visible . $required . $data_attributes . '>'
        . $options . '</select>';
}
