<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

//function atip

function smarty_function_multi_location_box($params, $template)
{
    if (empty($params)) {
        $params = array();
    }

    if (!function_exists('smarty_function_location_box')) {
        foreach ($template->getPluginsDir() as $v) {
            if (file_exists($v . 'function.location_box.php')) {
                require_once $v . 'function.location_box.php';
            }
        }
    }

    if (empty($params['prefix'])) {
        $params['prefix'] = 'address';
    }

    // when use first time write script content
    if (!defined('MULTI_LOCATION_BOX')) {
        define('MULTI_LOCATION_BOX', 1);
        echo '<script type="text/javascript" src="js/multi_location_box.js"></script>';
    }

    if (!empty($params['addresses'])) {
        echo '<div class="multi-location-box">';
        echo '<table class="multi-location-table">';
        $i = 0;

        foreach ($params['addresses'] as $v) {
            $uid = uniqid();

            echo '<tr>';
            echo '<td class="valign-top"><span class="toggle-address" data-target="' . $uid . '" data-state="closed">&plus;</span></td>';
            echo '<td class="valign-top">';
            echo '<div style="padding-top: 2px;">' . $v['location'] . '</div>';

            echo '<div id="' . $uid . '" style="display: none;">';

            $v['prefix']      = $params['prefix'] . "[$i]";
            $v['select_type'] = 'on';
            ++$i;

            smarty_function_location_box($v, $template);

            echo '</div>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</table>';
        echo '<span class="lms-ui-button locbox-addnew">' ,trans('Add address'), ' &raquo;&raquo;&raquo;</span>';
        echo '</div>';
    }
}
