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

$(function() {
    $('.lms-ui-geocoding').each(function() {
        var elem = $(this);
        $.ajax({
            url: '?m=maplink&api=1&action=geocoding',
            async: true,
            method: 'POST',
            dataType: 'json',
            data: {
                address: elem.attr('data-address')
            },
            success: function (data) {
                if (data.hasOwnProperty('longitude') && data.hasOwnProperty('latitude')) {
                    elem.attr({
                        href: function (index, value) {
                            return value.replace('%longitude', data.longitude).replace('%latitude', data.latitude);
                        },
                        disabled: null,
                        title: elem.attr('data-tip'),
                        "data-title": null,
                    });
                }
            }
        });
    });
});
