<?php


/**
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

function smarty_function_button(array $params, Smarty_Internal_Template $template)
{
    // optional - we want buttons without icon
    $icon = isset($params['icon']) ? $params['icon'] : null;
    $custom_icon = isset($icon) && (strpos($icon, 'lms-ui-icon-') === 0 || strpos($icon, 'fa') === 0);
    // optional - button by default,
    $type = isset($params['type']) ? $params['type'] : 'button';
    // optional - text tip,
    $tip = isset($params['tip']) ? trans($params['tip']) : null;
    // optional - button with icon only don't use label
    $label = isset($params['label']) ? trans($params['label']) : null;
    // optional - href attribute of link type button
    $href = isset($params['href']) ? trans($params['href']) : null;
    // optional - allow to easily attach event handler in jquery,
    $id = isset($params['id']) ? $params['id'] : null;
    // optional - additional css classes which are appended to class attribute
    $class = isset($params['class']) ? $params['class'] : null;
    // optional - allow to specify javascript code lauched after click,
    $onclick = isset($params['onclick']) && !empty($params['onclick']) ? $params['onclick'] : null;
    // optional - if open in new window after click
    $external = isset($params['external']) && $params['external'];
    // optional - data-resourceid attribute value
    $resourceid = isset($params['resourceid']) ? $params['resourceid'] : null;
    // optional - if element should be initially visible
    $visible = isset($params['visible']) ? $params['visible'] : true;
    // optional - keyboard shortcut
    $accesskey = isset($params['accesskey']) ? $params['accesskey'] : null;
    // optional - contents copied to clipboard
    $clipboard = isset($params['clipboard']) ? $params['clipboard'] : null;
    // optional - form id
    $form = isset($params['form']) ? $params['form'] : null;

    return '<' . ($type == 'link' ? 'a' . ($href ? ' href="' . $href . '"' : '') : 'button type="' . $type . '"')
            . ' class="' . ($type == 'link' ? '' : 'lms-ui-button') . ($icon && !$custom_icon ? ' lms-ui-button-' . $icon : '')
            . ($class ? ' ' . $class : '') . '"'
            . ($id ? ' id="' . $id . '"' : '') . ($onclick ? ' onclick="' . $onclick . '"' : '')
            . ($form ? ' form="' . $form . '"' : '')
            . ($tip ? ' title="' . $tip . '"' : '')
            . ($external ? ' rel="external"' : '')
            . ($resourceid ? ' data-resourceid="' . $resourceid . '"' : '')
            . ($clipboard ? ' data-clipboard-text="' . $clipboard . '"' : '')
            . ($visible ? '' : ' style="display: none;"')
            . ($accesskey ? ' accesskey="' . $accesskey . '"' : '') . '>'
            . ($icon ? '<i' . ($custom_icon ? ' class="' . $icon . '"' : '') . '></i>' : '')
            . ($label ? '<span class="lms-ui-label">' . $label . '</span>' : '') . '
		</' . ($type == 'link' ? 'a' : 'button') . '>';
}
