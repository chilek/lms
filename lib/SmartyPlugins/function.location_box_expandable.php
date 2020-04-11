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

function smarty_function_location_box_expandable($params, $template)
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

    if (!function_exists('smarty_function_button')) {
        foreach ($template->getPluginsDir() as $v) {
            if (file_exists($v . 'function.button.php')) {
                require_once $v . 'function.button.php';
            }
        }
    }

    // set default prefix
    if (empty($params['data']['prefix'])) {
        $params['data']['prefix'] = 'address';
    }

    echo '<div class="location-box-expandable"'
        . ' data-node-use-counter="' . (isset($params['data']['node_use_counter']) ? $params['data']['node_use_counter'] : '0') . '"'
        . ' data-netdev-use-counter="' . (isset($params['data']['netdev_use_counter']) ? $params['data']['netdev_use_counter'] : '0') . '"'
        . ' data-netnode-use-counter="' . (isset($params['data']['netnode_use_counter']) ? $params['data']['netnode_use_counter'] : '0') . '">';

    $uid = uniqid();
    $location_str = $params['data']['location_address_type'] == BILLING_ADDRESS ? ''
        : (empty($params['data']['location_name']) ? '' : $params['data']['location_name'] . ', ');

    $location_str .= $params['data']['location']
        ? ($params['data']['teryt']
            ? trans('$a (TERRIT)', $params['data']['location']) : $params['data']['location'])
        : '...';

    $title = '';

    switch ($params['data']['location_address_type']) {
        case POSTAL_ADDRESS:
            $title = trans('postal address');
            break;
        case BILLING_ADDRESS:
            $title = trans('billing address');
            break;
        case LOCATION_ADDRESS:
            $title = trans('location/recipient address');
            break;
        case DEFAULT_LOCATION_ADDRESS:
            $title = trans('default location address');
            break;
    }

    echo '<div class="address-full"
                title="' . $title . '"
                data-target="' . $uid . '"
                data-state="' . (isset($params['data']['show']) ? 'opened' : 'closed') . '">' .
                $location_str . '</div>';

    if (isset($params['data']['show'])) {
        echo '<div id="' . $uid . '">';
    } else {
        echo '<div id="' . $uid . '" style="display: none;">';
    }

    echo '<div style="padding: 3px 0; position: relative;">';

    static $usage_messages = array(
        'node_use_counter' => 'assigned to <strong>$a</strong> nodes',
        'netdev_use_counter' => 'assigned to <strong>$a</strong> network devices',
        'netnode_use_counter' => 'assigned to <strong>$a</strong> network nodes',
    );
    if (isset($params['data']['use_counter'])) {
        echo '<div>';
        $usages = array();
        foreach (array('node_use_counter', 'netdev_use_counter', 'netnode_use_counter') as $field_name) {
            if (!empty($params['data'][$field_name])) {
                $usages[] = trans($usage_messages[$field_name], $params['data'][$field_name]);
            }
        }
        if (!empty($usages)) {
            echo implode(', ', $usages);
        }
        echo '</div>';
    }

    echo '</div>';

    if (isset($params['data']['location_address_type'])) {
        echo '<input type="hidden" value="' . $params['data']['location_address_type']  . '" name="' . $params['data']['prefix'] . '[location_address_type]" data-address="address_type">';
    } else {
        echo '<input type="hidden" value="' . LOCATION_ADDRESS .                          '" name="' . $params['data']['prefix'] . '[location_address_type]" data-address="address_type">';
    }

    $params['data']['buttons'] = smarty_function_button(array('icon' => 'clear', 'tip' => 'Clear', 'class' => 'clear-location-box'), $template);
    if (isset($params['data']['delete_button'])) {
        $params['data']['buttons'] .= smarty_function_button(array('icon' => 'trash', 'tip' => 'Delete', 'class' => 'delete-location-box'), $template);
    }
    if (isset($params['data']['billing_address_button'])) {
        $params['data']['buttons'] .= smarty_function_button(array('icon' => 'home', 'tip' => 'Copy from billing address', 'class' => 'copy-address', 'data_type' => BILLING_ADDRESS), $template);
    }
    if (isset($params['data']['post_address_button'])) {
        $params['data']['buttons'] .= smarty_function_button(array('icon' => 'mail', 'tip' => 'Copy from post address', 'class' => 'copy-address', 'data_type' => POSTAL_ADDRESS), $template);
    }

    smarty_function_location_box($params['data'], $template);

    echo '</div>';
    echo '</div>';
}
