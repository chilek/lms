<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2007 LMS Developers
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
	global $DB;
	return $DB->GetOne('SELECT id FROM domains WHERE name = ?', array($name));
}

$domainadd = array();

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
		$DB->Execute('INSERT INTO domains (name, ownerid, description) VALUES (?,?,?)',
				    array($domainadd['name'], 
					    $domainadd['ownerid'], 
					    $domainadd['description']));
		
		if(!isset($domainadd['reuse']))
		{
			$SESSION->redirect('?m=domainlist');
		}
		
		unset($domainadd['name']);
		unset($domainadd['description']);
	}
}	
elseif(isset($_GET['cid']))
{
        $domainadd['ownerid'] = intval($_GET['cid']);
}

$layout['pagetitle'] = trans('New Domain');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('domainadd', $domainadd);
$SMARTY->assign('error', $error);
$SMARTY->assign('customers', $LMS->GetCustomerNames());
$SMARTY->display('domainadd.html');

?>
