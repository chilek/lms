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

$(function() {
    $('.lms-ui-reset-to-defaults').click(function () {
        var target = $($(this).attr('data-target'));
        if (!target) {
            return;
        }
        var elems = $(target);
        if (!elems.length) {
            return;
        }
        elems.each(function(index, elem) {
            var jqElem = $(elem);
            var defaultValue = jqElem.attr('[data-default-value') || '';
            jqElem.val(defaultValue);
        });
    }).on('mouseenter mouseleave', function(e) {
        var target = $($(this).attr('data-target'));
        if (!target) {
            return;
        }
        var elems = $(target);
        if (!elems.length) {
            return;
        }
        elems.toggleClass('lms-ui-distinguished', e.type == 'mouseenter');
    });
});