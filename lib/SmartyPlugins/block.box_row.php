<?php

/**
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

function smarty_block_box_row($params, $content, $template, $repeat) {
	if (!$repeat) {
		$id = isset($params['id']) ? $params['id'] : null;
		$icon = isset($params['icon']) ? $params['icon'] : null;
		$label = isset($params['label']) ? $params['label'] : null;
		$labelid = isset($params['labelid']) ? $params['labelid'] : null;
		$visible = (isset($params['visible']) && $params['visible']) || !isset($params['visible']);
		$class = isset($params['class']) ? $params['class'] : null;
		$icon_class = isset($params['icon_class']) ? $params['icon_class'] : null;
		$label_class = isset($params['label_class']) ? $params['label_class'] : null;
		$field_id = isset($params['field_id']) ? $params['field_id'] : null;
		$field_class = isset($params['field_class']) ? $params['field_class'] : null;

		return '
			<div' . ($id ? ' id="' . $id . '"' : '') . ' class="lms-ui-box-row' . ($class ? ' ' . $class : '') . '"'
					. ($visible ? '' : ' style="display: none;"') . '>
				<div class="lms-ui-box-row-icon' . ($icon_class ? ' ' . $icon_class : '') . '">
					' . ($icon ? (strpos($icon, '/') !== false ? '<IMG src="' . $icon . '" alt="">'
						: '<i class="' . $icon . '"></i>') : '') . '
				</div>
				<div class="lms-ui-box-row-label' . ($label_class ? ' ' . $label_class : '') . '">
					' . ($labelid ? '<label for="' . $labelid . '">' : '')
					. ($label ? trans($label) : '') . ($labelid ? '</label>' : '') . '
				</div>
				<div' . ($field_id ? ' id="' . $field_id . '"' : '')
					. ' class="lms-ui-box-row-field' . ($field_class ? ' ' . $field_class : '') . '">
					' . $content . '
				</div>
			</div>';
	}
}

?>
