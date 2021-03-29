/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2021 LMS Developers
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

var macAction = '?m=netdevmac';
var macNetDevId = $('#mac-netdevid').val();
var netdevmactable = $('#netdevmactable');
var netdevmactableHTML = ' #netdevmactable';
var netdevmactableHeader = $('#netdevmacs .lms-ui-tab-header');
var netdevmactableHeaderHTML = ' #netdevmacs .lms-ui-tab-header';

function getMacs() {
    netdevmactable.load(netdevmactableHTML);
    netdevmactableHeader.load(netdevmactableHeaderHTML);
    hideAddManagementMac();
}

function showLabels(macid, label) {
    $.ajax({
        url: macAction + '&api=1&oper=showlabels',
        async: true,
        method: 'POST',
        dataType: 'json'
    }).done(function (data) {
        var selectHTML = $('<select name="label_edit_' + macid + '"' +
            'id="label_edit_' + macid + '"' +
            'class="lms-ui-combobox" required></select>');
        var macrow = $('#netdevmactable [data-macid="' + macid + '"] .label');

        macrow.prepend(selectHTML);

        var select = $('#label_edit_' + macid);

        $.each(data, function(index, value) {
            select.append('<option value="'+ value.label + '"'+ (label === value.label ? ' selected' : '') +'>' + value.label + '</option>');
        });

        init_comboboxes(select);
    });
}

function showAddLabels() {
    $.ajax({
        url: macAction + '&api=1&oper=showlabels',
        async: true,
        method: 'POST',
        dataType: 'json'
    }).done(function (data) {
        var selectHTML = $('<select form="netdevmacadd" name="label"' +
            'id="mac-label"' +
            'class="lms-ui-combobox" required></select>');

        var macrow = $('#add-mac-label');

        macrow.append(selectHTML);

        var select = $('#mac-label');

        $.each(data, function(index, value) {
            select.append('<option value="'+ value.label + '"'+ (index === 1 ? ' selected' : '') +'>' + value.label + '</option>');
        });

        init_comboboxes(select);
    });
}

function addManagementMac() {
    var macLabel = $('#mac-label');
    var macMac = $('#mac-mac');
    var macMain = $('#mac-main');

    $.ajax({
        url: macAction + '&api=1&oper=add',
        async: true,
        method: 'POST',
        dataType: 'json',
        data: {
            netdevid: macNetDevId,
            label: macLabel.find('input.scombobox-value').val(),
            mac: macMac.val(),
            main: (macMain.prop('checked') ? 1 : 0)
        }
    }).done(function (data) {
        if (data.hasOwnProperty('id')) {
            getMacs();
        } else {
            if (data.hasOwnProperty('mac_error')) {
                macMac.addClass('lms-ui-error').removeAttr('data-tooltip')
                    .attr('title', data.mac_error);
            } else {
                macMac.removeClass('lms-ui-error').removeAttr('data-tooltip')
                    .attr('title', macMac.attr('data-old-tooltip'));
            }

            if (data.hasOwnProperty('main_error')) {
                macMain.addClass('lms-ui-error').removeAttr('data-tooltip')
                    .attr('title', data.main_error);
            } else {
                macMain.removeClass('lms-ui-error').removeAttr('data-tooltip')
                    .attr('title', macMain.attr('data-old-tooltip'));
            }

            if (data.hasOwnProperty('label_error')) {
                macLabel.find('input.scombobox-display').addClass('lms-ui-error')
                    .removeAttr('data-tooltip').attr('title', data.label_error);
            } else {
                macLabel.find('input.scombobox-display').removeClass('lms-ui-error').removeAttr('data-tooltip')
                    .attr('title', macLabel.find('input.scombobox-display').attr('data-old-tooltip'));
            }
        }
    });
}

