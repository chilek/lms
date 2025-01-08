/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

function getNodeLocks() {
	xajax_getNodeLocks();
}

function addNodeLock() {
	const timeRegExp = /^(?<hour>[0-9]{2}):(?<minute>[0-9]{2})$/;
	var formElem = $('#nodelockadd');
	var fromElem = $('[form="nodelockadd"][name="time[from]"]');
	var from = fromElem.val();
	var fromSec = null;
	var toElem = $('[form="nodelockadd"][name="time[to]"]');
	var to = toElem.val();
	var toSec = null;
	var timeComponents;

	if (from.length) {
		timeComponents = from.match(timeRegExp);
		if (timeComponents !== null && timeComponents.hasOwnProperty("groups")) {
			fromSec = timeComponents.groups.hour * 3600 + timeComponents.groups.minute * 60;
		} else {
			fromElem.get(0).setValidity({
				patternMismatch: true
			})
		}
	} else {
		fromSec = 0;
	}

	if (to.length) {
		timeComponents = to.match(timeRegExp);
		if (timeComponents !== null && timeComponents.hasOwnProperty("groups")) {
			toSec = timeComponents.groups.hour * 3600 + timeComponents.groups.minute * 60;
		} else {
			toElem.get(0).setValidity({
				patternMismatch: true
			})
		}
	} else {
		toSec = 0;
	}

	if (fromSec === null || toSec === null) {
		return;
	}

	fromElem.get(0).setCustomValidity(
		fromSec && toSec && fromSec >= toSec ?
				$t("'From' time should be earlier than 'to' time!")
				: ""
	);

	$('[id^="lockdays"]').get(0).setCustomValidity(
		$('[id^="lockdays"]:checked').length ?
			""
			: $t("No day was checked!")
	);

	if (!$("#nodelockadd").get(0).checkValidity()) {
		$("#nodelockadd").get(0).reportValidity();
		return;
	}

	$('[name="time[fromsec]"]').val(fromSec);
	$('[name="time[tosec]"]').val(toSec);

	$('#nodelockaddlink').prop('disabled', true);
	$('#nodelockspanel #nodelocktable').html(
		$('#nodelockspanel .lms-ui-tab-hourglass-template').html());
	xajax_addNodeLock(formElem.serialize());
}

function delNodeLock(id) {
	$('#nodelocktable').prop('disabled', true);
	$('#nodelockspanel #nodelocktable').html(
		$('#nodelockspanel .lms-ui-tab-hourglass-template').html());
	xajax_delNodeLock(id);
}

function toggleNodeLock(id) {
	$('#nodelocktable').prop('disabled', true);
	$('#nodelockspanel #nodelocktable').html(
		$('#nodelockspanel .lms-ui-tab-hourglass-template').html());
	xajax_toggleNodeLock(id);
}

$("#nodelockspanel .delete-button").click(function() {
	$(this).parent().find('[id*="lockdays_"]').prop('checked', false).end()
		.find('input[type="time"]').val("");
});

getNodeLocks();
