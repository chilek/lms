/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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
	$('#add-mac').click(function() {
		var key = $('.lms-ui-mac-address-selection .mac').length;
		$('table.lms-ui-mac-address-selection').append(
			'<tr id="mac' + key + '" class="mac">' +
			'<td style="width: 100%;">' +
			'<input type="text" id="mac-input-' + key + '" name="' + $(this).attr('data-field-prefix') + '[macs][' + key + ']" value="" ' +
			'placeholder="' + $t('MAC address') + '">' +
			'&nbsp;<span class="ui-icon ui-icon-closethick remove-mac"></span>' +
			'&nbsp;<a class="lms-ui-button mac-selector" ' +
			'title="' + $t('Click to select MAC from the list') + '"><i class="lms-ui-icon-next fa-fw"></i></a>' +
			'</td>' +
			'</tr>'
		);
	});

	$(document).on('click', '.mac-selector,.remove-mac', function() {
		if ($(this).is('.mac-selector')) {
			macchoosewin($(this).siblings('input')[0]);
		} else {
			var mac_row = $(this).closest('.mac');
			var other_mac_rows = mac_row.siblings('.mac');
			var dataNodeEmptyMac = mac_row.closest('table').attr('data-node-empty-mac');
			if (mac_row.index() || dataNodeEmptyMac.length || other_mac_rows.length) {
				mac_row.remove();
				if (!dataNodeEmptyMac.length && other_mac_rows.length) {
					other_mac_rows.first().find('input').prop('required', true);
				}
			} else {
				mac_row.find('input').val('');
			}
		}
	});
});