function updateManagementMac(id) {
    var params = {};
    var label = $('#label_edit_' + id);
    var labelValue = label.find('input.scombobox-value').val();
    var mac = $('#mac_edit_'+id);
    var macValue = mac.val();
    var main = $('#main_edit_'+id);
    var mainValue = (main.prop('checked') ? 1 : 0);

    params.label = labelValue;
    params.macid = id;
    params.mac = macValue;
    params.main = mainValue;

    $.ajax({
        url: macAction + '&api=1&oper=edit',
        async: true,
        method: 'POST',
        dataType: 'json',
        data: {
            netdevid: macNetDevId,
            macid: params.macid,
            label: params.label,
            mac: params.mac,
            main: params.main
        }
    }).done(function (data) {
        if (data.hasOwnProperty('id')) {
            getMacs();
        } else {
            if (data.hasOwnProperty('mac_error')) {
                mac.addClass('lms-ui-error').removeAttr('data-tooltip')
                    .attr('title', data.mac_error);
            } else {
                mac.removeClass('lms-ui-error').removeAttr('data-tooltip')
                    .attr('title', mac.attr('data-old-tooltip'));
            }

            if (data.hasOwnProperty('main_error')) {
                main.addClass('lms-ui-error').removeAttr('data-tooltip')
                    .attr('title', data.main_error);
            } else {
                main.removeClass('lms-ui-error').removeAttr('data-tooltip')
                    .attr('title', main.attr('data-old-tooltip'));
            }

            if (data.hasOwnProperty('label_error')) {
                label.find('input.scombobox-display').addClass('lms-ui-error')
                    .removeAttr('data-tooltip').attr('title', data.label_error);
            } else {
                label.find('input.scombobox-display').removeClass('lms-ui-error').removeAttr('data-tooltip')
                    .attr('title', label.find('input.scombobox-display').attr('data-old-tooltip'));
            }
        }
    });
}

function delManagementMac(macid) {
    $.ajax({
        url: macAction + '&api=1&oper=del&id=' + macid,
        async: true,
        method: 'POST',
        dataType: 'json'
    }).done(function () {
        getMacs();
    });
}

function toggleEditManagementMac(id) {
    var edit = $('#mac_edit_button_' + id).is(':visible');
    var editLabel = $('#label_' + id).text();
    if (edit) {
        $('#netdevmactable [data-macid="' + id + '"]')
            .find('.mac-info-field').hide().end()
            .find('.mac-edit-field').show();

        showLabels(id, editLabel.trim());

        $('#mac_cancel_button_' + id + ',#mac_save_button_' + id).show();
        $('#mac_edit_button_' + id).hide();
        $('#mac_edit_button_' + id).closest('.lms-ui-tab-table-row').find('.mac-edit-field').each(function () {
            $(this).attr('data-old-value', $(this).val());
        });
    } else {
        $('#netdevmactable [data-macid="' + id + '"]')
            .find('.mac-info-field').show().end()
            .find('.mac-edit-field').hide();

        $('#label_edit_' + id).remove();

        $('#mac_cancel_button_' + id + ',#mac_save_button_' + id).hide();
        $('#mac_edit_button_' + id).show();
        $('#mac_edit_button_' + id).closest('.lms-ui-tab-table-row').find('.mac-edit-field').each(function () {
            $(this).val($(this).attr('data-old-value')).removeClass('lms-ui-error');
        });
    }
}

function showAddManagementMac() {
    $('#add_management_mac').show().find('.mac-edit-field').each(function () {
        $(this).attr('data-old-value', $(this).val()).attr('data-old-tooltip', $(this).attr('title'));
    }).first().focus();

    showAddLabels();
    $('#management_mac_buttons').hide();
}

function hideAddManagementMac() {
    $('#add_management_mac').hide().find('.mac-edit-field').each(function () {
        $(this).val($(this).attr('data-old-value')).removeAttr('data-tooltip').removeClass('lms-ui-error')
            .attr('title', $(this).attr('data-old-tooltip'));
    });

    $('#mac-label').remove();
    $('#management_mac_buttons').show();
}

$('#add_management_mac').hide();