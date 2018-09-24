document.forms['ticket'].elements['ticket[subject]'].focus();

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

function change_customer() {
    getCustomerAddresses($('[name="ticket[custid]"]').val(), function (addresses) {
        customer_addresses.setAddressList(addresses);
        if (Object.keys(addresses).length == 1) {
            $('#customer_addresses').val($('#customer_addresses option:last-child').val());
            customer_addresses.refresh();
        }
        xajax_select_location($('[name="ticket[custid]"]').val(), $('[name="ticket[address_id]"]').val());
    });
}

function update_nodes(data) {
    var options = '<option value="">{trans("- none -")}</option>';
    $.each(data, function (k, v) {
        options += '<option value="' + v.id + '"' + (data.length == 1 ? ' selected' : '') + '>' + v.name + ': ' + v.location + '</option>';
    });
    $('[name="ticket[nodeid]"]').html(options);
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

getCustomerAddresses({intval($customerid)}, function (addresses) {
    customer_addresses.setAddressList(addresses);

    {
        if $customerid}
    {
        if $ticket.address_id}
    $('#customer_addresses').val({$ticket.address_id
} )
    ;
    {else
    }
    if (Object.keys(addresses).length == 1) {
        $('#customer_addresses').val($('#customer_addresses option:last-child').val());
    }
    {
        /if}
        {
            /if}

            customer_addresses.init();
        }
    );

$(function () {
    //$('[name="ticket[deadline]"]').attr('autocomplete', 'new-password');
});