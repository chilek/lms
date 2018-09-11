/*
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

function eventTimeSlider(options) {
	if (typeof(options) != 'object') {
		return null;
	}

	var valid = true;
	$.each([ 'start-selector', 'end-selector', 'slider-selector', 'step', 'max' ], function(index, value) {
		if (!options.hasOwnProperty(value) || !$(options[value]).length) {
			valid = false;
		}
	});
	if (!valid) {
		return null;
	}

	var start_input = $(options['start-selector']);
	var end_input = $(options['end-selector']);

	function setDateTimePickerStartRestrictions() {
		var startdt = start_input.datetimepicker('getValue');
		var enddt = end_input.datetimepicker('getValue');
		if (enddt === null) {
			return;
		}
		if (startdt > enddt) {
			start_input.datetimepicker('setOptions', {
				value: new Date(enddt)
			});
		}
/*
		start_input.datetimepicker('setOptions', {
			maxDate: new Date(enddt)
		});
*/
	}

	function setDateTimePickerEndRestrictions() {
		var startdt = start_input.datetimepicker('getValue');
		var enddt = end_input.datetimepicker('getValue');
		if (startdt === null) {
			return;
		}
		if (enddt < startdt) {
			end_input.datetimepicker('setOptions', {
				value: new Date(startdt)
			});
		}
		end_input.datetimepicker('setOptions', {
			minDate: new Date(startdt)
		});
	}

	function inputToSlider(input) {
		var time = input.datetimepicker('getValue');
		if (time) {
			return time.getHours() * 60 + time.getMinutes();
		} else {
			return 0;
		}
	}

	function sliderToInput(value, input) {
		var time = input.datetimepicker('getValue');
		time.setHours(Math.floor(value / 60))
		time.setMinutes(value % 60);
		input.datetimepicker({
			value: time
		});
	}

	function toggleSliderDrag(handleIndex) {
		if (handleIndex & 1) {
			start_input.toggleClass('lms-ui-dragslider-slave');
		}
		if (handleIndex & 2) {
			end_input.toggleClass('lms-ui-dragslider-slave');
		}
	}

	$(options['slider-selector']).dragslider({
		range: true,
		min: 0,
		max: options.max,
		step: options.step,
		rangeDrag: true,
		values: [
			inputToSlider(start_input),
			inputToSlider(end_input)
		],
		slide: function (e, ui) {
			sliderToInput(ui.values[0], start_input);
			sliderToInput(ui.values[1], end_input);
			switch (ui.handleIndex) {
				case 0:
					setDateTimePickerEndRestrictions();
					break;
				case 1:
					setDateTimePickerStartRestrictions();
					break;
				default:
					setDateTimePickerStartRestrictions();
					setDateTimePickerEndRestrictions();
			}
		},
		start: function (e, ui) {
			toggleSliderDrag(ui.handleIndex);
		},
		stop: function (e, ui) {
			toggleSliderDrag(ui.handleIndex);
		}
	});

	function RoundTime(item, type) {
		item.setOptions({
			value: new Date(Math.round(item.getValue().getTime() / 1000 / lmsSettings.eventTimeStep / 60) *
				lmsSettings.eventTimeStep * 60 * 1000)
		});
	}

	start_input.datetimepicker('setOptions', {
		onChangeDateTime: function() {
			RoundTime(this);
			setDateTimePickerEndRestrictions();
		}
	});
	end_input.datetimepicker('setOptions', {
		onChangeDateTime: function() {
			RoundTime(this);
			setDateTimePickerStartRestrictions();
		}
	});

	$(options['start-selector'] + ',' + options['end-selector']).change(function (e) {
		$(options['slider-selector']).dragslider('values', [
			inputToSlider(start_input),
			inputToSlider(end_input)
		]);
	});

	setDateTimePickerStartRestrictions();
	setDateTimePickerEndRestrictions();
}
