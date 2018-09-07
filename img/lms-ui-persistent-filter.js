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
		$('html,body').css('cursor', 'wait');
		$('.lms-ui-filter-modify-button,.lms-ui-filter-delete-button').addClass('lms-ui-button-disabled');
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
			},
			complete: function(data) {
				$('html,body').css('cursor', 'auto');
				$('.lms-ui-filter-modify-button,.lms-ui-filter-delete-button').removeClass('lms-ui-button-disabled');
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
		$('html,body').css('cursor', 'wait');
		$('.lms-ui-filter-modify-button,.lms-ui-filter-delete-button').addClass('lms-ui-button-disabled');
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
			},
			complete: function() {
				$('html,body').css('cursor', 'auto');
				$('.lms-ui-filter-modify-button,.lms-ui-filter-delete-button').removeClass('lms-ui-button-disabled');
			}
		});
		return false;
	});

	if ($('.lms-ui-persistent-filter .scombobox').length) {
		$('.lms-ui-persistent-filter .scombobox').scombobox('change', function () {
			var form = $(this).closest('form.lms-ui-persistent-filter');
			var selectelem = form.find('.scombobox')
			var selection = selectelem.scombobox('val');
			if (selection == -1 || selection.length < 5) {
				$('.lms-ui-filter-modify-button,.lms-ui-filter-delete-button').addClass('lms-ui-button-disabled');
				return false;
			}
			$('.lms-ui-filter-modify-button,.lms-ui-filter-delete-button').removeClass('lms-ui-button-disabled');
			var newname = true;
			selectelem.find('select option').each(function() {
				if ($(this).val() == selection) {
					newname = false;
				}
			});
			if (!newname) {
				form.find('[name="name"]').val(selection);
				form.attr('action', form.attr('action').replace('&api=1', ''));
				form.submit();
			}
		}, 'lms-ui');
	}
});
