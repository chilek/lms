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

if(isset($_POST['netnode']))
{
	$netnodedata = $_POST['netnode'];

	if($netnodedata['name'] == '')
		$error['name'] = trans('Net node name is required!');

	if($netnodedata['divisionid'] == '-1')
		$error['divisionid'] = trans('Division is required!');

	if ($netnodedata['invprojectid'] == '-1') { // nowy projekt
		if (!strlen(trim($netnodedata['projectname']))) {
		 $error['projectname'] = trans('Project name is required');
		}
		if ($DB->GetOne("SELECT * FROM invprojects WHERE name=? AND type<>?",
			array($netnodedata['projectname'], INV_PROJECT_SYSTEM)))
			$error['projectname'] = trans('Project with that name already exists');
	}

	if (in_array($netnodedata['ownership'], array('1', '2'))) { // węzeł współdzielony lub obcy
		if (!strlen(trim($netnodedata['coowner']))) {
		 $error['coowner'] = trans('Co-owner identifier is required');
		}
	}

    if(!$error)
    {

        if (empty($netnodedata['teryt'])) {
            $netnodedata['location_city'] = null;
            $netnodedata['location_street'] = null;
            $netnodedata['location_house'] = null;
            $netnodedata['location_flat'] = null;
        }

	$ipi = $netnodedata['invprojectid'];
	if ($ipi == '-1') {
		$DB->BeginTrans();
		$DB->Execute("INSERT INTO invprojects (name, type) VALUES (?, ?)",
			array($netnodedata['projectname'], INV_PROJECT_REGULAR));
		$ipi = $DB->GetLastInsertID('invprojects');
		$DB->CommitTrans();
	}

	$args = array('name'=>$netnodedata['name'],
		'type'=>$netnodedata['type'],
		'status'=>$netnodedata['status'],
		'location' => $netnodedata['location'],
		'location_city' => $netnodedata['location_city'] ? $netnodedata['location_city'] : null,
		'location_street' => $netnodedata['location_street'] ? $netnodedata['location_street'] : null,
		'location_house' => $netnodedata['location_house'] ? $netnodedata['location_house'] : null,
		'location_flat' => $netnodedata['location_flat'] ? $netnodedata['location_flat'] : null,
		'longitude' => !empty($netnodedata['longitude']) ? str_replace(',', '.', $netnodedata['longitude']) : NULL,
		'latitude' => !empty($netnodedata['latitude']) ? str_replace(',', '.', $netnodedata['latitude']) : NULL,
		'ownership'=>$netnodedata['ownership'],
		'coowner'=>$netnodedata['coowner'],
		'uip'=>$netnodedata['uip'],
		'miar'=>$netnodedata['miar'],
		'divisionid' => !empty($netnodedata['divisionid']) ? $netnodedata['divisionid'] : NULL
            );

	if ($netnodedata['invprojectid'] == '-1' || intval($ipi)>0) {
		$args['invprojectid'] = intval($ipi);
		$fields = 'name,type,status,location,location_city,location_street,location_house,location_flat,longitude,latitude,ownership,coowner,uip,miar,divisionid,invprojectid';
		$values = "?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?";
	} else {
		$fields = 'name,type,status,location,location_city,location_street,location_house,location_flat,longitude,latitude,ownership,coowner,uip,miar,divisionid';
		$values = "?,?,?,?,?,?,?,?,?,?,?,?,?,?,?";
	}


	$DB->Execute("INSERT INTO netnodes (".$fields.") VALUES (".$values.")",array_values($args));
	$netnodeid = $DB->GetLastInsertID('netnodes');
	$SESSION->redirect('?m=netnodeinfo&id='.$netnodeid);
    }

	$SMARTY->assign('error', $error);

} else {
	$netnodedata = array();
	$netnodedata['uip'] = 0;
	$netnodedata['miar'] = 0;
	$netnodedata['invprojectid'] = '-2'; // brak projektu
	$netnodedata['ownership'] = 0;
}

$SMARTY->assign('netnode', $netnodedata);
$SMARTY->assign('divisions', $DB->GetAll('SELECT id, shortname FROM divisions ORDER BY shortname'));

$nprojects = $DB->GetAll("SELECT * FROM invprojects WHERE type<>? ORDER BY name",
	array(INV_PROJECT_SYSTEM));

$layout['pagetitle'] = trans('New Net Device Node');
$SMARTY->assign('NNprojects',$nprojects);

$SMARTY->display('netnode/netnodeadd.html');

?>
