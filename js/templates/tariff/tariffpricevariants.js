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


const priceAction = '?m=tariffpricevariant';
const tariffpricevarianttable = $('#tariffpricevarianttable');
const tariffpricevarianttableHTML = ' #tariffpricevarianttable';
const tariffpricevarianttableHeader = $('#tariffpricevariants .lms-ui-tab-header');
const tariffpricevarianttableHeaderHTML = ' #tariffpricevariants .lms-ui-tab-header';

function getTariffPriceVariants() {
    tariffpricevarianttable.load(tariffpricevarianttableHTML);
    tariffpricevarianttableHeader.load(tariffpricevarianttableHeaderHTML);
    hideAddManagementTariffPriceVariant();
}

function addManagementTariffPriceVariant() {
    let quantityThresholdElem = $('#tariff-price-variant-threshold');
    let grossPriceElem = $('#tariff-price-variant-grossprice');
    let netPriceElem = $('#tariff-price-variant-netprice');

    $.ajax({
        url: priceAction + '&api=1&oper=add',
        async: true,
        method: 'POST',
        dataType: 'json',
        data: {
            tariff_id: tariffpricevariantTariffId,
            quantity_threshold: quantityThresholdElem.val(),
            gross_price: grossPriceElem.val(),
            net_price: netPriceElem.val()
        }
    }).done(function (data) {
        if (data.hasOwnProperty('id')) {
            getTariffPriceVariants();
        } else {
            if (data.hasOwnProperty('threshold_error')) {
                quantityThresholdElem.addClass('lms-ui-error').removeAttr('data-tooltip')
                    .attr('title', data.threshold_error);
            } else {
                quantityThresholdElem.removeClass('lms-ui-error').removeAttr('data-tooltip')
                    .attr('title', quantityThresholdElem.attr('data-old-tooltip'));
            }

            if (data.hasOwnProperty('gross_price_error')) {
                grossPriceElem.addClass('lms-ui-error').removeAttr('data-tooltip')
                    .attr('title', data.gross_price);
            } else {
                grossPriceElem.removeClass('lms-ui-error').removeAttr('data-tooltip')
                    .attr('title', grossPriceElem.attr('data-old-tooltip'));
            }

            if (data.hasOwnProperty('net_price_error')) {
                netPriceElem.addClass('lms-ui-error').removeAttr('data-tooltip')
                    .attr('title', data.net_price);
            } else {
                netPriceElem.removeClass('lms-ui-error').removeAttr('data-tooltip')
                    .attr('title', netPriceElem.attr('data-old-tooltip'));
            }
        }
    });
}

function updateManagementTariffPriceVariant(id) {
    let params = {};
    let quantityThresholdElem = $('#quantity_threshold_edit_'+id);
    let quantityThresholdValue = quantityThresholdElem.val();
    let grossPriceElem = $('#gross_price_edit_'+id);
    let grossPriceElemValue = grossPriceElem.val();
    let netPriceElem = $('#net_price_edit_'+id);
    let netPriceElemValue = netPriceElem.val();

    params.tariff_price_variant_id = id;
    params.quantity_threshold = quantityThresholdValue;
    params.gross_price = grossPriceElemValue;
    params.net_price = netPriceElemValue;

    $.ajax({
        url: priceAction + '&api=1&oper=edit',
        async: true,
        method: 'POST',
        dataType: 'json',
        data: {
            tariff_id: tariffpricevariantTariffId,
            tariff_price_variant_id: params.tariff_price_variant_id,
            quantity_threshold: params.quantity_threshold,
            gross_price: params.gross_price,
            net_price: params.net_price
        }
    }).done(function (data) {
        if (data.hasOwnProperty('id')) {
            getTariffPriceVariants();
        } else {
            if (data.hasOwnProperty('threshold_error')) {
                quantityThresholdElem.addClass('lms-ui-error').removeAttr('data-tooltip')
                    .attr('title', data.threshold_error);
            } else {
                quantityThresholdElem.removeClass('lms-ui-error').removeAttr('data-tooltip')
                    .attr('title', quantityThresholdElem.attr('data-old-tooltip'));
            }
            if (data.hasOwnProperty('gross_price_error')) {
                grossPriceElem.addClass('lms-ui-error').removeAttr('data-tooltip')
                    .attr('title', data.gross_price_error);
            } else {
                grossPriceElem.removeClass('lms-ui-error').removeAttr('data-tooltip')
                    .attr('title', grossPriceElem.attr('data-old-tooltip'));
            }
            if (data.hasOwnProperty('net_price_error')) {
                netPriceElem.addClass('lms-ui-error').removeAttr('data-tooltip')
                    .attr('title', data.net_price_error);
            } else {
                netPriceElem.removeClass('lms-ui-error').removeAttr('data-tooltip')
                    .attr('title', netPriceElem.attr('data-old-tooltip'));
            }
        }
    });
}

function delManagementTariffPriceVariant(priceVariantId) {
    $.ajax({
        url: priceAction + '&api=1&oper=del&id=' + priceVariantId,
        async: true,
        method: 'POST',
        dataType: 'json'
    }).done(function () {
        getTariffPriceVariants();
    });
}

