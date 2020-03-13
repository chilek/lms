<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

switch (strtolower($_GET['action'])) {
    /*!
     * \brief Returns customer addresses.
     *
     * \param  int  $_GET['id'] customer id
     * \return 0                customer id is empty
     * \return json
     */
    case 'getcustomeraddresses':
        if (empty($_GET['id'])) {
            die;
        }

        $caddr = $LMS->getCustomerAddresses(intval($_GET['id']), true);
        $LMS->determineDefaultCustomerAddress($caddr);
        die(json_encode($caddr));
    break;

    /*!
     * \brief Returns single address by id.
     *
     * \param  int  $_GET['id'] customer id
     * \return json
     */
    case 'getsingleaddress':
        if (empty($_GET['id'])) {
            return 0;
        }

        $addr = $DB->GetAllByKey(
            'SELECT
                                 id as address_id, name as location_name,
                                 state as location_state_name, state_id as location_state,
                                 city as location_city_name, city_id as location_city,
                                 street as location_street_name, street_id as location_street,
                                 house as location_house, zip as location_zip, postoffice AS location_postoffice,
                                 country_id as location_country_id, flat as location_flat,
                                 -1 as location_address_type
                             FROM addresses
                             WHERE id = ?',
            'address_id',
            array((int) $_GET['id'])
        );

        if (!$addr) {
            die(json_encode(array()));
        }

        foreach ($addr as $k => $v) {
            // generate address as single string
            $location = location_str(array(
                'city_name'      => $v['location_city_name'],
                'postoffice'     => $v['location_postoffice'],
                'street_name'    => $v['location_street_name'],
                'location_house' => $v['location_house'],
                'location_flat'  => $v['location_flat']
            ));

            if (strlen($location)) {
                $addr[$k]['location'] = (empty($v['location_name']) ? '' : $v['location_name'] . ', ')
                    . (empty($v['location_zip']) ? '' : $v['location_zip'] . ' ') . $location;
            } else {
                $addr[$k]['location'] = trans('undefined');
            }
        }

        die(json_encode($addr));
    break;

    /*!
     * \brief Returns html code with function.location_box.php.
     *
     * \param string optional GET parameter contains prefix for location box
     */
    case 'getlocationboxhtml':
        global $SMARTY;

        if (!function_exists('smarty_function_location_box_expandable')) {
            foreach ($SMARTY->getPluginsDir() as $v) {
                if (file_exists($v . 'function.location_box_expandable.php')) {
                    require_once $v . 'function.location_box_expandable.php';
                }
            }
        }

        $params = array();

        if (!empty($_GET['prefix'])) {
            $params['data']['prefix'] = $_GET['prefix'];
        }

        if (!empty($_GET['show'])) {
            $params['data']['show'] = 1;
        }

        if (!empty($_GET['clear_button'])) {
            $params['data']['clear_button'] = 1;
        }

        if (!empty($_GET['default_type'])) {
            $params['data']['default_type'] = 1;
        }

        smarty_function_location_box_expandable($params, $SMARTY);
        die();
    break;

    case 'geocode':
        if (!empty($_GET['address'])) {
            die(json_encode(geocode(trim($_GET['address']))));
        }
        break;

    default:
        return 0;
}
