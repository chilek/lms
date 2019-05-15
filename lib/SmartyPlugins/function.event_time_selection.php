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

function smarty_function_event_time_selection($params, $template)
{
    $field_prefix = isset($params['field_prefix']) ? $params['field_prefix'] : 'event';
    $begin = isset($params['begin']) ? $params['begin'] : '';
    $end = isset($params['end']) ? $params['end'] : '';
    $whole_days = isset($params['wholedays']) && $params['wholedays'];

    $legend_code = '<div class="lms-ui-event-time-legend">';
    for ($i = 0; $i <= 22; $i += 2) {
        $legend_code .= '<div class="lms-ui-event-time-legend-label">' . sprintf('%02d', $i) . ':00 &#8212;</div>
						<div class="lms-ui-event-time-legend-scale">-</div>';
    }
    $legend_code .= '</div>';

    return '
		<div class="lms-ui-event-time-container">
			<div class="lms-ui-event-time-top-panel">
				<div class="lms-ui-event-time-period">
					<div class="lms-ui-event-time-date">
						' . trans("Begin:") . ' <INPUT type="text" id="event-start" placeholder="' . trans("yyyy/mm/dd hh:mm")
                            . '" name="' . $field_prefix . '[begin]" value="' . $begin . '" size="14" ' .
                            Utils::tip(array(
                                'class' => 'calendar-time',
                                'text' => 'Enter date in YYYY/MM/DD hh:mm format (empty field means today) or click to choose it from calendar',
                                'trigger' => 'begin',
                            ), $template)
                            . ' required>
					</div>
					<div class="lms-ui-event-time-date">
						' . trans("End:") . ' <INPUT type="text" id="event-end" placeholder="' . trans("yyyy/mm/dd hh:mm")
                            . '" name="' . $field_prefix . '[end]" value="' . $end . '" size="14" ' .
                            Utils::tip(array(
                                'class' => 'calendar-time',
                                'text' => 'Enter date in YYYY/MM/DD hh:mm format (empty field means today) or click to choose it from calendar',
                                'trigger' => 'end',
                            ), $template)
                            . '>
					</div>
				</div>
				<div class="lms-ui-event-whole-days">
					<label>
						<input type="checkbox" class="lms-ui-event-whole-days-checkbox" name="' . $field_prefix . '[wholedays]" value="1"
							' . ($whole_days ? 'checked' : '') . '>
							' . trans("whole days") . '
					</label>
				</div>
			</div>
			<div class="lms-ui-event-time-bottom-panel">'
                . $legend_code .
                '<div class="lms-ui-event-time-slider"></div>
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
