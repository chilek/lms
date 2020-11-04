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
    $('.lms-ui-karma-container').each(function() {
        $(this).find('.lms-ui-karma-button').click(function() {
            if ($(this).is('.disabled')) {
                return;
            }
            var container = $(this).closest('.lms-ui-karma-container');
            container.find('.lms-ui-karma-button').addClass('disabled');
            var url = container.attr('data-handler') + '&api=1&oper=karma-' + ($(this).is('.lms-ui-karma-raise') ? 'raise' : 'lower') +
                '&karma-id=' + container.attr('data-id');
            $.ajax({
                url: url,
                dataType: 'json',
                success: function(data) {
                    var karma = parseInt(data.karma);
                    container.find('.lms-ui-counter').html(karma);
                    container.find('.lms-ui-karma-button').removeClass('disabled');
                    container.find('.lms-ui-icon-star').removeClass('red green').toggleClass('red', karma < 0).toggleClass('green', karma > 0);
                    if (data.hasOwnProperty('error')) {
                        alertDialog(data.error, container);
                    }
                }
            });
        });
    });
});
