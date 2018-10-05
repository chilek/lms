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

function getManagementUrls() {
	xajax_getManagementUrls();
}

function addManagementUrl() {
	$('#managementurladdlink').prop('disabled', true);
	xajax_addManagementUrl($('#managementurladd').serialize());
}

function delManagementUrl(id) {
	$('#managementurltable').prop('disabled', true);
	$('#managementurlspanel #managementurltable').html(
		$('#managementurlspanel .lms-ui-tab-hourglass-template').html());
	xajax_delManagementUrl(id);
}

function toggleEditManagementUrl(id) {
	var elems = ['url', 'comment'];
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
	} else {
		$('#cancel_button_' + id + ',#save_button_' + id).hide();
		$('#edit_button_' + id).show();
	}
}

function showAddManagementUrl() {
	var elems = ['url', 'comment'];

	$.each(elems, function(index, elem) {
		$('#' + elem).val('').off('mouseover');
	});

	$('#management_url_buttons').hide();
	$('#add_management_url').show();
	$('#url').focus();
}

function hideAddManagementUrl() {
	$('#add_management_url').hide();
	$('#management_url_buttons').show();
	$('#managementurlspanel #url,#comment').removeClass('alert')
		.filter('#url').removeAttr('data-tooltip').attr('title', $t("Enter management URL"));
}

function updateManagementUrl(id) {
	var elems = ['url', 'comment'];
	var params = {};

	$('#managementurltable').prop('disabled', true);
	$.each(elems, function(index, elem) {
		params[elem] = $('#' + elem + '_edit_' + id).val();
	});
	params.urlid = id;
	xajax_updateManagementUrl(id, params);
}

function managementUrlResponse(errors) {
	if (!$.isArray(errors)) {
		$.each(errors, function (index, value) {
			$('#managementurlspanel #' + index).addClass('alert')
				.removeAttr('data-tooltip').attr('title', value);
		});
	} else {
		$('#managementurlspanel #managementurltable').html(
			$('#managementurlspanel .lms-ui-tab-hourglass-template').html());
	}
}

$('#add_management_url').hide();

getManagementUrls();
