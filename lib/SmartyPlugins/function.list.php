<?php

/**
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

function smarty_function_list(array $params, Smarty_Internal_Template $template)
{

    $id = isset($params['id']) ? $params['id'] : 'list';
    $visible = !isset($params['visible']) || ConfigHelper::checkValue($params['visible']);
    $tipid = isset($params['tipid']) ? $params['tipid'] : 'list-tip';
    $tip = isset($params['tip']) ? $params['tip'] : trans('Select elements using suggestions');
    $items = isset($params['items']) && !empty($params['items']) ? $params['items'] : null;
    $field_name_pattern = isset($params['field_name_pattern']) ? $params['field_name_pattern'] : 'list[%id%]';
    $item_content = isset($params['item_content']) && !empty($params['item_content']) ? $params['item_content']
        : function ($item) {
            if (isset($item['name'])) {
                return sprintf('#%06d', $item['id']) . ' <a href="?m=list&id=' . $item['id'] . '">' . $item['name'] . '</a>';
            } else {
                return '<a href="?m=list&id=' . $item['id'] . '">' . sprintf('#%06d', $item['id']) . '</a>';
            }
        };

    if (isset($items)) {
        $item_text = '';
        if (isset($items['id'])) {
            $items = array($items);
        }
        foreach ($items as $item) {
            if (!empty($item)) {
                if (is_callable($item_content)) {
                    $content = $item_content($item);
                } else {
                    $template->smarty->ext->_tplFunction->callTemplateFunction($template, $item_content, array('item' => $item), true);
                    $content = $template->smarty->ext->_capture->getBuffer($template, 'item_content_result');
                }
                $item_text .= '<li data-item-id="' . $item['id'] . '">
                    <input type="hidden" name="' . str_replace('%id%', $item['id'], $field_name_pattern)
                    . '" value="' . $item['id'] . '">
                    <i class="lms-ui-icon-delete lms-ui-list-unlink"></i>' . $content . '</li>';
            }
        }
    }
    return '<div id = "' . $id . '" class="lms-ui-list-container"' . ($visible ? '' : ' style="display: none;"') . '>
		<div class="lms-ui-list-suggestion-container">'
            . smarty_function_button(array(
                'type' => 'link',
                'class' => 'lms-ui-item-suggestion-button',
                'icon' => 'edit',
                'href' => '#',
            ), $template)
            . '<input type="text" class="lms-ui-list-suggestion"'
                . (isset($tip) && isset($tipid) ? smarty_function_tip(array('text' => $tip, 'trigger' => $tipid), $template) : '') . '>
        </div>
		<ul class="lms-ui-list"' . (isset($items) ? '' : ' style="display: none;"') . '>
		    ' . (isset($items) ? $item_text : ''). '
		</ul>
	</div>';
}
