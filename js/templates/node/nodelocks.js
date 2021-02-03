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
	$('#nodelockaddlink').prop('disabled', true);
	$('#nodelockspanel #nodelocktable').html(
		$('#nodelockspanel .lms-ui-tab-hourglass-template').html());
	xajax_addNodeLock($('#nodelockadd').serialize());
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
		.find('select').val(0);
});

getNodeLocks();
