<?php

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

        if (!empty($_GET['delete_button'])) {
            $params['data']['delete_button'] = 1;
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
