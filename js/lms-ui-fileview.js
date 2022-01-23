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

function lmsFileView(file, view_containerid) {
    if (typeof (file) == 'undefined') {
        alert("404, Error: no file to view");
        return;
    }

    var objUrl = (window.URL ? URL : webkitURL).createObjectURL(file);
    let content = '';
    let window_height_size = '';
    let window_width_size = '';

    ///put file in object if not image
    switch (file.type) {
        case 'image/png':
        case 'image/jpg':
        case 'image/jpeg':
            content = file.contentElem;
            window_height_size = 'auto';
            window_width_size = 'auto';
            break;
        default:
            content = document.createElement("object");
            window_height_size = window.innerHeight*0.9;
            window_width_size = window.innerWidth*0.9;
            break;
    }

    ///show in popup or use inline container
    if (typeof (view_containerid) == 'undefined') {
        $(content).dialog(
            {
                width: window_width_size,
                height: window_height_size,
                title: file.name,
                modal: true
            }
        );
    } else {
        $("#" + view_containerid).html(content);
        document.getElementById(view_containerid).style.display = 'inline';
    }

    switch (file.type) {
        case 'image/png':
        case 'image/jpg':
        case 'image/jpeg':
            break;
        default:
            content.setAttribute('data', objUrl);
            content.setAttribute('type', file.type);
            break;
    }
    URL.revokeObjectURL(objUrl);
}
