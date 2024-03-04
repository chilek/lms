<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2021 LMS Developers
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

$lms = LMS::getInstance();
$error = array();

if (isset($_GET['oper'])) {
    switch ($_GET['oper']) {
        case 'add':
            $params = $_POST;
            $netdevid = intval($params['netdevid']);
            $label = (empty($params['label']) ? null : trim($params['label']));
            $mac = (empty($params['mac']) ? null : strtoupper(trim($params['mac'])));
            $main = intval($params['main']);

            // validate mac
            if (!empty($mac)) {
                if (!check_mac($mac)) {
                    $error['mac_error'] = trans('Incorrect MAC address!');
                } elseif ($lms->getNetDevByMac($mac)) {
                    $error['mac_error'] = trans('MAC address already exists!');
                }
            } else {
                $error['mac_error'] = trans('No MAC address!');
            }

            // validate main mac
            if ($main && $lms->getNetDevMacs($netdevid, 1)) {
                $error['main_error'] = trans('Primary MAC already exists!');
            }

            // validate label
            if (!empty($label)) {
                $netdevLabels = $lms->getNetDevMacLabels($netdevid);
                if (isset($netdevLabels[$label])) {
                    $error['label_error'] = trans('MAC label already exists for the network device!');
                }
            } else {
                $error['label_error'] = trans('No label!');
            }

            $netdevMacData = compact("netdevid", "label", "mac", "main");

            $hook_data = $lms->executeHook(
                'netdevmacadd_validation_before_update',
                array(
                    'netdev_mac_data' => $netdevMacData,
                    'error' => $error
                )
            );
            $netdevMacData = $hook_data['netdev_mac_data'];
            $error = $hook_data['error'];

            if ($error) {
                die(json_encode($error));
            }

            $macid = $lms->addNetDevMac($netdevMacData);

            if ($macid) {
                $netdevMacData = $lms->getNetDevMac($macid);

                $hook_data = $lms->executeHook(
                    'netdevmacadd_after_update',
                    array(
                        'netdev_mac_data' => $netdevMacData,
                    )
                );

                die(json_encode($netdevMacData));
            }
            break;
        case 'edit':
            $params = $_POST;
            $macid = intval($params['macid']);
            $netdevid = intval($params['netdevid']);
            $label = (empty($params['label']) ? null : trim($params['label']));
            $mac = (empty($params['mac']) ? null : strtoupper(trim($params['mac'])));
            $main = intval($params['main']);

            $oldMacData = $lms->getNetDevMac($macid);
            // validate mac
            if (!empty($mac)) {
                if (!check_mac($mac)) {
                    $error['mac_error'] = trans('Incorrect MAC address!');
                } elseif ($lms->getNetDevByMac($mac) && $oldMacData['mac'] != $mac) {
                    $error['mac_error'] = trans('MAC address already exists!');
                }
            } else {
                $error['mac_error'] = trans('No MAC address!');
            }

            // validate label
            if (!empty($label)) {
                $netdevLabels = $lms->getNetDevMacLabels($netdevid);
                if (isset($netdevLabels[$label]) && $oldMacData['label'] != $label) {
                    $error['label_error'] = trans('MAC label already exists for the network device!');
                }
            } else {
                $error['label_error'] = trans('No label!');
            }

            $netdevMacData = compact("macid", "netdevid", "label", "mac", "main");

            $hook_data = $lms->executeHook(
                'netdevmacedit_validation_before_update',
                array(
                    'netdev_mac_data' => $netdevMacData,
                    'error' => $error
                )
            );
            $netdevMacData = $hook_data['netdev_mac_data'];
            $error = $hook_data['error'];

            if ($error) {
                die(json_encode($error));
            }

            $macid = $lms->updateNetDevMac($netdevMacData);

            if ($macid) {
                $netdevMacData = $lms->getNetDevMac($macid);

                $hook_data = $lms->executeHook(
                    'netdevmacedit_after_update',
                    array(
                        'netdev_mac_data' => $netdevMacData,
                    )
                );

                die(json_encode($netdevMacData));
            }
            break;
        case 'del':
            $lms->delNetDevMac($_GET['id']);
            break;
        case 'showlabels':
            die(json_encode($lms->GetNetdevsMacLabels()));
    }
}

die('[]');
