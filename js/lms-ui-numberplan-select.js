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

$(function () {

    $('.lms-ui-numberplan-container').each(function () {
        let planElem = $(this);
        let doctypeElemId = planElem.attr('data-doctype-selector');
        let customerElemId = planElem.attr('data-customer-selector');
        let cdateElemId = planElem.attr('data-cdate-selector');
        let number = planElem.find('.lms-ui-numberplan-number input');
        let selectElem = planElem.find('.lms-ui-numberplan-plan select');

        selectElem.on('change', function () {
            number.val('');
        })

        if (doctypeElemId) {
            $(doctypeElemId).on('change', function () {
                let documentType = $(this).val();
                let customerId = $(customerElemId).val();
                let cdate = $(cdateElemId).val();
                number.val('');
                getNumberPlans(planElem, documentType, customerId, cdate);
            });
        }

        if (customerElemId) {
            $(customerElemId).on('change', function () {
                let customerId = $(this).val();
                let documentType = $(doctypeElemId).val();
                let cdate = $(cdateElemId).val();
                getNumberPlans(planElem, documentType, customerId, cdate);
            });
        }

        if (cdateElemId) {
            $(cdateElemId).on('change', function () {
                let cdate = $(this).val();
                let documentType = $(doctypeElemId).val();
                let customerId = $(customerElemId).val();
                getNumberPlans(planElem, documentType, customerId, cdate);
            });
        }

        getNumberPlans(planElem, $(doctypeElemId).val(), $(customerElemId).val(), $(cdateElemId).val());
    });

    function getNumberPlans(planElem, documentType, customerId, cdate) {
        if (!documentType) {
            documentType = planElem.attr('data-plan-document-type');
        }
        if (!customerId) {
            customerId = planElem.attr('data-plan-customer-id');
        }
        let selectElem = planElem.find('.lms-ui-numberplan-plan select');
        let currentPlanId = selectElem.val();

        let data = $.extend({ documentType: documentType }, customerId ? { customerID: customerId } : {}, cdate ? { cdate: cdate } : {});
        $.ajax('?m=numberplanhelper', {
            async: true,
            method: 'POST',
            dataType: 'json',
            data: data
        })
            .done( function (data) {
                let html = '';
                let options = '';
                let alreadySelected = false;
                selectElem.html(html);

                if(!$.isEmptyObject(data)) {
                    if (Object.keys(data).length > 1) {
                        options += '<option value="" disabled hidden>' + $t('— select —') + '</option>';
                    }
                    $.each(data, function(key, item) {
                        let isDefault = parseInt(item.isdefault) !== 0;
                        if (isDefault) {
                            if (alreadySelected) {
                                isDefault = false;
                            } else {
                                alreadySelected = true;
                            }
                        }
                        options += '<option value="' + item.id + '"' + (isDefault ? ' selected data-default="1"' : '') + '>';
                        options += item.nextNumber + ' (' + item.period_name + ')';
                        options += '</option>';
                    });
                } else {
                    options += '<option value="" selected>' + $t('— select —') + '</option>';
                }
                html += options;
                selectElem.html(html);

                let selectElemOptions = selectElem.find('option');
                selectElemOptions.each(function () {
                    if (parseInt($(this).val()) === parseInt(currentPlanId)) {
                        selectElem.find('option[value="' + currentPlanId + '"]').prop('selected', true);
                        return;
                    }
                })
            });
    }

});
