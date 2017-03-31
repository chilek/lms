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

if (!$LMS->NetNodeExists($id))
	$SESSION->redirect('?m=netnodelist');

if (isset($_POST['netnode'])) {
	$netnodedata = $_POST['netnode'];
	$netnodedata['id'] = $id;
	if ($netnodedata['name'] == '')
		$error['name'] = trans('Net node name is required!');

	if ($netnodedata['invprojectid'] == '-1') { // new project
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

	if ($netnodedata['location_zip'] && !check_zip($netnodedata['location_zip'])) {
		$error['netnode[location_zip]'] = trans('Incorrect ZIP code!');
	}

	if (!$error) {
		if (intval($netnodedata['invprojectid']) == -1) {
			$DB->Execute("INSERT INTO invprojects (name, type) VALUES (?, ?)",
				array($netnodedata['projectname'], INV_PROJECT_REGULAR));
			$netnodedata['invprojectid'] = $DB->GetLastInsertID('invprojects');
		}

		$LMS->NetNodeUpdate($netnodedata);
		$LMS->CleanupInvprojects();

		$SESSION->redirect('?m=netnodeinfo&id=' . $id);
	}
} else {
	$netnodedata = $LMS->GetNetNode($id);

	if ($netnodedata['location_city'] || $netnodedata['location_street']) {
		$netnodedata['teryt'] = true;
	}
}

$layout['pagetitle'] = trans('Net Device Node Edit: $a', $netnodedata['name']);

if ($subtitle)
	$layout['pagetitle'] .= ' - ' . $subtitle;

$SMARTY->assign('error'    , $error);
$SMARTY->assign('netnode'  , $netnodedata);
$SMARTY->assign('objectid' , $netnodedata['id']);
$SMARTY->assign('divisions', $DB->GetAll('SELECT id, shortname FROM divisions ORDER BY shortname'));

$nprojects = $DB->GetAll("SELECT * FROM invprojects WHERE type<>? ORDER BY name",
	array(INV_PROJECT_SYSTEM));
$SMARTY->assign('NNprojects',$nprojects);


$SMARTY->display('netnode/netnodeedit.html');

?>
