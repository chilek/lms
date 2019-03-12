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
	$('[name="ticket[requestor_userid]').change(function () {
		if ($(this).val() == '0') {
			$('.indicated-person').show();
		} else {
			$('.indicated-person').hide();
		}
	}).change();

	$('#assign-to-me').change(function() {
		var select = $('[name="ticket[owner]"]');
		if ($(this).prop('checked')) {
			select.val($(this).attr('data-old-userid', select.val()).attr('data-userid'));
		} else {
			select.val($(this).attr('data-old-userid'));
		}
	});
});
