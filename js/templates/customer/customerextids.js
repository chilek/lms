/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2023 LMS Developers
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

const extidAction = '?m=customerextidhelper';
const customerextidtable = $('#customerextidtable');
const customerextidtableHTML = ' #customerextidtable';
const customerextidtableHeader = $('#customerextids .lms-ui-tab-header');
const customerextidtableHeaderHTML = ' #customerextids .lms-ui-tab-header';

function getCustomerExtids() {
    customerextidtable.load(customerextidtableHTML);
    customerextidtableHeader.load(customerextidtableHeaderHTML);
    hideAddManagementCustomerExtid();
}

function addManagementCustomerExtid() {
    let extIdElem = $('#customer-extid-ext_id');
    let serviceProviderElem = $('#customer-extid-providerid');

    $.ajax({
        url: extidAction + '&api=1&oper=add',
        async: true,
        method: 'POST',
        dataType: 'json',
        data: {
            customer_id: customerId,
            ext_id: extIdElem.val(),
            service_provider_id: serviceProviderElem.val()
        }
    }).done(function (data) {
        if (data.hasOwnProperty('result') && data.result === 1) {
            getCustomerExtids();
        } else {
            if (data.hasOwnProperty('provider_error')) {
                serviceProviderElem.addClass('lms-ui-error').removeAttr('data-tooltip')
                    .attr('title', data.provider_error);
            } else {
                serviceProviderElem.removeClass('lms-ui-error').removeAttr('data-tooltip')
                    .attr('title', serviceProviderElem.attr('data-old-tooltip'));
            }
        }
    });
}

function updateManagementCustomerExtid(seviceProviderId, extId) {
    let extIdElem = $('#extid-edit-' + seviceProviderId + extId);
    let serviceProviderElem = $('#extid-providerid-' + seviceProviderId + extId);
    let oldserviceProviderVal = $('#extid-providerid-' + seviceProviderId + extId).attr('data-old-providerid');
    let oldExtidVal = $('#extid-edit-' + seviceProviderId + extId).attr('data-old-extid');

    $.ajax({
        url: extidAction + '&api=1&oper=edit',
        async: true,
        method: 'POST',
        dataType: 'json',
        data: {
            customer_id: customerId,
            ext_id: extIdElem.val(),
            old_ext_id: oldExtidVal,
            service_provider_id: serviceProviderElem.val(),
            old_service_provider_id: oldserviceProviderVal
        }
    }).done(function (data) {
        if (data.hasOwnProperty('result') && data.result === 1) {
            getCustomerExtids();
        } else {
            if (data.hasOwnProperty('provider_error')) {
                serviceProviderElem.addClass('lms-ui-error').removeAttr('data-tooltip')
                    .attr('title', data.provider_error);
            } else {
                serviceProviderElem.removeClass('lms-ui-error').removeAttr('data-tooltip')
                    .attr('title', serviceProviderElem.attr('data-old-tooltip'));
            }
        }
    });
}

function delManagementCustomerExtid(seviceProviderId, extId) {
    $.ajax({
        url: extidAction + '&api=1&oper=del',
        async: true,
        method: 'POST',
        dataType: 'json',
        data: {
            customer_id: customerId,
            ext_id: extId,
            service_provider_id: seviceProviderId
        }
    }).done(function () {
        getCustomerExtids();
    });
}

function showServiceProviders(serviceproviderId, extId) {
    $.ajax({
        url: extidAction + '&api=1&oper=showextids',
        async: true,
        method: 'POST',
        dataType: 'json',
        data: {
            customer_id: customerId
        }
    }).done(function (data) {
        let selectHTML = $('<select name="serviceprovider-edit-' + serviceproviderId + extId + '"' +
            'id="extid-providerid-' + serviceproviderId + extId +  '"' +
            'data-old-providerid="' + serviceproviderId + '"' +
            'required>' +
            '</select>');

        let extidrow = $('#customerextidtable [data-extid="' + serviceproviderId + extId + '"] .serviceproviderid');

        extidrow.prepend(selectHTML);

        let select = $('#extid-providerid-' + serviceproviderId + extId);

        $.each(data, function(index, value) {
            select.append('<option value="'+ value.id + '"'+ (serviceproviderId == value.id ? ' selected' : '') +'>' + value.name + '</option>');
        });
    });
}

function showAddServiceProviders() {
    $.ajax({
        url: extidAction + '&api=1&oper=showextids',
        async: true,
        method: 'POST',
        dataType: 'json',
        data: {
            customer_id: customerId
        }
    }).done(function (data) {
        let selectHTML = $('<select form="customerextidadd" name="serviceprovider_id"' +
            'id="customer-extid-providerid"' +
            'required>' +
            '<option value="" selected>' + $t("— select —") +
            '</option></select>');

        let extidrow = $('#add-extid-providerid');

        extidrow.append(selectHTML);

        let select = $('#customer-extid-providerid');

        $.each(data, function(index, value) {
            select.append('<option value="'+ value.id + '">' + value.name + '</option>');
        });
    });
}

function toggleEditManagementCustomerExtid(serviceproviderId, extId) {
    let edit = $('#customer-extid-edit-button-' + serviceproviderId + extId).is(':visible');
    if (edit) {
        $('#customerextidtable [data-extid="' + serviceproviderId + extId + '"]')
            .find('.customer-extid-info-field').hide().end().find('.customer-extid-edit-field').show();

        showServiceProviders(serviceproviderId, extId);

        $('#customer-extid-cancel-button-' + serviceproviderId + extId + ', #customer-extid-save-button-' + serviceproviderId + extId).show();
        $('#customer-extid-edit-button-' + serviceproviderId + extId).hide();
        $('#customer-extid-edit-button-' + serviceproviderId + extId).closest('.lms-ui-tab-table-row').find('.customer-extid-edit-field').each(function () {
            $(this).attr('data-old-value', $(this).val());
        });
    } else {
        $('#customerextidtable [data-extid="' + serviceproviderId + extId + '"]')
            .find('.customer-extid-info-field').show().end().find('.customer-extid-edit-field').hide();

        $('#extid-providerid-' + serviceproviderId + extId).remove();

        $('#customer-extid-cancel-button-' + serviceproviderId + extId + ',#customer-extid-save-button-' + serviceproviderId + extId).hide();
        $('#customer-extid-edit-button-' + serviceproviderId + extId).show();
        $('#customer-extid-edit-button-' + serviceproviderId + extId).closest('.lms-ui-tab-table-row').find('.customer-extid-edit-field').each(function () {
            $(this).val($(this).attr('data-old-value')).removeClass('lms-ui-error');
        });
    }
}

function showAddManagementCustomerExtid() {
    $('#add-management-customer-extid').show().find('.customer-extid-edit-field').each(function () {
        $(this).attr('data-old-value', $(this).val()).attr('data-old-tooltip', $(this).attr('title'));
    }).first().focus();

    showAddServiceProviders();
    $('#customer-extid-buttons').hide();
}

function hideAddManagementCustomerExtid() {
    $('#add-management-customer-extid').hide().find('.customer-extid-edit-field').each(function () {
        $(this).val($(this).attr('data-old-value')).removeAttr('data-tooltip').removeClass('lms-ui-error')
            .attr('title', $(this).attr('data-old-tooltip'));
    });

    $('#customer-extid-providerid').remove();
    $('#customer-extid-buttons').show();
}

$('#add-management-customer-extid').hide();
