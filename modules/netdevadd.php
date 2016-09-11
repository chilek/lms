<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

if(isset($_POST['netdev']))
{
	$netdevdata = $_POST['netdev'];

	if($netdevdata['ports'] == '')
		$netdevdata['ports'] = 0;
	else
		$netdevdata['ports'] = intval($netdevdata['ports']);

	if(empty($netdevdata['clients']))
		$netdevdata['clients'] = 0;
	else
		$netdevdata['clients'] = intval($netdevdata['clients']);

	if($netdevdata['name'] == '')
		$error['name'] = trans('Device name is required!');
	elseif (strlen($netdevdata['name']) > 60)
		$error['name'] = trans('Specified name is too long (max. $a characters)!', '60');

	$netdevdata['purchasetime'] = 0;
	if($netdevdata['purchasedate'] != '') 
	{
		// date format 'yyyy/mm/dd'
		if(!preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $netdevdata['purchasedate']))
		{
			$error['purchasedate'] = trans('Invalid date format!');
		}
		else
		{
			$date = explode('/', $netdevdata['purchasedate']);
			if(checkdate($date[1], $date[2], (int)$date[0]))
			{
				$tmpdate = mktime(0, 0, 0, $date[1], $date[2], $date[0]);
                        	if(mktime(0,0,0) < $tmpdate)
			                $error['purchasedate'] = trans('Date from the future not allowed!');
				else
				        $netdevdata['purchasetime'] = $tmpdate;
			}
			else
				$error['purchasedate'] = trans('Invalid date format!');
		}
	}

	if($netdevdata['guaranteeperiod'] != 0 && $netdevdata['purchasetime'] == NULL)
	{
		$error['purchasedate'] = trans('Purchase date cannot be empty when guarantee period is set!');
	}


	if ($netdevdata['invprojectid'] == '-1') { // nowy projekt
		if (!strlen(trim($netdevdata['projectname']))) {
		 $error['projectname'] = trans('Project name is required');
		}
		$l = $DB->GetOne("SELECT * FROM invprojects WHERE name=? AND type<>?",
			array($netdevdata['projectname'], INV_PROJECT_SYSTEM));
		if (sizeof($l)>0) {
			$error['projectname'] = trans('Project with that name already exists');
		}
	}

    if(!$error)
    {
		if($netdevdata['guaranteeperiod'] == -1)
			$netdevdata['guaranteeperiod'] = NULL;

		if(!isset($netdevdata['shortname'])) $netdevdata['shortname'] = '';
        if(!isset($netdevdata['secret'])) $netdevdata['secret'] = '';
        if(!isset($netdevdata['community'])) $netdevdata['community'] = '';
        if(!isset($netdevdata['nastype'])) $netdevdata['nastype'] = 0;

        if (empty($netdevdata['teryt'])) {
            $netdevdata['location_city'] = null;
            $netdevdata['location_street'] = null;
            $netdevdata['location_house'] = null;
            $netdevdata['location_flat'] = null;
	}
	$ipi = $netdevdata['invprojectid'];
	if ($ipi == '-1') {
		$DB->BeginTrans();
		$DB->Execute("INSERT INTO invprojects (name, type) VALUES (?, ?)",
			array($netdevdata['projectname'], INV_PROJECT_REGULAR));
		$ipi = $DB->GetLastInsertID('invprojects');
		$DB->CommitTrans();
	} 
	if ($netdevdata['invprojectid'] == '-1' || intval($ipi)>0)
		$netdevdata['invprojectid'] = intval($ipi);
	else
		$netdevdata['invprojectid'] = NULL;
	if ($netdevdata['netnodeid']=="-1") {
		$netdevdata['netnodeid']=NULL;
	}
	else {
		/* dziedziczenie lokalizacji */
		$dev = $DB->GetRow("SELECT * FROM netnodes WHERE id = ?", array($netdevdata['netnodeid']));
		if ($dev) {
			if (!strlen($netdevdata['location'])) {
				$netdevdata['location'] = $dev['location'];
				$netdevdata['location_city'] = $dev['location_city'];
				$netdevdata['location_street'] = $dev['location_street'];
				$netdevdata['location_house'] = $dev['location_house'];
				$netdevdata['location_flat'] = $dev['location_flat'];
			}
			if (!strlen($netdevdata['longitude']) || !strlen($netdevdata['longitude'])) {
				$netdevdata['longitude'] = $dev['longitude'];
				$netdevdata['latitude'] = $dev['latitude'];
			}
		}
	}

		$netdevid = $LMS->NetDevAdd($netdevdata);

		$SESSION->redirect('?m=netdevinfo&id='.$netdevid);
    }

	$SMARTY->assign('error', $error);
	$SMARTY->assign('netdev', $netdevdata);
} elseif (isset($_GET['id'])) {
	$netdevdata = $LMS->GetNetDev($_GET['id']);
	$netdevdata['name'] = trans('$a (clone)', $netdevdata['name']);
	$netdevdata['teryt'] = !empty($netdevdata['location_city']) && !empty($netdevdata['location_street']);
	$SMARTY->assign('netdev', $netdevdata);
}

$layout['pagetitle'] = trans('New Device');

$SMARTY->assign('nastype', $LMS->GetNAStypes());

$nprojects = $DB->GetAll("SELECT * FROM invprojects WHERE type<>? ORDER BY name", array(INV_PROJECT_SYSTEM));
$SMARTY->assign('NNprojects',$nprojects);
$netnodes = $DB->GetAll("SELECT * FROM netnodes ORDER BY name");
$SMARTY->assign('NNnodes',$netnodes);

if (ConfigHelper::checkConfig('phpui.ewx_support'))
	$SMARTY->assign('channels', $DB->GetAll('SELECT id, name FROM ewx_channels ORDER BY name'));

$SMARTY->display('netdev/netdevadd.html');

?>
