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

function visiblePanelSelectorChanged() {
    var visible_panels = [];
    $('[name="visible-panels[]"]:checked').each(function() {
        visible_panels.push($(this).val());
    });
    var params = {};
    params[$("#visible-panel-selector-module").val() + "-visible-panels"] = visible_panels.join(';');
    savePersistentSettings(params).done(function() {
        location.reload();
    });
}

(function() {
    var options = '';
    var visible_panels = $("#visible-panel-selector-selected");
    var visible_panels_selected = visible_panels.length ? visible_panels.val().split(';') : '';
    $('.lms-ui-tab-container[data-label]').each(function () {
        var id = $(this).attr('id');
        if (!id) {
            return;
        }
        var visible = !visible_panels.length || visible_panels_selected.indexOf(id) != -1;
        $("#" + id).toggle(visible);
        options += '<option value="' + id + '"' +
            (visible ? ' selected' : '') +
            '>' + $(this).attr('data-label') + '</option>';
    });

    if (options.length) {
        $(function() {
            $('#visible-panel-selector').html(options);

            init_multiselects("#visible-panel-selector");

            $('#lms-ui-visible-panel-selector-container').show();
        });
    }
})();
