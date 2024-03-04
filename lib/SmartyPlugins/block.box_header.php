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

function smarty_block_box_header($params, $content, $template, $repeat)
{
    if (!$repeat) {
        $id = $params['id'] ?? null;
        $multi_row = isset($params['multi_row']) && $params['multi_row'];
        $icon = $params['icon'] ?? null;
        $label = $params['label'] ?? null;
        $icon_class = $params['icon_class'] ?? null;
        $content_id = $params['content_id'] ?? null;

        if ($multi_row) {
            return '<div class="lms-ui-box-header-multi-row">'
                    . $content . '
				</div>';
        } else {
            return '
		<div' . ($id ? ' id="' . $id . '"' : '') . ' class="lms-ui-box-header">
		' . (isset($icon) ? (strpos($icon, '/') !== false ? '<IMG src="' . $icon . '" alt="">'
                    : '<i class="' . (strpos($icon, 'lms-ui-icon-') === 0 ? $icon : 'lms-ui-icon-' . $icon)
                    . (empty($icon_class) ? '' : ' ' . $icon_class) . '"></i>') : '')
                  . (isset($label) ? '<label' . (isset($content_id) ? ' for="' . $content_id . '"' : '') . '>' . trans($label) . '</label>' : '')
                  . $content . '
				</div>';
        }
    }
}
