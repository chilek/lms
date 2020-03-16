<?php

/*
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

if ($api) {
    if (!isset($_POST['in'])) {
        die;
    }
    $netdev = json_decode(base64_decode($_POST['in']), true);
} else {
    $LMS->InitXajax();
    include(MODULES_DIR . DIRECTORY_SEPARATOR . 'geocodexajax.inc.php');
    $SMARTY->assign('xajax', $LMS->RunXajax());

    if (isset($_POST['netdev'])) {
        $netdev = $_POST['netdev'];
    }
}

if (isset($netdev)) {
    $netdev['ports']   = ($netdev['ports'] == '')    ? 0 : intval($netdev['ports']);
    $netdev['clients'] = (empty($netdev['clients'])) ? 0 : intval($netdev['clients']);
    $netdev['ownerid'] = (empty($netdev['ownerid'])) ? 0 : intval($netdev['ownerid']);

    $netdev['name'] = trim($netdev['name']);
    if ($netdev['name'] == '') {
        $error['name'] = trans('Device name is required!');
    } elseif (strlen($netdev['name']) > 60) {
        $error['name'] = trans('Specified name is too long (max. $a characters)!', '60');
    }

    $netdev['purchasetime'] = intval($netdev['purchasetime']);
    if ($netdev['purchasetime'] && time() < $netdev['purchasetime']) {
        $error['purchasetime'] = trans('Date from the future not allowed!');
    }

    if (!empty($netdev['ownerid']) && !$LMS->customerExists($netdev['ownerid'])) {
        $error['ownerid'] = trans('Customer doesn\'t exist!');
    }

    if ($netdev['guaranteeperiod'] != 0 && !$netdev['purchasetime']) {
        $error['purchasetime'] = trans('Purchase date cannot be empty when guarantee period is set!');
    }

    if (!strlen($netdev['projectid']) && !empty($netdev['project'])) {
        $project = $LMS->GetProjectByName($netdev['project']);
        if (empty($project)) {
            $netdev['projectid'] = -1;
        } else {
            $netdev['projectid'] = $project['id'];
        }
    }

    if (isset($netdev['terc']) && isset($netdev['simc']) && isset($netdev['ulic'])) {
        $teryt = $LMS->TerytToLocation($netdev['terc'], $netdev['simc'], $netdev['ulic']);
        $netdev['teryt'] = 1;
        $netdev['location_state'] = $teryt['location_state'];
        $netdev['location_state_name'] = $teryt['location_state_name'];
        $netdev['location_city'] = $teryt['location_city'];
        $netdev['location_city_name'] = $teryt['location_city_name'];
        $netdev['location_street'] = $teryt['location_street'];
        $netdev['location_street_name'] = $teryt['location_street_name'];
    }

    if (empty($netdev['ownerid']) && !ConfigHelper::checkPrivilege('full_access')
        && ConfigHelper::checkConfig('phpui.teryt_required')
        && !empty($netdev['location_city_name']) && ($netdev['location_country_id'] == 2 || empty($netdev['location_country_id']))
        && (!isset($netdev['teryt']) || empty($netdev['location_city']))) {
        $error['netdev[teryt]'] = trans('TERRIT address is required!');
    }

    $hook_data = $LMS->executeHook(
        'netdevadd_validation_before_submit',
        array(
            'netdevdata' => $netdev,
            'error' => $error,
        )
    );
    $netdev = $hook_data['netdevdata'];
    $error = $hook_data['error'];

    if (!$error) {
        if ($netdev['guaranteeperiod'] == -1) {
            $netdev['guaranteeperiod'] = null;
        }

        if (!isset($netdev['shortname'])) {
            $netdev['shortname'] = '';
        }
        if (!isset($netdev['login'])) {
            $netdev['login'] = '';
        }
        if (!isset($netdev['secret'])) {
            $netdev['secret'] = '';
        }
        if (!isset($netdev['community'])) {
            $netdev['community'] = '';
        }
        if (!isset($netdev['nastype'])) {
            $netdev['nastype'] = 0;
        }

        // if network device owner is set then get customer address
        // else get fields from location dialog box
        if (empty($netdev['ownerid'])) {
            $netdev['address_id'] = null;
        } else {
            $netdev['location_name']        = null;
            $netdev['location_state_name']  = null;
            $netdev['location_state']       = null;
            $netdev['location_city_name']   = null;
            $netdev['location_city']        = null;
            $netdev['location_street_name'] = null;
            $netdev['location_street']      = null;
            $netdev['location_house']       = null;
            $netdev['location_flat']        = null;
            $netdev['location_zip']         = null;
            $netdev['location_country_id']  = null;
        }

        if ($netdev['projectid'] == -1) {
            $netdev['projectid'] = $LMS->AddProject($netdev);
        } elseif (empty($netdev['projectid'])) {
            $netdev['projectid'] = null;
        }

        if ($netdev['netnodeid']=="-1") {
            $netdev['netnodeid'] = null;
        } else {
            // heirdom localization
            $dev = $DB->GetRow("SELECT address_id, longitude, latitude FROM netnodes WHERE id = ?", array($netdev['netnodeid']));
            if ($dev) {
                if (empty($netdev['address_id']) && empty($netdev['location_city']) && empty($netdev['location_street'])) {
                    $netdev['address_id'] = $dev['address_id'];
                }
                if (!strlen($netdev['longitude']) || !strlen($netdev['longitude'])) {
                    $netdev['longitude'] = $dev['longitude'];
                    $netdev['latitude']  = $dev['latitude'];
                }
            }
        }

        $netdevid = $LMS->NetDevAdd($netdev);

        $netdev['id'] = $netdevid;
        $hook_data = $LMS->executeHook(
            'netdevadd_after_update',
            array(
                'netdevdata' => $netdev,
            )
        );

        if ($api) {
            if ($netdevid) {
                header('Content-Type: application/json');
                echo json_encode(array('id' => $netdevid));
            }
            die;
        }

        $SESSION->redirect('?m=netdevinfo&id='.$netdevid);
    } elseif ($api) {
        header('Content-Type: application/json');
        echo json_encode($error);
        die;
    }

    if (!empty($netdev['ownerid'])) {
        $netdev['address_id'] = $netdev['customer_address_id'];
    }

    $SMARTY->assign('error', $error);
    $SMARTY->assign('netdev', $netdev);
} elseif (isset($_GET['id'])) {
    $netdev = $LMS->GetNetDev($_GET['id']);

    if (preg_match('/^[0-9]+$/', $netdev['producerid'])
        && preg_match('/^[0-9]+$/', $netdev['modelid'])) {
        $netdev['producer'] = $netdev['producerid'];
        $netdev['model'] = $netdev['modelid'];
    }

    $netdev['name'] = trans('$a (clone)', trim($netdev['name']));
    $netdev['clone'] = isset($_GET['clone']) ? $_GET['clone'] : null;
    $netdev['teryt'] = !empty($netdev['location_city']) && !empty($netdev['location_street']);
    $SMARTY->assign('netdev', $netdev);
} else {
    if (isset($_GET['customerid'])) {
        $netdev['ownerid'] = intval($_GET['customerid']);
        if (!$netdev['ownerid']) {
            $netdev['ownerid'] = '';
        }
    }
    $SMARTY->assign('netdev', $netdev);
}

$layout['pagetitle'] = trans('New Device');

$SMARTY->assign('nastypes', $LMS->GetNAStypes());

$SMARTY->assign('NNprojects', $LMS->GetProjects());
$SMARTY->assign('NNnodes', $LMS->GetNetNodes());
$SMARTY->assign('producers', $LMS->GetProducers());
$SMARTY->assign('models', $LMS->GetModels());

if (!empty($netdev['ownerid'])) {
    $addresses = $LMS->getCustomerAddresses($netdev['ownerid']);
    $LMS->determineDefaultCustomerAddress($addresses);
    $SMARTY->assign('addresses', $addresses);
}

if (ConfigHelper::checkConfig('phpui.ewx_support')) {
    $SMARTY->assign('channels', $DB->GetAll('SELECT id, name FROM ewx_channels ORDER BY name'));
}

if (!ConfigHelper::checkConfig('phpui.big_networks')) {
    $SMARTY->assign('customers', $LMS->GetCustomerNames());
}

$SMARTY->display('netdev/netdevadd.html');
