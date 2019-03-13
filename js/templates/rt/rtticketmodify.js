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

	$('[name="ticket[owner]"]').change(function() {
		$('#assign-to-me').prop('checked', false);
	});

	$('#assign-to-me').change(function() {
		var select = $('[name="ticket[owner]"]');
		if ($(this).prop('checked')) {
			select.val($(this).attr('data-old-userid', select.val()).attr('data-userid'));
		} else {
			select.val($(this).attr('data-old-userid'));
		}
	});

	$('[name="ticket[queue]"]').change(function () {
		var newticket_notify = $(this).find(':selected').attr('data-newticket-notify');
		if (newticket_notify === undefined) {
			$('#customernotify-row').hide();
			$('#customernotify').attr('checked', false);
		} else {
			$('#customernotify-row').show();
			$('#customernotify').attr('checked', true);
		}
		xajax_GetCategories($(this).val());
	});

	var newticket_notify = $('[name="ticket[queue]"]').find(':selected').attr('data-newticket-notify');
	if (newticket_notify === undefined) {
		$('#customernotify-row').hide();
	} else {
		$('#customernotify-row').show();
	}
});

function change_customer(customer_selector, address_selector) {
	getCustomerAddresses($(customer_selector).val(), function (addresses) {
		customer_addresses.setAddressList(addresses);
		if (Object.keys(addresses).length == 1) {
			$('#customer_addresses').val($('#customer_addresses option:last-child').val());
			customer_addresses.refresh();
		}
		xajax_select_location($(customer_selector).val(), $(address_selector).val());
	});
}

function update_nodes(data) {
	var options = '<option value="">' + $t('- none -') + '</option>';
	$.each(data, function (k, v) {
		options += '<option value="' + v.id + '"' + (data.length == 1 ? ' selected' : '') + '>' + v.name + ': ' + v.location + '</option>';
	});
	$('.node-list').html(options);
	$('.node-row').toggle(data.length > 0);
}

var customer_addresses = new LmsUiIconSelectMenu("#customer_addresses", {
	change: function (event, ui) {
		xajax_select_location($('[name="ticket[custid]"]').val(), $(this).val());
	}
});

function initCustomerSelection(customerid, address_id) {
	getCustomerAddresses(customerid, function (addresses) {
		customer_addresses.setAddressList(addresses);

		if (customerid) {
			if (address_id) {
				$('#customer_addresses').val(address_id);
			} else if (Object.keys(addresses).length == 1) {
				$('#customer_addresses').val($('#customer_addresses option:last-child').val());
			}
		}

		customer_addresses.init();
	});
}
