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

function customerCompanySelectionDialog(companies, context) {
    var deferred = $.Deferred();

    context = typeof(context) === 'undefined' ? null : context;

    var message = '<table id="company-selection-table" class="lmsbox lms-ui-background-cycle">';
    $.each(companies, function(index, company) {
        message += '<tr>';
        message += '<td>' + escapeHtml(company.lastname) + '</td>';
        message += '<td><input type="radio" name="company" value="' + index + '"' + (index ? '' : 'checked') + '></td>';
        message += '</tr>'
    });
    message += '</table>';

    $('#lms-ui-modal-dialog').on('click', '#company-selection-table td:first-child', function() {
        $(this).next().find('input').click();
    });

    return modalDialog($t("<!dialog>Confirmation"), message,
        [
            {
                text: $t("Select"),
                icon: "ui-icon-check",
                class: "lms-ui-button",
                click: function() {
                    $( this ).dialog( "close" );
                    var companyIndex = parseInt($('#lms-ui-modal-dialog input:checked').val());
                    if (context) {
                        deferred.resolveWith(context, [ companies[companyIndex] ]);
                    } else {
                        deferred.resolve([ companies[companyIndex] ]);
                    }
                }
            },
            {
                text: $t("Cancel"),
                icon: "ui-icon-closethick",
                class: "lms-ui-button",
                click: function() {
                    $( this ).dialog( "close" );
                    if (context) {
                        deferred.rejectWith(context);
                    } else {
                        deferred.reject();
                    }
                }
            }
        ], deferred, context
    );
}
