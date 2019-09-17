/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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
    $('#delete-all-trusted-devices').click(function() {
        confirmDialog($t('Are you sure you want to remove all trusted devices?'), this).done(function() {
            location.href = '?m=' + $('#remove-trusted-device-module').val() + '&removetrusteddevices=1';
        });
        return false;
    });

    $('.delete-trusted-device').click(function() {
        confirmDialog($t('Are you sure you want to remove this trusted device?'), this).done(function() {
            location.href = '?m=' + $('#remove-trusted-device-module').val() +
                '&id=' + $('#user-id').val() + '&deviceid=' +
                $(this).closest('.lms-ui-tab-table-row').attr('data-trusted-device-id') + '&removetrusteddevices=1';
        });
        return false;
    });
});
