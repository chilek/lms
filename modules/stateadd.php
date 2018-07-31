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

$stateadd = isset($_POST['stateadd']) ? $_POST['stateadd'] : NULL;

if(count($stateadd)) 
{
	$stateadd['name'] = trim($stateadd['name']);

	if($stateadd['name']=='' && $stateadd['description']=='')
	{
		$SESSION->redirect('?m=statelist');
	}
	
	if($stateadd['name'] == '')
		$error['name'] = trans('State name is required!');

	if (!$error) {
		$args = array(
			'name' => $stateadd['name'],
			'description' => $stateadd['description']
		);
		$DB->Execute('INSERT INTO states (name, description)
				VALUES (?,?)', array_values($args));

		if ($SYSLOG) {
			$args[SYSLOG::RES_STATE] = $DB->GetLastInsertID('states');
			$SYSLOG->AddMessage(SYSLOG::RES_STATE, SYSLOG::OPER_ADD, $args);
		}

		if (!isset($stateadd['reuse']))
			$SESSION->redirect('?m=statelist');

		unset($stateadd['name']);
		unset($stateadd['description']);
	}
}	

$layout['pagetitle'] = trans('New State');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('stateadd', $stateadd);
$SMARTY->assign('error', $error);
$SMARTY->display('state/stateadd.html');

?>
