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

$(function() {
	$('.lms-ui-filter-modify-button').click(function () {
		var form = $(this).closest('form.lms-ui-persistent-filter');
		var selectelem = form.find('.scombobox')
		var selection = selectelem.scombobox('val');
		if (selection == -1 || selection.length < 5) {
			return false;
		}
		$.ajax(form.attr('action'), {
			method: "POST",
			data: {
				'persistent-filter': 1,
				api: 1,
				action: 'modify',
				name: selection
			},
			success: function (data) {
				data.unshift({
					text: lmsMessages.filterNone,
					value: -1
				})
				selectelem.scombobox('fill', data);
				selectelem.scombobox('val', selection);
			}
		});
		return false;
	});

	$('.lms-ui-filter-delete-button').click(function () {
		var form = $(this).closest('form.lms-ui-persistent-filter');
		var selectelem = form.find('.scombobox')
		var selection = selectelem.scombobox('val');
		if (selection == -1 || selection.length < 5) {
			return false;
		}
		$.ajax(form.attr('action'), {
			method: "POST",
			data: {
				'persistent-filter': 1,
				api: 1,
				action: 'delete',
				name: selection
			},
			success: function (data) {
				data.unshift({
					text: lmsMessages.filterNone,
					value: -1
				})
				selectelem.scombobox('fill', data);
			}
		});
		return false;
	});

	$('.lms-ui-filter-apply-button').click(function () {
		var form = $(this).closest('form.lms-ui-persistent-filter');
		var selectelem = form.find('.scombobox')
		var selection = selectelem.scombobox('val');
		if (selection == -1 || selection.length < 5) {
			return false;
		}
		form.find('[name="name"]').val(selection);
		form.attr('action', form.attr('action').replace('&api=1', ''));
		form.submit();
		return false;
	});
});
