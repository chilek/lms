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

	function selectToSlider(value) {
		return Math.floor((value / 100) * 60) + (value % 100);
	}

	function sliderToSelect(value) {
		return Math.floor(value / 60) * 100 + (value % 60);
	}

	$(options['slider-selector']).slider({
		range: true,
		min: 0,
		max: options['max'],
		step: options['step'],
		values: [
			selectToSlider($(options['start-selector']).val()),
			selectToSlider($(options['end-selector']).val())
		],
		slide: function (e, ui) {
			$(options['start-selector']).val(sliderToSelect(ui.values[0]));
			$(options['end-selector']).val(sliderToSelect(ui.values[1]));
		}
	});

	$(options['start-selector'] + ',' + options['end-selector']).change(function (e) {
		$(options['slider-selector']).slider('values', [
			selectToSlider($(options['start-selector']).val()),
			selectToSlider($(options['end-selector']).val())
		]);
	});

}
