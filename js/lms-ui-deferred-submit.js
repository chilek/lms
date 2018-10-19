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

function lmsDeferredSubmit(formElem) {
	this.submitTimer = null;
	this.formElem = formElem;
	var that = this;

	this.keyUpHandler = function() {
		if (this.submitTimer) {
			clearTimeout(this.submitTimer);
		}
		this.submitTimer = setTimeout(function () {
			that.formElem.submit();
		}, 500);
	}
}

$(function() {
	$('.lms-ui-deferred-submit').each(function () {
		var formelem = $(this).attr('form') ? $('form#' + $(this).attr('form')) : $(this).closest('form');
		var deferredSubmit = new lmsDeferredSubmit(formelem);
		$(this).on('keyup', deferredSubmit.keyUpHandler);
	});
});
