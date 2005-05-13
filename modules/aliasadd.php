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

function AliasExists($login, $account)
{
	global $LMS;
	return ($LMS->DB->GetOne('SELECT id FROM aliases WHERE login = ? AND accountid = ?', array($login, $account)) ? TRUE : FALSE);
}

function AccountExistsInDomain($login, $domain)
{
	global $LMS;
	return ($LMS->DB->GetOne('SELECT id FROM passwd WHERE login = ? AND domainid = ?', array($login, $domain)) ? TRUE : FALSE);
}

function AliasExistsInDomain($login, $domain)
{
	global $LMS;
	return ($LMS->DB->GetOne('SELECT 1 FROM aliases, passwd WHERE accountid = passwd.id AND aliases.login = ? AND domainid = ?', array($login, $domain)) ? TRUE : FALSE);
}

$aliasadd = isset($_POST['aliasadd']) ? $_POST['aliasadd'] : NULL;

if(sizeof($aliasadd)) 
{
	$aliasadd['login'] = trim($aliasadd['login']);

	if($aliasadd['login']=='' && $aliasadd['accountid']==0)
	{
		$SESSION->redirect('?m=aliaslist');
	}
	
	if($aliasadd['login'] == '')
		$error['login'] = trans('You must specify alias name!');
	elseif(!eregi("^[a-z0-9._-]+$", $aliasadd['login']))
    	    $error['login'] = trans('Login contains forbidden characters!');
	elseif($aliasadd['accountid'])
	{
		if(AliasExists($aliasadd['login'], $aliasadd['accountid']))
			$error['login'] = trans('This account has alias with specified name!');
		else
		{
			$domain = $LMS->DB->GetOne('SELECT domainid FROM passwd WHERE id = ?', array($aliasadd['accountid']));
			
			if($aliasadd['accountid'] && AliasExistsInDomain($aliasadd['login'], $domain))
				$error['login'] = trans('Alias with specified login already exists in that domain!');
			elseif($aliasadd['accountid'] && AccountExistsInDomain($aliasadd['login'], $domain))
				$error['login'] = trans('Account with specified login already exists in that domain!');
		}
	}
		
	if(!$aliasadd['accountid'])
		$error['accountid'] = trans('You must select account for alias!');
	
	if(!$error)
	{
		$LMS->DB->Execute('INSERT INTO aliases (login, accountid) VALUES (?,?)',
				    array($aliasadd['login'], $aliasadd['accountid']));
		$LMS->SetTS('aliases');
		
		if(!isset($aliasadd['reuse']))
		{
			$SESSION->redirect('?m=aliaslist');
		}
		unset($aliasadd['login']);
	}
}	

if(isset($_GET['accountid']))
	$aliasadd['accountid'] = $_GET['accountid'];

$layout['pagetitle'] = trans('New Alias');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('aliasadd', $aliasadd);
$SMARTY->assign('error', $error);
$SMARTY->assign('domainlist', $LMS->DB->GetAll('SELECT id, name FROM domains ORDER BY name'));
$SMARTY->assign('accountlist', $LMS->DB->GetAll('SELECT passwd.id AS id, login, domains.name AS domain FROM passwd, domains WHERE domainid = domains.id ORDER BY login, domains.name'));
$SMARTY->assign('layout',$layout);
$SMARTY->display('aliasadd.html');

?>
