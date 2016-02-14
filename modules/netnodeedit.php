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

$id = intval($_GET['id']);
if (empty($id)) {
 $id = intval($_POST['id']);
}

if (!$DB->GetOne('SELECT * FROM netnodes WHERE id=?',array($id)))
	$SESSION->redirect('?m=netnodelist');


if (isset($_POST['netnode'])) {
	$netnodedata = $_POST['netnode'];
	$netnodedata['id'] = $id;	
	if($netnodedata['name'] == '')
		$error['name'] = trans('Net node name is required!');

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

	if (!$error) {
		
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
		'divisionid'=>$netnodedata['divisionid']
                );	
	
		if ($netnodedata['invprojectid'] == '-1' || intval($ipi)>0) {
			$args['invprojectid'] = intval($ipi);	
		} else {
			$args['invprojectid'] = 'NULL';
		}
		
		$fields = array();
		foreach ($args as $key=>$value) {
			if ($key == 'name' || $key == 'location' ||$key == 'location_house' 
				|| $key == 'location_flat' || $key == 'coowner' ) {	
					array_push($fields,$key."='".$value."'");
				} else {
					if (strval($value)!='')
						array_push($fields,$key."=".$value);
			}
		}
                $DB->Execute("UPDATE netnodes SET ".join($fields,",")." WHERE id=?",array($id));
		$LMS->CleanupInvprojects();
		$SESSION->redirect('?m=netnodeinfo&id=' . $id);
	}
} else {
	$netnodedata = $DB->GetRow("SELECT n.*,p.name AS projectname FROM netnodes n LEFT JOIN invprojects p ON n.invprojectid=p.id WHERE n.id=?",array($id));
	if ($netnodedata['location_city'] || $netnodedata['location_street']) {
		$netnodedata['teryt'] = true;
	}
}


$layout['pagetitle'] = trans('Net Device Node Edit: $a', $netnodedata['name']);

if ($subtitle)
	$layout['pagetitle'] .= ' - ' . $subtitle;

$SMARTY->assign('error', $error);
$SMARTY->assign('netnode', $netnodedata);
$SMARTY->assign('objectid', $netnodedata['id']);
$SMARTY->assign('divisions', $DB->GetAll('SELECT id, shortname FROM divisions ORDER BY shortname'));

$nprojects = $DB->GetAll("SELECT * FROM invprojects WHERE type<>? ORDER BY name",
	array(INV_PROJECT_SYSTEM));
$SMARTY->assign('NNprojects',$nprojects);


$SMARTY->display('netnode/netnodeedit.html');

?>
