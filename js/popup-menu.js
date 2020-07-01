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
    var popup_menu = $('#lms-ui-popup-menu');

    $(document).keydown(function(e) {
        if (e.key == 'Escape') {
            var dropdown_toggle = $('.lms-ui-dropdown-toggle.open');
            if (dropdown_toggle.length) {
                popup_menu.attr('aria-hidden', true);
                dropdown_toggle.removeClass('open');
                showMainScrollbars();
                popup_menu.css({
                    'left': '',
                    'top': ''
                });
            }
        }
    });


    $(document).click(function(e) {
        if (!$(e.target).is('.lms-ui-dropdown-toggle') && !$(e.target).closest('.lms-ui-dropdown-toggle').length &&
            !$(e.target).closest('#lms-ui-popup-menu').length &&
            popup_menu.is('[aria-hidden="false"]')) {
            popup_menu.attr('aria-hidden', true);
            $('.lms-ui-dropdown-toggle.open').removeClass('open');
            showMainScrollbars();
        }
    });

    $(window).resize(function() {
        var dropdown_toggle = $('.lms-ui-dropdown-toggle.open');
        if (dropdown_toggle.length) {
            if (!dropdown_toggle.is(':visible')) {
                popup_menu.attr('aria-hidden', true);
                dropdown_toggle.removeClass('open');
                popup_menu.css({
                    'left': '',
                    'top': ''
                });
            } else if (parseInt($(window).outerWidth()) >= 800) {
                showMainScrollbars();
                popup_menu.position({
                    my: "right top",
                    at: "left top",
                    of: dropdown_toggle
                });
            } else {
                hideMainScrollbars();
                popup_menu.css({
                    'left': '',
                    'top': ''
                });
            }
        }
    });

    popup_menu.find('.close-button').click(function() {
        popup_menu.attr('aria-hidden', true);
        $('.lms-ui-dropdown-toggle.open').removeClass('open');
        showMainScrollbars();
    });

    $('.lms-ui-dropdown-toggle').click(function() {
        var that = this;
        var html = '';
        $(this).siblings('.lms-ui-dropdown-buttons').find('.lms-ui-button' + ($(this).is('.secondary') ? '.secondary' : ''))
            .each(function() {
                var target_id = $(this).attr('id');
                if (!target_id) {
                    target_id = $(this).uniqueId().attr('id');
                }
                html += '<li data-target-id="' + target_id + '"><i class="' + $(this).find('i').attr('class') + ' fa-fw' +
                    '"></i>' + $(this).attr('data-title') + '</li>';
            });
        var title = $(this).closest('[data-popup-menu-title]').attr('data-popup-menu-title');
        $('#lms-ui-popup-menu-title').html(title ? title : '');
        $('#lms-ui-popup-menu-content ul').html(html).find('li').click(function() {
            $(that).toggleClass('open');
            popup_menu.attr('aria-hidden', false);
            $('#' + $(this).attr('data-target-id'))[0].click();
        });
        if (!$(this).is('.open')) {
            popup_menu.css({
                'left': '',
                'top': ''
            });
        }
        $(this).toggleClass('open');
        popup_menu.attr('aria-hidden', !$(this).is('.open'));
        //$('.lms-ui-dropdown-buttons').not(dropdown_buttons).removeClass('show');
        if ($(this).is('.open')) {
            if (parseInt($(window).outerWidth()) >= 800) {
                popup_menu.position({
                    my: "right top",
                    at: "left top",
                    of: that
                });
            } else {
                hideMainScrollbars();
                popup_menu.css({
                    'left': '',
                    'top': ''
                });
            }
        }
    });
});
