<?php

/*
 * LMS version 1.6-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

function GetDomainIdByName($name)
{
	global $LMS;
	return $LMS->DB->GetOne('SELECT id FROM domains WHERE name = ?', array($name));
}

if(isset($_POST['domainadd']))
{
	$domainadd = $_POST['domainadd'];

	$domainadd['name'] = trim($domainadd['name']);
	$domainadd['description'] = trim($domainadd['description']);
	
	if($domainadd['name']=='' && $domainadd['description']=='')
	{
		$SESSION->redirect('?m=domainlist');
	}
	
	if($domainadd['name'] == '')
		$error['name'] = trans('Domain name is required!');
	elseif(GetDomainIdByName($domainadd['name']))
		$error['name'] = trans('Domain with specified name exists!');
	
	if(!$error)
	{
		$LMS->DB->Execute('INSERT INTO domains (name, description) VALUES (?,?)',
				    array($domainadd['name'], $domainadd['description']));
		$LMS->SetTS('domains');
		
		if(!isset($domainadd['reuse']))
		{
			$SESSION->redirect('?m=domainlist');
		}
		
		unset($domainadd['name']);
		unset($domainadd['description']);
	}
	$SMARTY->assign('domainadd', $domainadd);
}	

$layout['pagetitle'] = trans('New Domain');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('error', $error);
$SMARTY->assign('layout', $layout);
$SMARTY->display('domainadd.html');

?>
