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

function smarty_function_event_time_selection($params, $template) {
	$field_prefix = isset($params['field_prefix']) ? $params['field_prefix'] : 'event';
	$begin = isset($params['begin']) ? $params['begin'] : '';
	$end = isset($params['end']) ? $params['end'] : '';

	if ( !function_exists('smarty_function_tip') ) {
		foreach ( $template->getPluginsDir() as $v ) {
			if ( file_exists($v . 'function.tip.php') ) {
				require_once $v . 'function.tip.php';
			}
		}
	}

	return '
		<div class="lms-ui-event-time-container">
			<div>
				' . trans("Begin:") . ' <INPUT type="text" id="event-start" placeholder="' . trans("yyyy/mm/dd hh:mm")
					. '" name="' . $field_prefix . '[begin]" value="' . $begin . '" size="20" ' .
					smarty_function_tip(array(
						'class' => 'calendar-time',
						'text' => 'Enter date in YYYY/MM/DD hh:mm format (empty field means today) or click to choose it from calendar',
						'trigger' => 'date',
					), $template)
					. '>
				' . trans("End:") . ' <INPUT type="text" id="event-end" placeholder="' . trans("yyyy/mm/dd hh:mm")
					. '" name="' . $field_prefix . '[end]" value="' . $end . '" size="20" ' .
					smarty_function_tip(array(
						'class' => 'calendar-time',
						'text' => 'Enter date in YYYY/MM/DD hh:mm format (empty field means today) or click to choose it from calendar',
						'trigger' => 'enddate',
					), $template)
					. '>
			</div>
			<div>
				<div class="lms-ui-event-time-slider"></div>
			</div>
		</div>
		<script>

			$(function() {
				new eventTimeSlider({
					\'start-selector\': \'#event-start\',
					\'end-selector\': \'#event-end\',
					\'slider-selector\': \'.lms-ui-event-time-slider\',
					\'max\': 1410,
					\'step\': lmsSettings.eventTimeStep
				});
			});

		</script>';
}
