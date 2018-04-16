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

function smarty_function_location_box_expandable( $params = array(), $template )
{
    if ( !function_exists('smarty_function_location_box') ) {
        foreach ( $template->getPluginsDir() as $v ) {
            if ( file_exists($v . 'function.location_box.php') ) {
                require_once $v . 'function.location_box.php';
            }
        }
    }

    // set default prefix
    if ( empty($params['data']['prefix']) ) {
        $params['data']['prefix'] = 'address';
    }

    echo '<div class="location-box-expandable">';

    $uid = uniqid();
    $location_str = $params['data']['location_address_type'] == BILLING_ADDRESS ? ''
        : (empty($params['data']['location_name']) ? '' : $params['data']['location_name'] . ', ');
    $location_str .= $params['data']['location'] ? $params['data']['location'] : '...';

    $title = '';

    switch ( $params['data']['location_address_type']  ) {
        case POSTAL_ADDRESS           : $title = trans('postal address');             break;
        case BILLING_ADDRESS          : $title = trans('billing address');            break;
        case LOCATION_ADDRESS         : $title = trans('location/recipient address'); break;
        case DEFAULT_LOCATION_ADDRESS : $title = trans('default location address');   break;
    }

    echo '<div class="address-full"
                title="' . $title . '"
                data-target="' . $uid . '"
                data-state="' . (isset($params['data']['show']) ? 'opened' : 'closed') . '">' .
                $location_str . '</div>';

    if ( isset($params['data']['show']) ) {
        echo '<div id="' . $uid . '">';
    } else {
        echo '<div id="' . $uid . '" style="display: none;">';
    }

    echo '<div style="padding: 3px 0; position: relative;">';
    echo '<span class="lms-ui-button clear-location-box"><img src="img/eraser.gif" alt="">' . trans('Clear') . '</span>';

    if ( isset($params['data']['clear_button']) ) {
        echo '&nbsp;&nbsp;<span class="lms-ui-button delete-location-box"><img src="img/delete.gif" alt="">' . trans('Delete') . '</span>';
    }

    if ( isset($params['data']['use_counter']) ) {
        echo '<div style="position: absolute; right: 0; top: 7px;">' . trans('assigned to <b>$a</b> nodes', $params['data']['use_counter']) . '</div>';
    }

    echo '</div>';

    if ( isset($params['data']['location_address_type']) ) {
        echo '<input type="hidden" value="' . $params['data']['location_address_type']  . '" name="' . $params['data']['prefix'] . '[location_address_type]" data-address="address_type">';
    } else {
        echo '<input type="hidden" value="' . LOCATION_ADDRESS .                          '" name="' . $params['data']['prefix'] . '[location_address_type]" data-address="address_type">';
    }

    smarty_function_location_box( $params['data'], $template );

    echo '</div>';
    echo '</div>';
}

?>
