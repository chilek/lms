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
    $default_taxrate = ConfigHelper::getConfig('phpui.default_taxrate', 23.00);

    $lms = LMS::getInstance();
    $taxratelist = $lms->GetTaxes();

    // search taxid using taxrate
    foreach ($taxratelist as $idx => $tr) {
        if ($tr['value'] === $default_taxrate) {
            $default_taxid = $idx;
            break;
        }
    }

    $id = isset($params['id']) ? ' id="' . $params['id'] . '"' : null;
    $name = isset($params['name']) ? ' name="' . $params['name'] . '"' : null;
    $selected = isset($params['selected']) ? $params['selected'] : $default_taxid;
    $value = ' value="' . $selected . '"';
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
    foreach ($params as $attname => $attvalue) {
        if (strpos($attname, 'data_') === 0) {
            $data_attributes .= ' ' . str_replace('_', '-', $attname) . '="' . $attvalue . '"';
        }
    }

    $data_attributes .= ' data-default-value="' . $selected . '"';

    $options = '';
    if (empty($taxratelist)) {
        $options .= '<option selected value="">' . trans('— no tax rates defined —') . '</option>';
    } else {
        foreach ($taxratelist as $tax) {
            $options .= '<option value="' . $tax['id'] . '" data-taxrate-value="' . $tax['value'] . '"'
            . LMSSmartyPlugins::tipFunction(array('text' => $tax['label']), $template)
            . ($tax['id'] == $selected ? ' selected' : null)
            . '>' . $tax['label'] . ' (' . $tax['value'] . '%)</option>';
        }
    }

    return $icon . '<select ' . $id . $name . $value . $class . $form . $tip_text . $visible . $required
        . $data_attributes . '>' . $options . '</select>';
}
