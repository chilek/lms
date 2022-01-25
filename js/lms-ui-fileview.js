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
    if (typeof (view_selector)=='undefined') {
        $(content).dialog(
            {
                width: window_width_size,
                height: window_height_size,
                title: file.name,
                modal: true
            }
        );
    } else {
        $("#" + view_selector).html(content).show();
    }

    switch (file.type) {
        case 'image/png':
        case 'image/jpg':
        case 'image/jpeg':
            break;
        default:
            $( content ).attr({
                'data': objUrl,
                'type': file.type
            });
            break;
    }
    window.URL ? URL.revokeObjectURL(objUrl) : webkitURL.revokeObjectURL(objUrl);

    return content;
}
