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
        var input = container.find('.lms-ui-customer-select input');
        var select = container.find('select');
        var customerid = parseInt(input.val());
        var customername = input.attr('data-customer-name');
        var button = container.find('.lms-ui-customer-search-button');
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
            if (customername) {
                input.on('blur focus input', function () {
                    var timer;
                    var elem = $(this);
                    if (elem.val() != elem.attr('data-prev-value')) {
                        timer = elem.data('timer');
                        if (timer) {
                            clearTimeout(timer);
                        }
                        elem.data('timer', setTimeout(function () {
                            getCustomerName(elem[0]);
                            elem.attr('data-prev-value', elem.val());
                            elem.removeData('timer');
                        }, 500));
                    }
                });

                if (customerid) {
                    getCustomerNameDeferred(input[0]);
                }
            }
        }

        button.on('click', function() {
            return customerchoosewin(input.get(0));
        });
    });
});
