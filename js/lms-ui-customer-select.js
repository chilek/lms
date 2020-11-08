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

$(function() {
    $('.lms-ui-customer-select-container').each(function () {
        var container = $(this);
        var version = parseInt(container.attr('data-version'));
        var input = container.find('.lms-ui-customer-select-customerid');
        var suggestionInput = container.find('.lms-ui-customer-select-suggestion-input');
        var select = container.find('select');
        var customerId = parseInt(input.val());
        var customerName = input.attr('data-customer-name');
        var customerNameLink = container.find('.lms-ui-customer-select-name')
        var button = container.find('.lms-ui-customer-function-button');
        var selectChange = false;

        if (select.length) {
            input.on('change focus', function () {
                if (selectChange) {
                    return;
                }
                var elem = $(this);
                if (elem.val() != elem.attr('data-prev-value')) {
                    if (input.val()) {
                        select.val(input.val()).trigger('change');
                    }

                    elem.attr('data-prev-value', elem.val());
                }
            });
            select.on('change', function() {
                if (select.val()) {
                    selectChange = true;
                    input.val(select.val()).change();
                    selectChange = false;
                }
            });
        } else {
            if (customerName) {
                var timer;
                input.on('blur focus input', function () {
                    var elem = $(this);
                    if (elem.val() != elem.attr('data-prev-value')) {
                        if (timer) {
                            clearTimeout(timer);
                        }
                        timer = setTimeout(function () {
                            getCustomerName(elem[0]);
                            elem.attr('data-prev-value', elem.val());
                            timer = 0;
                        }, 500);
                    }
                });

                if (customerId) {
                    getCustomerNameDeferred(input[0]);
                }
            }
        }

        if (version == 2) {
            suggestionInput.one('focus', function() {
                new AutoSuggest({
                    form: suggestionInput[0].form,
                    elem: suggestionInput[0],
                    uri: '?m=quicksearch&mode=customer&ajax=1&api=1&what=',
                    suggestionContainer: container.find('.lms-ui-customer-select-suggestion-container'),
                    onSubmit: function (data) {
                        suggestionInput.val('');
                        customerNameLink.find('a').attr('href', data.action).html(data.name);
                        input.val(data.id).trigger('change');
                    }
                });
            });
        }

        button.on('click', function() {
            if ($(this).find('.lms-ui-icon-search').length) {
                return customerchoosewin(input.get(0));
            } else {
                customerNameLink.find('a').attr('href', '').html('');
                suggestionInput.val('');
                input.val(0).trigger('change');
            }
        });
    });
});
