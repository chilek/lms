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

$(function() {
	$('.toggle-file-list').click(function() {
		var containerid = parseInt($(this).attr('data-container-id'));
		var elem = $('#files-' + containerid);
		elem.toggle();
		$(this).html('<img src="img/' + (elem.is(':visible')  ? 'desc' : 'asc') + '_order.gif">');
	});

	$('.container-view,.container-edit').click(function() {
		var row = $(this).closest('.lms-ui-tab-table-row');
		row.find('.container-view,.container-edit').hide();
		row.find('.container-modify,.container-save,.container-cancel').show();
		var description = row.find('.container-modify > input');
		description.attr('data-old-value', description.val()).removeClass('alert').focus();
	});

	$('.container-modify').keydown(function(e) {
		switch (e.key) {
			case 'Enter':
				$(this).closest('.lms-ui-tab-table-row').find('.container-save').click();
				break;
			case 'Escape':
				$(this).closest('.lms-ui-tab-table-row').find('.container-cancel').click();
				break;
		}
	});

	$('.container-cancel').click(function() {
		var row = $(this).closest('.lms-ui-tab-table-row');
		row.find('.container-view,.container-edit').show();
		row.find('.container-modify,.container-save,.container-cancel').hide();
		var description = row.find('.container-modify > input');
		description.val(description.attr('data-old-value'));
	});

	$('.container-save').click(function() {
		var row = $(this).closest('.lms-ui-tab-table-row');
		var description = row.find('.container-modify > input');
		if (description.attr('data-old-value') != description.val()) {
			var form = $('#filecontainer-form');
			$.ajax({
				url: form.attr('action') + '&id=' + row.attr('data-id'),
				type: form.attr('method'),
				data: {
					description: description.val()
				},
				success: function (data) {
					if (data.hasOwnProperty('error')) {
						description.addClass('alert').attr('title', data.error);
					} else {
						row.find('.container-view,.container-edit').show();
						row.find('.container-modify,.container-save,.container-cancel').hide();
						row.find('.container-view').html(description.val().length ? description.val() : '---');
					}
				}
			});
		}
	});

	$('.container-del').click(function() {
		confirmDialog($t("Are you sure you want to delete this file container?"), this).done(function() {
			location.href = $(this).attr('href');
		});
		return false;
	});

});
