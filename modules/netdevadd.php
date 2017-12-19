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
	if (!isset($_POST['in']))
		die;
	$netdevdata = json_decode(base64_decode($_POST['in']), true);
} else {
	$LMS->InitXajax();
	include(MODULES_DIR . DIRECTORY_SEPARATOR . 'geocodexajax.inc.php');
	$SMARTY->assign('xajax', $LMS->RunXajax());

	if (isset($_POST['netdev']))
		$netdevdata = $_POST['netdev'];
}

if (isset($netdevdata)) {
	$netdevdata['ports']   = ($netdevdata['ports'] == '')    ? 0 : intval($netdevdata['ports']);
	$netdevdata['clients'] = (empty($netdevdata['clients'])) ? 0 : intval($netdevdata['clients']);
	$netdevdata['ownerid'] = (empty($netdevdata['ownerid'])) ? 0 : intval($netdevdata['ownerid']);

	if ($netdevdata['name'] == '')
		$error['name'] = trans('Device name is required!');
	elseif (strlen($netdevdata['name']) > 60)
		$error['name'] = trans('Specified name is too long (max. $a characters)!', '60');

	if ($netdevdata['purchasedate'] != '')
	{
		$netdevdata['purchasetime'] = date_to_timestamp($netdevdata['purchasedate']);
		if(empty($netdevdata['purchasetime']))
			$error['purchasedate'] = trans('Invalid date format!');
		else
			if (time() < $netdevdata['purchasetime'])
				$error['purchasedate'] = trans('Date from the future not allowed!');
	}
	else
		$netdevdata['purchasetime'] = 0;

    if (!empty($netdevdata['ownerid']) && !$LMS->customerExists($netdevdata['ownerid']))
        $error['ownerid'] = trans('Customer doesn\'t exist!');

	if ($netdevdata['guaranteeperiod'] != 0 && $netdevdata['purchasedate'] == NULL) {
		$error['purchasedate'] = trans('Purchase date cannot be empty when guarantee period is set!');
	}

	if ($api && isset($netdevdata['project'])) {
		$project = $LMS->GetProjectByName($netdevdata['project']);
		if (empty($project)) {
			$netdevdata['projectname'] = $netdevdata['project'];
			$netdevdata['invprojectid'] = -1;
		} else
			$netdevdata['invprojectid'] = $project['id'];
	}

	// new project
	if ($netdevdata['invprojectid'] == '-1') {
		if (!strlen(trim($netdevdata['projectname'])))
			$error['projectname'] = trans('Project name is required');
		if ($LMS->ProjectByNameExists($netdevdata['projectname']))
			$error['projectname'] = trans('Project with that name already exists');
	}

	if (isset($netdevdata['terc']) && isset($netdevdata['simc']) && isset($netdevdata['ulic'])) {
		$teryt = $LMS->TerytToLocation($netdevdata['terc'], $netdevdata['simc'], $netdevdata['ulic']);
		$netdevdata['teryt'] = 1;
		$netdevdata['location_state'] = $teryt['location_state'];
		$netdevdata['location_state_name'] = $teryt['location_state_name'];
		$netdevdata['location_city'] = $teryt['location_city'];
		$netdevdata['location_city_name'] = $teryt['location_city_name'];
		$netdevdata['location_street'] = $teryt['location_street'];
		$netdevdata['location_street_name'] = $teryt['location_street_name'];
	}

    if (!$error) {
		if ($netdevdata['guaranteeperiod'] == -1)
			$netdevdata['guaranteeperiod'] = NULL;

		if (!isset($netdevdata['shortname'])) $netdevdata['shortname'] = '';
        if (!isset($netdevdata['secret'])) $netdevdata['secret'] = '';
        if (!isset($netdevdata['community'])) $netdevdata['community'] = '';
        if (!isset($netdevdata['nastype'])) $netdevdata['nastype'] = 0;

        // if network device owner is set then get customer address
        // else get fields from location dialog box
        if ( empty($netdevdata['ownerid']) ) {
            $netdevdata['address_id'] = null;
        } else {
            $netdevdata['location_name']        = null;
            $netdevdata['location_state_name']  = null;
            $netdevdata['location_state']       = null;
            $netdevdata['location_city_name']   = null;
            $netdevdata['location_city']        = null;
            $netdevdata['location_street_name'] = null;
            $netdevdata['location_street']      = null;
            $netdevdata['location_house']       = null;
            $netdevdata['location_flat']        = null;
            $netdevdata['location_zip']         = null;
            $netdevdata['location_country_id']  = null;
        }

		$ipi = $netdevdata['invprojectid'];
		if ($ipi == '-1')
			$ipi = $LMS->AddProject($netdevdata);

		if ($netdevdata['invprojectid'] == '-1' || intval($ipi)>0)
			$netdevdata['invprojectid'] = intval($ipi);
		else
			$netdevdata['invprojectid'] = NULL;

		if ($netdevdata['netnodeid']=="-1") {
			$netdevdata['netnodeid'] = NULL;
		} else {
			// heirdom localization
			$dev = $DB->GetRow("SELECT address_id, longitude, latitude FROM netnodes WHERE id = ?", array($netdevdata['netnodeid']));
			if ($dev) {
				if ( empty($netdevdata['address_id']) && empty($netdevdata['location_city']) && empty($netdevdata['location_street']) ) {
					$netdevdata['address_id'] = $dev['address_id'];
				}
				if (!strlen($netdevdata['longitude']) || !strlen($netdevdata['longitude'])) {
					$netdevdata['longitude'] = $dev['longitude'];
					$netdevdata['latitude']  = $dev['latitude'];
				}
			}
		}

		$netdevid = $LMS->NetDevAdd($netdevdata);

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

	$SMARTY->assign('error', $error);
	$SMARTY->assign('netdev', $netdevdata);
} elseif (isset($_GET['id'])) {
	$netdevdata = $LMS->GetNetDev($_GET['id']);
	$netdevdata['name'] = trans('$a (clone)', $netdevdata['name']);
	$netdevdata['teryt'] = !empty($netdevdata['location_city']) && !empty($netdevdata['location_street']);
	$SMARTY->assign('netdev', $netdevdata);
} else {
	if (isset($_GET['customerid'])) {
		$netdevdata['ownerid'] = intval($_GET['customerid']);
		if (!$netdevdata['ownerid'])
			$netdevdata['ownerid'] = '';
	}
	$SMARTY->assign('netdev', $netdevdata);
}

$layout['pagetitle'] = trans('New Device');

$SMARTY->assign('nastypes', $LMS->GetNAStypes());

$SMARTY->assign('NNprojects', $LMS->GetProjects());
$SMARTY->assign('NNnodes', $LMS->GetNetNodes());

if (ConfigHelper::checkConfig('phpui.ewx_support'))
	$SMARTY->assign('channels', $DB->GetAll('SELECT id, name FROM ewx_channels ORDER BY name'));

if (!ConfigHelper::checkConfig('phpui.big_networks'))
    $SMARTY->assign('customers', $LMS->GetCustomerNames());

$SMARTY->display('netdev/netdevadd.html');

?>
