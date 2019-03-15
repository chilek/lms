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

function smarty_function_date_period_preset(array $params, Smarty_Internal_Template $template) {
	$from_selector = isset($params['from']) ? $params['from'] : null;
	$to_selector = isset($params['to']) ? $params['to'] : null;
	$period = isset($params['period']) ? $params['period'] : null;
	if (!isset($period))
		$period = 'previous-month';
	if (!isset($from_selector) || !isset($to_selector))
		return;

	switch ($period) {
		case 'previous-month':
		default:
			$label = trans('previous month');
			break;
	}
	return '<button type="button" class="lms-ui-button lms-ui-button-date-period" data-from="'
		. htmlspecialchars($from_selector) . '" data-to="'
		. htmlspecialchars($to_selector) . '" period="' . $period . '">' . $label . '</button>';
}
