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
	$('#add-mac').click(function() {
		var key = $('.mac').length;
		$('<tr id="mac' + key + '" class="mac">' +
			'<td style="width: 100%;">' +
			'<input type="text" name="' + $(this).attr('data-field-prefix') + '[macs][' + key + ']" value="" ' +
			'title="' + $t('Enter MAC address') + '">' +
			'&nbsp;<span class="ui-icon ui-icon-closethick remove-mac"></span>' +
			'&nbsp;<a href="#" class="mac-selector" ' +
			'title="' + $t('Click to select MAC from the list') + '">&raquo;&raquo;&raquo;</a>' +
			'</td>' +
			'</tr>').insertAfter($('.mac').last());
	});

	$(document).on('click', '.mac-selector,.remove-mac', function() {
		if ($(this).is('.mac-selector')) {
			macchoosewin($(this).siblings('input')[0]);
		} else {
			var mac_row = $(this).closest('.mac');
			if (mac_row.index()) {
				mac_row.remove();
			} else {
				mac_row.find('input').val('');
			}
		}
	});
});
