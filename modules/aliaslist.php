<?php

/*
 * LMS version 1.5-cvs
 *
 *  (C) Copyright 2001-2004 LMS Developers
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

function GetAliasList($order='login,asc', $user=NULL, $kind=NULL, $domain='')
{
	global $LMS;

	list($order,$direction) = explode(',',$order);

	($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

	switch($order)
	{
		case 'id':
			$sqlord = " ORDER BY aliases.id $direction";
		break;
		case 'username':
			$sqlord = " ORDER BY username $direction, aliases.login";
		break;
		case 'domain':
			$sqlord = " ORDER BY domain $direction, aliases.login";
		break;
		case 'expdate':
			$sqlord = " ORDER BY expdate $direction, aliases.login";
		break;
		case 'account':
			$sqlord = " ORDER BY passwd.login $direction, aliases.login";
		break;
		default:
			$sqlord = " ORDER BY aliases.login $direction, domain";
		break;
	}

	$list = $LMS->DB->GetAll(
	        'SELECT aliases.id AS id, passwd.id AS aid, ownerid, aliases.login AS login, passwd.login AS account, expdate, domains.name AS domain, domainid, '
		.$LMS->DB->Concat('users.lastname', "' '",'users.name').
		' AS username FROM aliases 
		LEFT JOIN passwd ON accountid = passwd.id
		LEFT JOIN users ON users.id = ownerid 
		LEFT JOIN domains ON domains.id = domainid 
		WHERE 1=1'
		.($user != '' ? ' AND ownerid = '.$user : '')
		.($kind == 1 ? ' AND expdate!= 0 AND expdate < ?NOW?' : '')
		.($kind == 2 ? ' AND (expdate=0 OR expdate > ?NOW?)' : '')
		.($domain != '' ? ' AND domainid = '.$domain : '')
		.($sqlord != '' ? $sqlord : '')
		);
	
	$list['total'] = sizeof($list);
	$list['order'] = $order;
	$list['kind'] = $kind;
	$list['user'] = $user;
	$list['domain'] = $domain;
	$list['direction'] = $direction;

	return $list;
}

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

if($aliasadd = $_POST['aliasadd']) 
{
	$aliasadd['login'] = trim($aliasadd['login']);

	if($aliasadd['login']=='' && $aliasadd['accountid']==0)
	{
		header('Location: ?m=aliaslist');
		die;
	}
	
	if($aliasadd['login'] == '')
		$error['login'] = 'Nie poda³e¶ nazwy aliasu!';
	elseif(!eregi("^[a-z0-9._-]+$", $aliasadd['login']))
    	    $error['login'] = 'Login zawiera niepoprawne znaki!';
	elseif($aliasadd['accountid'])
	{
		if(AliasExists($aliasadd['login'], $aliasadd['accountid']))
			$error['login'] = 'To konto ma ju¿ alias o podanej nazwie!';
		else
		{
			$domain = $LMS->DB->GetOne('SELECT domainid FROM passwd WHERE id = ?', array($aliasadd['accountid']));
			
			if($aliasadd['accountid'] && AliasExistsInDomain($aliasadd['login'], $domain))
				$error['login'] = 'W tej domenie jest ju¿ alias o podanej nazwie!';
			elseif($aliasadd['accountid'] && AccountExistsInDomain($aliasadd['login'], $domain))
				$error['login'] = 'W tej domenie istnieje konto o podanej nazwie!';
		}
	}
		
	if(!$aliasadd['accountid'])
		$error['accountid'] = 'Musisz wybraæ konto na które ma wskazywaæ alias!';
	
	if(!$error)
	{
		$LMS->DB->Execute('INSERT INTO aliases (login, accountid) VALUES (?,?)',
				    array($aliasadd['login'], $aliasadd['accountid']));
		$LMS->SetTS('aliases');
	}
	else
	{
		$SMARTY->assign('error', $error);
		$SMARTY->assign('aliasadd', $aliasadd);
	}
}	

if(!isset($_GET['o']))
	$o = $_SESSION['allo'];
else
	$o = $_GET['o'];
$_SESSION['allo'] = $o;

if(!isset($_GET['u']))
	$u = $_SESSION['allu'];
else
	$u = $_GET['u'];
$_SESSION['allu'] = $u;

if(!isset($_GET['k']))
	$k = $_SESSION['allk'];
else
	$k = $_GET['k'];
$_SESSION['allk'] = $k;

if(!isset($_GET['d']))
	$d = $_SESSION['alld'];
else
	$d = $_GET['d'];
$_SESSION['alld'] = $d;

if (isset($_SESSION['allp']) && !isset($_GET['page']))
	$_GET['page'] = $_SESSION['allp'];
	    
$page = (! $_GET['page'] ? 1 : $_GET['page']); 
$pagelimit = (! $LMS->CONFIG['phpui']['aliaslist_pagelimit'] ? $listdata['total'] : $LMS->CONFIG['phpui']['aliaslist_pagelimit']);
$start = ($page - 1) * $pagelimit;

$_SESSION['alp'] = $page;

$layout['pagetitle'] = 'Zarz±dzanie aliasami';

$aliaslist = GetAliasList($o, $u, $t, $d);
$listdata['total'] = $aliaslist['total'];
$listdata['order'] = $aliaslist['order'];
$listdata['direction'] = $aliaslist['direction'];
$listdata['kind'] = $aliaslist['kind'];
$listdata['user'] = $aliaslist['user'];
$listdata['domain'] = $aliaslist['domain'];
unset($aliaslist['total']);
unset($aliaslist['order']);
unset($aliaslist['kind']);
unset($aliaslist['user']);
unset($aliaslist['domain']);
unset($aliaslist['direction']);

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('start', $start);
$SMARTY->assign('aliaslist', $aliaslist);
$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('userlist', $LMS->GetUserNames());
$SMARTY->assign('domainlist', $LMS->DB->GetAll('SELECT id, name FROM domains ORDER BY name'));
$SMARTY->assign('accountlist', $LMS->DB->GetAll('SELECT passwd.id AS id, login, domains.name AS domain FROM passwd, domains WHERE domainid = domains.id ORDER BY login, domains.name'));
$SMARTY->assign('layout',$layout);
$SMARTY->display('aliaslist.html');

?>