function toggleEditManagementTariffPriceVariant(id) {
    let edit = $('#tariff_price_variant_edit_button_' + id).is(':visible');
    if (edit) {
        $('#tariffpricevarianttable [data-pricevariantid="' + id + '"]')
            .find('.tariff-price-variant-info-field').hide().end()
            .find('.tariff-price-variant-edit-field').show();

        $('#tariff_price_variant_cancel_button_' + id + ',#tariff_price_variant_save_button_' + id).show();
        $('#tariff_price_variant_edit_button_' + id).hide();
        $('#tariff_price_variant_edit_button_' + id).closest('.lms-ui-tab-table-row').find('.tariff-price-variant-edit-field').each(function () {
            $(this).attr('data-old-value', $(this).val());
        });

        let tariffPriceVariantNetPriceEditElem = $('#net_price_edit_' + id);
        let tariffPriceVariantGrossPriceEditElem = $('#gross_price_edit_' + id);
        tariffPriceVariantGrossPriceEditElem.prop('disabled', parseInt(tariffNetFlagValue));
        tariffPriceVariantNetPriceEditElem.prop('disabled', !parseInt(tariffNetFlagValue));

        tariffPriceVariantGrossPriceEditElem.on('change', function () {
            claculatePriceVariantFromGross($(this), tariffPriceVariantNetPriceEditElem);
        });

        tariffPriceVariantNetPriceEditElem.on('change', function () {
            claculatePriceVariantFromNet($(this), tariffPriceVariantGrossPriceEditElem);
        });

    } else {
        $('#tariffpricevarianttable [data-pricevariantid="' + id + '"]')
            .find('.tariff-price-variant-info-field').show().end()
            .find('.tariff-price-variant-edit-field').hide();

        $('#tariff_price_variant_cancel_button_' + id + ',#tariff_price_variant_save_button_' + id).hide();
        $('#tariff_price_variant_edit_button_' + id).show();
        $('#tariff_price_variant_edit_button_' + id).closest('.lms-ui-tab-table-row').find('.tariff-price-variant-edit-field').each(function () {
            $(this).val($(this).attr('data-old-value')).removeClass('lms-ui-error');
        });
    }
}

function showAddManagementTariffPriceVariant() {
    $('#add_management_tariff_price').show().find('.tariff-price-variant-edit-field').each(function () {
        $(this).attr('data-old-value', $(this).val()).attr('data-old-tooltip', $(this).attr('title'));
    }).first().focus();

    $('#management_tariff_price_variant_buttons').hide();
}

function hideAddManagementTariffPriceVariant() {
    $('#add_management_tariff_price').hide().find('.tariff-price-variant-edit-field').each(function () {
        $(this).val($(this).attr('data-old-value')).removeAttr('data-tooltip').removeClass('lms-ui-error')
            .attr('title', $(this).attr('data-old-tooltip'));
    });

    $('#management_tariff_price_variant_buttons').show();
}

$('#add_management_tariff_price').hide();

let tariffPriceVariantNetPriceElem = $('#tariff-price-variant-netprice');
let tariffPriceVariantGrossPriceElem = $('#tariff-price-variant-grossprice');
tariffPriceVariantGrossPriceElem.prop('disabled', parseInt(tariffNetFlagValue));
tariffPriceVariantNetPriceElem.prop('disabled', !parseInt(tariffNetFlagValue));

function claculatePriceVariantFromGross(grossPriceElem, netPriceElem) {
    let grossPriceElemVal = grossPriceElem.val();
    grossPriceElemVal = parseFloat(grossPriceElemVal.replace(/[\,]+/, '.'));

    if (!isNaN(grossPriceElemVal)) {
        let grossPrice = financeDecimals.round(grossPriceElemVal, 3);
        let netPrice = financeDecimals.round(grossPrice / (tariffTaxValue / 100 + 1), 3);

        netPrice = netPrice.toFixed(3).replace(/[\.]+/, ',');
        netPriceElem.val(netPrice);

        grossPrice = grossPrice.toFixed(3).replace(/[\.]+/, ',');
        grossPriceElem.val(grossPrice);
    } else {
        netPriceElem.val('');
        grossPriceElem.val('');
    }
}

function claculatePriceVariantFromNet(netPriceElem, grossPriceElem) {
    let netPriceElemVal = netPriceElem.val();
    netPriceElemVal = parseFloat(netPriceElemVal.replace(/[\,]+/, '.'));

    if (!isNaN(netPriceElemVal)) {
        let netPrice = financeDecimals.round(netPriceElemVal, 3);
        let grossPrice = financeDecimals.round(netPrice * (tariffTaxValue / 100 + 1), 3);

        grossPrice = grossPrice.toFixed(3).replace(/[\.]+/, ',');
        grossPriceElem.val(grossPrice);

        netPrice = netPrice.toFixed(3).replace(/[\.]+/, ',');
        netPriceElem.val(netPrice);
    } else {
        grossPriceElem.val('');
        netPriceElem.val('');
    }
}

tariffPriceVariantGrossPriceElem.on('change', function () {
    claculatePriceVariantFromGross($(this), tariffPriceVariantNetPriceElem);
});

tariffPriceVariantNetPriceElem.on('change', function () {
    claculatePriceVariantFromNet($(this), tariffPriceVariantGrossPriceElem);
});
