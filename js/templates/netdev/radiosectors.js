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

function getRadioSectors() {
	xajax_getRadioSectors();
}

function addRadioSector() {
	$('#radiosectoraddlink').prop('disabled', true);
	xajax_addRadioSector($('#radiosectoradd').serialize());
}

function delRadioSector(id) {
	$('#radiosectortable').prop('disabled', true);
	$('#radiosectorpanel #radiosectortable').html(
		$('#radiosectorpanel .lms-ui-tab-hourglass-template').html());
	xajax_delRadioSector(id);
}

function toggleEditRadioSector(id) {
	var elems = ['name', 'azimuth', 'width', 'altitude', 'rsrange', 'license', 'technology', 'type', 'frequency',
		'frequency_separator', 'frequency2', 'bandwidth', 'secret'];
	var edit = $('#edit_button_' + id).is(':visible');

	$.each(elems, function(index, elem) {
		if (edit) {
			$('#' + elem + '_' + id).hide();
			$('#' + elem + '_edit_' + id).show();
		} else {
			$('#' + elem + '_' + id).show();
			$('#' + elem + '_edit_' + id).hide();
		}
	});

	if (edit) {
		$('#cancel_button_' + id + ',#save_button_' + id).show();
		$('#edit_button_' + id).hide();
		$('#edit_button_' + id).closest('.lms-ui-tab-table-row').find('.radio-sector-edit-field').each(function() {
			$(this).attr('data-old-value', $(this).val());
		});
	} else {
		$('#cancel_button_' + id + ',#save_button_' + id).hide();
		$('#edit_button_' + id).show();
		$('#edit_button_' + id).closest('.lms-ui-tab-table-row').find('.radio-sector-edit-field').each(function() {
			$(this).val($(this).attr('data-old-value'));
		});
	}
}

function showAddRadioSector() {
	$('#add_radio_sector').show().find('.radio-sector-edit-field').each(function() {
		$(this).attr('data-old-value', $(this).val()).attr('data-old-tooltip', $(this).attr('title'));
	}).first().focus();

	$('#radio_sector_buttons').hide();
}

function hideAddRadioSector() {
	$('#add_radio_sector').hide().find('.radio-sector-edit-field').each(function() {
		$(this).val($(this).attr('data-old-value')).removeAttr('data-tooltip').removeClass('alert')
			.attr('title', $(this).attr('data-old-tooltip'));
	});
	$('#radio_sector_buttons').show();
}

function updateRadioSector(id) {
	var elems = ['name', 'azimuth', 'width', 'altitude', 'rsrange', 'license', 'technology', 'type', 'frequency',
		'frequency_separator', 'frequency2', 'bandwidth', 'secret'];
	var params = {};

	$('#radiosectortable').prop('disabled', true);
	$.each(elems, function(index, elem) {
		params[elem] = $('#' + elem + '_edit_' + id).val();
	});
	params.rsid = id;
	xajax_updateRadioSector(id, params);
}

function radioSectorResponse(errors) {
	if (!$.isArray(errors)) {
		$.each(errors, function (index, value) {
			$('#radiosectorpanel #' + index).addClass('alert')
				.removeAttr('data-tooltip').attr('title', value);
		});
	} else {
		$('#radiosectorpanel #radiosectortable').html(
			$('#radiosectorpanel .lms-ui-tab-hourglass-template').html());
	}
}

$('#add_radio_sector').hide();

getRadioSectors();
