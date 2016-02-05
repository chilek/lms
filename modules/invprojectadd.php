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

if(!empty($_POST['invprojectadd'])) 
{
	$invproject = $_POST['invprojectadd'];
			
	if($invproject['name']=='')
	{
		$SESSION->redirect('?m=invprojectadd');
	}

	if($DB->GetOne('SELECT 1 FROM invprojects WHERE name = ?', array($invproject['name'])))
		$error['name'] = trans('Investment project with specified name already exists!');
		
	if (!$error) {
		$args = array(
			'name' => $invproject['name'],
			'divisionid' => $invproject['divisionid'],
			'type' => INV_PROJECT_REGULAR,
		);
		$DB->Execute('INSERT INTO invprojects (name, divisionid, type)
			VALUES (?, ?, ?)', array_values($args));

		if(!isset($invproject['reuse']))
		{
			$SESSION->redirect('?m=invprojectlist');
		}
	}
}	

$layout['pagetitle'] = trans('New investment project');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('invprojectadd', $invproject);
$SMARTY->assign('divisions', $DB->GetAll('SELECT id, shortname, status FROM divisions ORDER BY shortname'));
$SMARTY->assign('error', $error);
$SMARTY->display('invproject/invprojectadd.html');

?>
