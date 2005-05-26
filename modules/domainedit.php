<?php

/*
 * LMS version 1.7-cvs
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
	return $DB->GetOne('SELECT id FROM domains WHERE name = ?', array($name));
}

function DomainExists($id)
{
	global $LMS;
	return ($DB->GetOne('SELECT id FROM domains WHERE id = ?', array($id)) ? TRUE : FALSE);
}

$id = $_GET['id'];

if($id && !DomainExists($id))
{
	$SESSION->redirect('?'.$SESSION->get('backto'));
}

$domain = $DB->GetRow('SELECT id, name, description FROM domains WHERE id = ?', array($id));

$layout['pagetitle'] = trans('Domain Edit: $0', $domain['name']);

if(isset($_POST['domain']))
{
	$olddomain = $domain['name'];
	$domain = $_POST['domain'];
	$domain['name'] = trim($domain['name']);
	$domain['description'] = trim($domain['description']);
	$domain['id'] = $id;
	
	if($domain['name']=='' && $domain['description']=='')
	{
		$SESSION->redirect('?'.$SESSION->get('backto'));
	}
	
	if($domain['name'] == '')
		$error['name'] = trans('Domain name is required!');
	elseif($olddomain != $domain['name'] && GetDomainIdByName($domain['name']))
		$error['name'] = trans('Domain with specified name exists!');

	if(!$error)
	{
		$DB->Execute('UPDATE domains SET name = ?, description = ? WHERE id = ?', 
			array(	$domain['name'],
				$domain['description'],
				$domain['id']
				));
		$LMS->SetTS('domains');
		$SESSION->redirect('?m=domainlist');
	}
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('error', $error);
$SMARTY->assign('domain', $domain);
$SMARTY->assign('layout', $layout);
$SMARTY->display('domainedit.html');

?>
