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
	$('.container-add-button').click(function() {
		var addbutton = $(this);
		addbutton.closest('.lms-ui-tab-buttons').prop('disabled', true);
		var formdata = new FormData(this.form);
		formdata.delete(addbutton.parent().find('[type="file"]').attr('name'));
		$.ajax($(this.form).attr('action'), {
			type: "POST",
			contentType: false,
			dataType: "json",
			data: formdata,
			processData: false,
			success: function(data) {
				if (data.hasOwnProperty("url")) {
					location.href = data.url;
				}
				addbutton.closest('.lms-ui-tab-buttons').prop('disabled', false);
			},
			error: function() {
				addbutton.closest('.lms-ui-tab-buttons').prop('disabled', false);
			}
		});
		return false;
	});

	init_attachment_lists();
});
