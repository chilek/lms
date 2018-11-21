$(function () {
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
    $('#nodes').html(options);
    if (data.length) {
        $('#node-row').show();
    } else {
        $('#node-row').hide();
    }
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
