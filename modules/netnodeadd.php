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
	$netnodedata = json_decode(base64_decode($_POST['in']), true);
} else {
	$LMS->InitXajax();
	include(MODULES_DIR . DIRECTORY_SEPARATOR . 'geocodexajax.inc.php');
	$SMARTY->assign('xajax', $LMS->RunXajax());

	if (isset($_POST['netnode']))
		$netnodedata = $_POST['netnode'];
}


if (isset($netnodedata)) {
	if ($netnodedata['name'] == '')
		$error['name'] = trans('Net node name is required!');

	if ($netnodedata['divisionid'] == '-1')
		$error['divisionid'] = trans('Division is required!');

	if ($netnodedata['invprojectid'] == '-1') { // new investment project
		if (!strlen(trim($netnodedata['projectname']))) {
		 $error['projectname'] = trans('Project name is required');
		}
		if ($LMS->ProjectByNameExists($netnodedata['projectname']))
			$error['projectname'] = trans('Project with that name already exists');
	}

	if ($netnodedata['location_zip'] && !check_zip($netnodedata['location_zip'])) {
		$error['netnode[location_zip]'] = trans('Incorrect ZIP code!');
	}

	if (in_array($netnodedata['ownership'], array('1', '2'))) { // węzeł współdzielony lub obcy
		if (!strlen(trim($netnodedata['coowner']))) {
		 $error['coowner'] = trans('Co-owner identifier is required');
		}
	}

    if (!$error) {
		if (intval($netnodedata['invprojectid']) == -1)
			$netnodedata['invprojectid'] = $LMS->AddProject($netnodedata);

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

	$SMARTY->assign('error', $error);

} else {
	$netnodedata = array();
	$netnodedata['uip'] = 0;
	$netnodedata['miar'] = 0;
	$netnodedata['invprojectid'] = '-2'; // no investment project selected
	$netnodedata['ownership'] = 0;
}

$layout['pagetitle'] = trans('New Net Device Node');

$SMARTY->assign('netnode'  , $netnodedata);
$SMARTY->assign('divisions', $LMS->GetDivisions());
$SMARTY->assign('NNprojects', $LMS->GetProjects());

$SMARTY->display('netnode/netnodeadd.html');

?>
