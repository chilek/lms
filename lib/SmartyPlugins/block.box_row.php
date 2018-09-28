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
		$icon = $params['icon'];
		$label = $params['label'];
		$labelid = isset($params['labelid']) ? $params['labelid'] : null;

		return '
			<div' . ($id ? ' id="' . $id . '"' : '') . ' class="lms-ui-box-row">
				<div class="lms-ui-box-row-icon">
					' . (strpos($icon, '/') !== false ? '<IMG src="' . $icon . '" alt="">'
						: '<i class="' . $icon . '"></i>') . '
				</div>
				<div class="lms-ui-box-row-label">
					' . ($labelid ? '<label for="' . $labelid . '">' : '') . trans($label) . ($labelid ? '</label>' : '') . '
				</div>
				<div class="lms-ui-box-row-field">
					' . $content . '
				</div>
			</div>';
	}
}

?>
