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
	xajax_addManagementUrl($('#managementurladd').serialize());
}

function delManagementUrl(id) {
	xajax_delManagementUrl(id);
}

function toggleEditManagementUrl(id) {
	var edit = $('#edit_button_' + id).is(':visible');

	if (edit) {
		$('#managementurltable [data-urlid="' + id + '"]')
			.find('.url-info-field').hide().end()
			.find('.url-edit-field').show();

		$('#cancel_button_' + id + ',#save_button_' + id).show();
		$('#edit_button_' + id).hide();
		$('#edit_button_' + id).closest('.lms-ui-tab-table-row').find('.url-edit-field').each(function() {
			$(this).attr('data-old-value', $(this).val());
		});
	} else {
		$('#managementurltable [data-urlid="' + id + '"]')
			.find('.url-info-field').show().end()
			.find('.url-edit-field').hide();

		$('#cancel_button_' + id + ',#save_button_' + id).hide();
		$('#edit_button_' + id).show();
		$('#edit_button_' + id).closest('.lms-ui-tab-table-row').find('.url-edit-field').each(function() {
			$(this).val($(this).attr('data-old-value'));
		});
	}
}

function showAddManagementUrl() {
	$('#add_management_url').show().find('.url-edit-field').each(function() {
		$(this).attr('data-old-value', $(this).val()).attr('data-old-tooltip', $(this).attr('title'));
	}).first().focus();

	$('#management_url_buttons').hide();

}

function hideAddManagementUrl() {
	$('#add_management_url').hide().find('.url-edit-field').each(function() {
		$(this).val($(this).attr('data-old-value')).removeAttr('data-tooltip').removeClass('alert')
			.attr('title', $(this).attr('data-old-tooltip'));
	});
	$('#management_url_buttons').show();

}

function updateManagementUrl(id) {
	var params = {};

	$('#managementurltable [data-urlid="' + id + '"] .url-edit-field').each(function() {
		params[$(this).attr('id').replace(/_edit_[0-9]+$/, '')] = $(this).val();
	});
	params.urlid = id;

	xajax_updateManagementUrl(id, params);
}

function managementUrlErrors(errors) {
	$.each(errors, function (index, value) {
		$('#managementurlspanel #' + index).addClass('alert')
			.removeAttr('data-tooltip').attr('title', value);
	});
}

$('#add_management_url').hide();
