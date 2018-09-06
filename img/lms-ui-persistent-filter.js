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
	$('.lms-ui-filter-selection').change(function () {
		if ($(this).val() == -1) {
			$(this).next('.lms-ui-filter-name').show();
		} else {
			$(this).next('.lms-ui-filter-name').hide();
		}
	});

	$('.lms-ui-filter-modify-button').click(function () {
		var form = $(this).closest('form.lms-ui-persistent-filter');
		var selectelem = $(this).siblings('.lms-ui-filter-selection')
		var selection = selectelem.val();
		var name = $(this).siblings('.lms-ui-filter-name').val();
		if (!selection.length || (selection == -1 && name.length < 5)) {
			return false;
		}
		$.ajax(form.attr('action'), {
			method: "POST",
			data: {
				'persistent-filter': 1,
				api: 1,
				action: 'modify',
				name: (selection == -1 ? name : selection)
			},
			success: function (data) {
				selectelem.find('option:nth-child(n+3)').remove();
				$.each(data, function (index, value) {
					selectelem.append('<option value="' + value + '">' + value + '</option>');
				});
				selectelem.val(selection == -1 ? name : selection);
			}
		});
		return false;
	});

	$('.lms-ui-filter-delete-button').click(function () {
		var form = $(this).closest('form.lms-ui-persistent-filter');
		var selectelem = $(this).siblings('.lms-ui-filter-selection')
		var selection = selectelem.val();
		if (!selection.length || selection == -1) {
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
				selectelem.find('option:nth-child(n+3)').remove();
				$.each(data, function (index, value) {
					selectelem.append('<option value="' + value + '">' + value + '</option>');
				});
			}
		});
		return false;
	});

	$('.lms-ui-filter-apply-button').click(function () {
		var form = $(this).closest('form.lms-ui-persistent-filter');
		var name = $(this).siblings('.lms-ui-filter-selection').val();
		form.find('[name="name"]').val(name);
		form.attr('action', form.attr('action').replace('&api=1', ''));
		form.submit();
		return false;
	});
});
