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

function smarty_function_persistent_filter($params, $template)
{
    $layout = $template->getTemplateVars('layout');
    $persistent_filters = $template->getTemplateVars('persistent_filters');
    $persistent_filter = $template->getTemplateVars('persistent_filter');

    if(!empty($persistent_filters) && is_array($persistent_filters))
    {
        foreach ($persistent_filters as $key => $row) {
            $text[$key] = $row['text'];
        }
        array_multisort($text, SORT_ASC, $persistent_filters);
    }

    $filters = '';
    foreach ($persistent_filters as $filter) {
        $filters .= '<option value="' . $filter['value'] . '"' . ($filter['value'] == $persistent_filter ? ' selected' : '')
            . '>' . $filter['text'] . '</option >';
    }

    return '
		<div class="lms-ui-persistent-filter">
			<select class="lms-ui-filter-selection lms-ui-combobox" title="' . trans("<!filter>Select filter") . '">
				<option value="-1">' . trans("<!filter>- none -") . '</option>
				' . $filters . '
			</select>
			<button class="lms-ui-button lms-ui-filter-modify-button"'
                . ($persistent_filter == -1 || empty($persistent_filter) ? ' disabled' : '') . ' title="'
                . trans("<!filter>Update") . '"><i class="lms-ui-icon-add"></i>
			</button>
			<button class="lms-ui-button lms-ui-filter-delete-button" title="'
                . trans("<!filter>Delete") . '"><i class="lms-ui-icon-trash"></i>
			</button>
    	</div>
	</form>';
}
