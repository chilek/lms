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

function modalDialog(title, message, buttons, deferred) {
	$('#lms-ui-modal-dialog .message').html(message.replace("\n", '<br>'));

	$('#lms-ui-modal-dialog').dialog({
		autoOpen: true,
		modal: true,
		resizable: false,
		title: title,
		buttons: buttons,
		open: function() {
			var that = this;
			$('.ui-widget-overlay').bind('click', function () {
				$(that).dialog('close');
			});
		}
	});

	deferred.always(function() {
		$('#lms-ui-modal-dialog').dialog('destroy');
	});

	return deferred;
}

function alertDialog(message, context) {
	var deferred = $.Deferred();

	return modalDialog($t("<!dialog>Alert"), message,
		[
			{
				text: $t("OK"),
				icon: "ui-icon-check",
				class: "lms-ui-button",
				click: function() {
					$( this ).dialog( "close" );
					deferred.resolveWith(context);
				}
			}
		], deferred
	);
}

function confirmDialog(message, context) {
	var deferred = $.Deferred();

	context = typeof(context) === 'undefined' ? this : context;

	return modalDialog($t("<!dialog>Confirmation"), message,
		[
			{
				text: $t("Yes"),
				icon: "ui-icon-check",
				class: "lms-ui-button",
				click: function() {
					$( this ).dialog( "close" );
					deferred.resolveWith(context);
				}
			},
			{
				text: $t("No"),
				icon: "ui-icon-closethick",
				class: "lms-ui-button",
				click: function() {
					$( this ).dialog( "close" );
					deferred.rejectWith(context);
				}
			}
		], deferred
	);
}
