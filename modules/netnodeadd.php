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

if ($api) {
    if (!isset($_POST['in'])) {
        die;
    }
    $netnodedata = json_decode(base64_decode($_POST['in']), true);
} else {
    $LMS->InitXajax();
    include(MODULES_DIR . DIRECTORY_SEPARATOR . 'geocodexajax.inc.php');
    $SMARTY->assign('xajax', $LMS->RunXajax());

    if (isset($_POST['netnode'])) {
        $netnodedata = $_POST['netnode'];
    }
}


if (isset($netnodedata)) {
    if ($netnodedata['name'] == '') {
        $error['name'] = trans('Network node name is required!');
    }

    if ($api) {
        if (isset($netnodedata['division'])) {
            $division = $LMS->GetDivisionByName($netnodedata['division']);
            if (empty($division)) {
                $error['divisionid'] = trans('Division is required!');
            } else {
                $netnodedata['divisionid'] = $division['id'];
            }
        } else {
            $error['divisionid'] = trans('Division is required!');
        }
    } elseif ($netnodedata['divisionid'] == '-1') {
        $error['divisionid'] = trans('Division is required!');
    }

    if (!strlen($netnodedata['projectid']) && !empty($netnodedata['project'])) {
        $project = $LMS->GetProjectByName($netnodedata['project']);
        if (empty($project)) {
            $netnodedata['projectid'] = -1;
        } else {
            $netnodedata['projectid'] = $project['id'];
        }
    }

    if ($netnodedata['location_zip'] && !Localisation::checkZip($netnodedata['location_zip'], $netnodedata['location_country_id'])) {
        $error['location_zip'] = trans('Incorrect ZIP code!');
    }

    if (in_array($netnodedata['ownership'], array('1', '2'))) { // węzeł współdzielony lub obcy
        if (!strlen(trim($netnodedata['coowner']))) {
            $error['coowner'] = trans('Co-owner identifier is required');
        }
    }

    if (isset($netnodedata['terc']) && isset($netnodedata['simc']) && isset($netnodedata['ulic'])) {
        $teryt = $LMS->TerytToLocation($netnodedata['terc'], $netnodedata['simc'], $netnodedata['ulic']);
        $netnodedata['teryt'] = 1;
        $netnodedata['location_state'] = $teryt['location_state'];
        $netnodedata['location_state_name'] = $teryt['location_state_name'];
        $netnodedata['location_city'] = $teryt['location_city'];
        $netnodedata['location_city_name'] = $teryt['location_city_name'];
        $netnodedata['location_street'] = $teryt['location_street'];
        $netnodedata['location_street_name'] = $teryt['location_street_name'];
    }

    if (intval($netnodedata['lastinspectiontime']) > time()) {
        $error['lastinspectiontime'] = trans('Date from the future not allowed!');
    }

    if (empty($netnodedata['ownerid']) && !ConfigHelper::checkPrivilege('full_access')
        && ConfigHelper::checkConfig('phpui.teryt_required')
        && !empty($netnodedata['location_city_name']) && ($netnodedata['location_country_id'] == 2 || empty($netnodedata['location_country_id']))
        && (!isset($netnodedata['teryt']) || empty($netnodedata['location_city']))) {
        $error['netnode[teryt]'] = trans('TERRIT address is required!');
    }

    if (!$error) {
        if ($netnodedata['projectid'] == -1) {
            $netnodedata['projectid'] = $LMS->AddProject($netnodedata);
        } elseif (empty($netnodedata['projectid'])) {
            $netnodedata['projectid'] = null;
        }

        $netnodeid = $LMS->NetNodeAdd($netnodedata);

        if ($api) {
            if ($netnodeid) {
                header('Content-Type: application/json');
                echo json_encode(array('id' => $netnodeid));
            }
            die;
        }

        $SESSION->redirect('?m=netnodeinfo&id=' . $netnodeid);
    } elseif ($api) {
        header('Content-Type: application/json');
        echo json_encode($error);
        die;
    }

    if (!empty($netnodedata['ownerid'])) {
        $netnodedata['address_id'] = $netnodedata['customer_address_id'];
    }

    $SMARTY->assign('error', $error);
} else {
    $netnodedata = array();
    $netnodedata['uip'] = 0;
    $netnodedata['miar'] = 0;
    $netnodedata['invprojectid'] = '-2'; // no investment project selected
    $netnodedata['ownership'] = 0;
    if (isset($_GET['customerid'])) {
        $netnodedata['ownerid'] = $_GET['customerid'];
        $netnodedata['ownership'] = 2;
    }
}

$layout['pagetitle'] = trans('New Net Device Node');

if (!empty($netnodedata['ownerid'])) {
    $addresses = $LMS->getCustomerAddresses($netnodedata['ownerid']);
    $LMS->determineDefaultCustomerAddress($addresses);
    $SMARTY->assign('addresses', $addresses);
}

$SMARTY->assign('netnode', $netnodedata);
$SMARTY->assign('divisions', $LMS->GetDivisions());
$SMARTY->assign('NNprojects', $LMS->GetProjects());

$SMARTY->display('netnode/netnodemodify.html');
