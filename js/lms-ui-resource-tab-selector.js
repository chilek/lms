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

function resourceTabSelectorChanged() {
    var tabs = [];
    $('[name="resource-tabs[]"] option').each(function() {
        tabs.push($(this).val() + ':' + ($(this).prop('selected') ? 1 : 0));
    });
    var params = {};
    params[$("#resource-tab-module").val() + "-resource-tabs"] = tabs.join(';');
    savePersistentSettings(params).done(function() {
        location.reload();
    });
}

(function() {
    var tabs = $("#resource-tab-states");
    var visible_tabs = [];
    var hidden_tabs = []
    $.each(tabs.length ? tabs.val().split(';') : [], function(index, tab_state) {
        var elem = tab_state.split(':');
        if (parseInt(elem[1])) {
            visible_tabs.push(elem[0]);
        } else {
            hidden_tabs.push(elem[0]);
        }
    });

    var options = '';
    $('.lms-ui-tab-container[data-label]').each(function () {
        var id = $(this).attr('id');
        if (!id) {
            return;
        }
        var visible = visible_tabs.indexOf(id) != -1 || hidden_tabs.indexOf(id) == -1;
        $("#" + id).toggle(visible);
        options += '<option value="' + id + '"' +
            (visible ? ' selected' : '') +
            '>' + $(this).attr('data-label') + '</option>';
    });

    if (options.length) {
        $(function() {
            $('#resource-tab-selector').html(options);

            init_multiselects("#resource-tab-selector");

            $('#lms-ui-resource-tab-selector-container').show();
        });
    }
})();
