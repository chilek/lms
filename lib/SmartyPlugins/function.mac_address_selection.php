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

function smarty_function_mac_address_selection($params, $template)
{
    $result = '<table style="width: 100%;" class="lms-ui-mac-address-selection">';

    $form = $params['form'];
    $i = 0;
    foreach ($params['macs'] as $key => $mac) {
        $result .= '<tr id="mac' . $key . '" class="mac">
			<td style="width: 100%;">
				<input type="text" name="' . $form . '[macs][' . $key . ']" value="' . $mac . '" ' . (!$i ? 'required ' : '')
                    . LMSSmartyPlugins::tipFunction(array(
                            'text' => "Enter MAC address",
                            'trigger' => 'mac' . $key
                        ), $template) . '>
				<span class="ui-icon ui-icon-closethick remove-mac"></span>
				<a href="#" class="mac-selector"
					' . LMSSmartyPlugins::tipFunction(array(
                            'text' => "Click to select MAC from the list",
                        ), $template) . '>&raquo;&raquo;&raquo;</a>
			</td>
		</tr>';
        $i++;
    }

    $result .= '</table>
		<a href="#" id="add-mac" data-field-prefix="' . $form
            . '"><span class="ui-icon ui-icon-plusthick"></span> ' . trans("Add MAC address") . '</a>
		<script src="js/lms-ui-mac-address-selection.js"></script>';

    return $result;
}
