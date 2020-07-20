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

function modalDialog(title, message, buttons, deferred, context) {
	var position = { my: "center", at: "center", of: window };
	var mobile = $('body').is('.lms-ui-mobile');

	$('#lms-ui-modal-dialog .message').html(message.replace("\n", '<br>'));

	if (context) {
		position = { my: "left bottom", at: "left top", of: context };
	}

	$('#lms-ui-modal-dialog').dialog({
		autoOpen: true,
		modal: true,
		resizable: false,
		title: title.replace(/<![^>]*>/g, ''),
		buttons: buttons,
		classes: {
			"ui-dialog": "lms-ui-modal-dialog-wrapper lms-ui-popup"
		},
		position: mobile ? null : position,
		create: function() {
			enableFullScreenPopup();
		},
		open: function() {
			var that = this;
			$('.ui-widget-overlay').bind('click', function () {
				$(that).dialog('close');
			});
			if (mobile) {
				$(that).closest('.ui-dialog').css({
					'width': '',
					'left': '',
					'top': ''
				}).find('.ui-dialog-titlebar-close').html('<i class="lms-ui-icon-hide"></i>');
			} else {
				$(that).closest('.ui-dialog').find('.ui-dialog-titlebar-close').html(
					'<span class="ui-button-icon ui-icon ui-icon-closethick"></span><span class="ui-button-icon-space"></span>'
				);
			}
		},
		destroy: function() {
			disableFullScreenPopup();
		}
	});

	deferred.always(function() {
		$('#lms-ui-modal-dialog').dialog('destroy');
	});

	return deferred;
}

function alertDialog(message, context) {
	var deferred = $.Deferred();

	context = typeof(context) === 'undefined' ? null : context;

	return modalDialog($t("<!dialog>Alert"), message,
		[
			{
				text: $t("OK"),
				icon: "ui-icon-check",
				class: "lms-ui-button",
				click: function() {
					$( this ).dialog( "close" );
					if (context) {
						deferred.resolveWith(context);
					} else {
						deferred.resolve();
					}
				}
			}
		], deferred, context
	);
}

function confirmDialog(message, context) {
	var deferred = $.Deferred();

	context = typeof(context) === 'undefined' ? null : context;

	return modalDialog($t("<!dialog>Confirmation"), message,
		[
			{
				text: $t("Yes"),
				icon: "ui-icon-check",
				class: "lms-ui-button",
				click: function() {
					$( this ).dialog( "close" );
					if (context) {
						deferred.resolveWith(context);
					} else {
						deferred.resolve();
					}
				}
			},
			{
				text: $t("No"),
				icon: "ui-icon-closethick",
				class: "lms-ui-button",
				click: function() {
					$( this ).dialog( "close" );
					if (context) {
						deferred.rejectWith(context);
					} else {
						deferred.reject();
					}
				}
			}
		], deferred, context
	);
}
