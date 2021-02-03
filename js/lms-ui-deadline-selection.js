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
    $('.lms-ui-deadline-selection').each(function() {
        init_datepickers($(this).find('.lms-ui-deadline-selection-date'));
        init_comboboxes($(this).find('.lms-ui-deadline-selection-days'));

        var cdate_elem = $($(this).attr('data-cdate-selector'));
        var deadline_date_elem = $(this).find('.lms-ui-deadline-selection-date');
        var deadline_days_elem = $(this).find('.lms-ui-deadline-selection-days');

        $(cdate_elem).change(function() {
            var ddt = new Date();
            var deadline = deadline_days_elem.scombobox('val');
            if (!deadline.match(/^[0-9]+$/)) {
                return;
            }
            ddt.setTime(Date.parse($(this).val()) + deadline * 86400 * 1000);
            deadline_date_elem.val(sprintf("%04d/%02d/%02d", ddt.getFullYear(), ddt.getMonth() + 1, ddt.getDate()));
        });

        $(deadline_date_elem).change(function() {
            if (!cdate_elem.val().length || !$(this).val().length) {
                return;
            }
            var cdt = new Date();
            var ddt = new Date();
            cdt.setTime(Date.parse(cdate_elem.val()));
            ddt.setTime(Date.parse($(this).val()));
            var diffTime = Math.abs(ddt - cdt);
            var diffDays = Math.ceil(diffTime / (1000 * 86400));
            deadline_days_elem.scombobox('val', diffDays);
        }).change();

        deadline_days_elem.scombobox('change', function () {
            var cdt = new Date();
            if (cdate_elem.val().length) {
                cdt.setTime(Date.parse(cdate_elem.val()));
            } else {
                cdate_elem.val(sprintf("%04d/%02d/%02d", cdt.getFullYear(), cdt.getMonth() + 1, cdt.getDate()));
            }
            var diffDays = deadline_days_elem.scombobox('val');
            if (!diffDays.match(/^[0-9]+$/)) {
                return;
            }
            var ddt = new Date();
            ddt.setTime(cdt.getTime() + parseInt(diffDays) * 86400 * 1000);
            deadline_date_elem.val(sprintf("%04d/%02d/%02d", ddt.getFullYear(), ddt.getMonth() + 1, ddt.getDate()));
        });
    });
});
