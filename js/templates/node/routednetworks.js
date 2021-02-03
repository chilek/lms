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
	$('#add-routed-networks').click(function() {
		if ($("[name='routednetworkid[]'] option:selected").length) {
			$('form#routednetworks').submit();
		} else {
			return false;
		}
	});

	$('#delete-routed-networks').click(function() {
		if ($(this).closest('div.lms-ui-multi-check').find('input:checked').length) {
			confirmDialog($t("Are you sure, you want to remove routed network to node assignments?"), this).done(function() {
				$('form#routednetworks').attr('action', '?m=routednetworks&action=delete').submit();
			});
		}
	});
});
