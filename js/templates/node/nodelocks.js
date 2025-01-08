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

function validateNodeLock(params) {
	const timeRegExp = /^(?<hour>[0-9]{2}):(?<minute>[0-9]{2})$/;
	var selectorSuffix = "";

	if (params.hasOwnProperty("nodeLockId")) {
		selectorSuffix += '[data-node-lock-id="' + params.nodeLockId + '"]';
	}

	var formElem = $('#' + params.formId);
	var fromElem = $('[form="' + params.formId + '"][name="time[from]"]' + selectorSuffix);
	var from = fromElem.val();
	var fromSec = null;
	var toElem = $('[form="' + params.formId + '"][name="time[to]"]' + selectorSuffix);
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
		return false;
	}

	fromElem.get(0).setCustomValidity(
		fromSec && toSec && fromSec >= toSec ?
			$t("'From' time should be earlier than 'to' time!")
			: ""
	);

	$('[form="' + params.formId + '"][name^="days"]' + selectorSuffix).get(0).setCustomValidity(
		$('[form="' + params.formId + '"][name^="days"]' + selectorSuffix + ":checked").length ?
			""
			: $t("No day was checked!")
	);

	if (!formElem.get(0).checkValidity()) {
		formElem.get(0).reportValidity();
		return false;
	}

	if (selectorSuffix.length) {
		$('[form="' + params.formId + '"][name^="days"]' + selectorSuffix).each(function() {
			formElem.find('[name="' + $(this).attr('name') + '"]').val($(this).prop('checked') ? "1" : "0");
		});
		formElem.find('[name="time[fromsec]"]').val(fromSec);
		formElem.find('[name="time[tosec]"]').val(toSec);
		formElem.find('[name="id"]').val(params.nodeLockId);
	} else {
		$('[form="' + params.formId + '"][name="time[fromsec]"]').val(fromSec);
		$('[form="' + params.formId + '"][name="time[tosec]"]').val(toSec);
	}

	return true;
}

function addNodeLock() {
	if (validateNodeLock({
		formId: "nodelockadd"
	})) {
		$('#nodelockaddlink').prop('disabled', true);
		$('#nodelockspanel #nodelocktable').html(
			$('#nodelockspanel .lms-ui-tab-hourglass-template').html());
		xajax_addNodeLock($("#nodelockadd").serialize());
	}
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

function updateNodeLock(nodeLockId) {
	if (validateNodeLock({
		formId: "nodelockedit",
		nodeLockId: nodeLockId
	})) {
		$('#nodelockaddlink').prop('disabled', true);
		$('#nodelockspanel #nodelocktable').html(
			$('#nodelockspanel .lms-ui-tab-hourglass-template').html());
		xajax_updateNodeLock($("#nodelockedit").serialize());
	}
}

$(function() {
	$("#nodelockspanel").on("click", ".delete-button", function () {
		//$('[form="nodelockadd"][name^="days"]').prop('checked', false);
		//$('[form="nocelockadd"]input[type="time"]').val("");
		delNodeLock($(this).closest("[data-node-lock-id]").attr("data-node-lock-id"));
	}).on("click", ".start-edit-button", function() {
		$(this).closest('.lms-ui-tab-table-row').find('.view-mode,.edit-mode').toggle();
	}).on("click", ".cancel-edit-button", function() {
		$(this).closest('.lms-ui-tab-table-row').find('.view-mode,.edit-mode').toggle();
		$('.edit-mode input').each(function() {
			if ($(this).is('[type="time"]')) {
				$(this).val($(this).attr("data-prev-value"));
			} else {
				$(this).prop('checked', $(this).attr("data-prev-checked") == "1");
			}
		});
	}).on("click", ".save-edit-button", function() {
		updateNodeLock($(this).closest("[data-node-lock-id]").attr("data-node-lock-id"));
	});
});

getNodeLocks();
