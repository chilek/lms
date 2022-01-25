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

function lmsFileView(file) {
    if (typeof(file) == 'undefined') {
        alert("404, Error: no file to view");
        return;
    }

    var url = window.URL ? URL : webkitURL;
    var objUrl = url.createObjectURL(file);
    var content;
    var object;

    ///put file in object if not image
    switch (file.type) {
        case 'image/png':
        case 'image/jpg':
        case 'image/jpeg':
            content = file.contentElem;
            break;
        default:
            object = $('<object/>').attr({
                data: objUrl,
                type: file.type
            });
            content = $('<div/>').append(object);
            break;
    }

    $(content).dialog({
        dialogClass: "lms-ui-fileview-dialog",
        width: $(window).width() * 0.8,
        height: $(window).height() * 0.8,
        title: file.name,
        modal: true
    });

    url.revokeObjectURL(objUrl);
}
