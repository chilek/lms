/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

function lmsFileView(file, view_selector) {
    if (typeof(file) == 'undefined') {
        alert("404, Error: no file to view");
        return;
    }

    var url = window.URL ? URL : webkitURL;
    var objUrl = url.createObjectURL(file);
    var object;
    var dialogOptions = {
        dialogClass: "lms-ui-fileview-dialog",
        width: $(window).width() * 0.8,
        title: file.name,
        modal: true,
        close: function() {
            url.revokeObjectURL(objUrl);
            $(this).dialog('destroy').remove();
        }
    };

    ///put file in object if not image
    switch (file.type) {
        case 'image/png':
        case 'image/jpg':
        case 'image/jpeg':
            object = $(file.contentElem || file.imgElem).clone();
            break;
        default:
            object = $('<object/>').attr({
                data: objUrl,
                type: file.type
            });
            dialogOptions.height = $(window).height() * 0.8;
            break;
    }

    if (!view_selector) {
        $('<div/>').append(object).dialog(dialogOptions);
    } else {
        var target = $(document.getElementById(view_selector));
        if (!target.length) {
            url.revokeObjectURL(objUrl);
            return;
        }
        var previousUrl = target.data('fileview-url');
        target.empty().append(object).removeClass('hidden')
            .addClass('attachment-loaded lms-ui-fileview-container').data('fileview-url', objUrl);
        if (previousUrl) {
            url.revokeObjectURL(previousUrl);
        }
    }
}
