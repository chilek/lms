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

$state = $DB->GetRow('SELECT * FROM states WHERE id=?', array($_GET['id']));

$name = $state['name'];

$stateedit = isset($_POST['stateedit']) ? $_POST['stateedit'] : NULL;

if(sizeof($stateedit)) 
{
	$stateedit['name'] = trim($stateedit['name']);
	$stateedit['description'] = trim($stateedit['description']);
	$stateedit['id'] = $state['id'];

	if($stateedit['name'] == '')
		$error['name'] = trans('State name is required!');

	if(!$error)
	{
		$DB->Execute('UPDATE states SET name=?, description=? WHERE id=?',
			    array(
				    $stateedit['name'],
				    $stateedit['description'],
				    $stateedit['id']
				    ));
		
		$SESSION->redirect('?m=statelist');
	}
	
	$state = $stateedit;
}	

$layout['pagetitle'] = trans('State Edit: $a', $name);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('stateedit', $state);
$SMARTY->assign('error', $error);
$SMARTY->display('stateedit.html');

?>
