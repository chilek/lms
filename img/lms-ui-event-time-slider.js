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

	var start = $(options['start-selector']),
		end = $(options['end-selector']);

	function selectToSlider(value) {
		return Math.floor((value / 100) * 60) + (value % 100);
	}

	function sliderToSelect(value) {
		return Math.floor(value / 60) * 100 + (value % 60);
	}

	function toggleSliderDrag(handleIndex) {
		if (handleIndex & 1) {
			start.toggleClass('lms-ui-dragslider-slave');
		}
		if (handleIndex & 2) {
			end.toggleClass('lms-ui-dragslider-slave');
		}
	}

	$(options['slider-selector']).dragslider({
		range: true,
		min: 0,
		max: options.max,
		step: options.step,
		rangeDrag: true,
		values: [
			selectToSlider(start.val()),
			selectToSlider(end.val())
		],
		slide: function (e, ui) {
			start.val(sliderToSelect(ui.values[0]));
			end.val(sliderToSelect(ui.values[1]));
		},
		start: function (e, ui) {
			toggleSliderDrag(ui.handleIndex);
		},
		stop: function (e, ui) {
			toggleSliderDrag(ui.handleIndex);
		}
	});

	$(options['start-selector'] + ',' + options['end-selector']).change(function (e) {
		$(options['slider-selector']).slider('values', [
			selectToSlider(start-selector.val()),
			selectToSlider(end-selector.val())
		]);
	});

}
