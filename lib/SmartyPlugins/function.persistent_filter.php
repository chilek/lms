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

function smarty_function_persistent_filter($params, $template) {
	$layout = $template->getTemplateVars('layout');
	$persistent_filters = $template->getTemplateVars('persistent_filters');
	$persistent_filter = $template->getTemplateVars('persistent_filter');

	$filters = '';
	foreach ($persistent_filters as $filter)
		$filters .= '<option value="' . $filter['value'] . '"' . ($filter['value'] == $persistent_filter ? ' selected' : '')
			. '>' . $filter['text'] . '</option >';

	return '<form method="post" class="lms-ui-persistent-filter" action="?m=' . $layout['module'] . '&persistent-filter=1&api=1">
		<input type="hidden" name="action" value="apply">
		<input type="hidden" name="name" value="">
		<div class="lms-ui-persistent-filter">
			' . trans("Persistent filter:") . '
			<button class="lms-ui-button lms-ui-button-icon lms-ui-filter-apply-button" >
			' . trans("<!filter>Apply") . '
			</button>
			<button class="lms-ui-button lms-ui-button-icon lms-ui-filter-modify-button" >
			' . trans("<!filter>Update") . '
			</button>
			<select class="lms-ui-filter-selection lms-ui-combobox">
				<option value="-1">' . trans("<!filter>- none -") . '</option>
				' . $filters . '
			</select>
			<button class="lms-ui-button lms-ui-button-icon lms-ui-filter-delete-button" >
				' . trans("<!filter>Delete") . '
			</button>
    	</div>
	</form>';
}
