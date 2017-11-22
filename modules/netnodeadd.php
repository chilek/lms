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

$LMS->InitXajax();
include(MODULES_DIR . DIRECTORY_SEPARATOR . 'geocodexajax.inc.php');
$SMARTY->assign('xajax', $LMS->RunXajax());

if (isset($_POST['netnode']))
{
	$netnodedata = $_POST['netnode'];

	if ($netnodedata['name'] == '')
		$error['name'] = trans('Net node name is required!');

	if ($netnodedata['divisionid'] == '-1')
		$error['divisionid'] = trans('Division is required!');

	if ($netnodedata['invprojectid'] == '-1') { // new investment project
		if (!strlen(trim($netnodedata['projectname']))) {
		 $error['projectname'] = trans('Project name is required');
		}
		if ($DB->GetOne("SELECT * FROM invprojects WHERE name=? AND type<>?",
			array($netnodedata['projectname'], INV_PROJECT_SYSTEM)))
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
		if (intval($netnodedata['invprojectid']) == -1) {
			$DB->Execute("INSERT INTO invprojects (name, type) VALUES (?, ?)",
				array($netnodedata['projectname'], INV_PROJECT_REGULAR));
			$netnodedata['invprojectid'] = $DB->GetLastInsertID('invprojects');
		}

		$netnodeid = $LMS->NetNodeAdd($netnodedata);
		$SESSION->redirect('?m=netnodeinfo&id=' . $netnodeid);
	}

	$SMARTY->assign('error', $error);

} else {
	$netnodedata = array();
	$netnodedata['uip'] = 0;
	$netnodedata['miar'] = 0;
	$netnodedata['invprojectid'] = '-2'; // no investment project selected
	$netnodedata['ownership'] = 0;
}

$SMARTY->assign('netnode'  , $netnodedata);
$SMARTY->assign('divisions', $LMS->GetDivisions());

$nprojects = $DB->GetAll("SELECT * FROM invprojects WHERE type<>? ORDER BY name",
	array(INV_PROJECT_SYSTEM));

$layout['pagetitle'] = trans('New Net Device Node');
$SMARTY->assign('NNprojects',$nprojects);

$SMARTY->display('netnode/netnodeadd.html');

?>
