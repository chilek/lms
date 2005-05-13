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

function GetAliasList($order='login,asc', $user=NULL, $kind=NULL, $domain='')
{
	global $LMS;

	list($order,$direction) = sscanf($order, '%[^,],%s');

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

if(!isset($_GET['o']))
	$SESSION->restore('alo', $o);
else
	$o = $_GET['o'];
$SESSION->save('alo', $o);

if(!isset($_GET['u']))
	$SESSION->restore('alu', $u);
else
	$u = $_GET['u'];
$SESSION->save('alu', $u);

if(!isset($_GET['k']))
	$SESSION->restore('alk', $k);
else
	$k = $_GET['k'];
$SESSION->save('alk', $k);

if(!isset($_GET['d']))
	$SESSION->restore('ald', $d);
else
	$d = $_GET['d'];
$SESSION->save('ald', $d);

if ($SESSION->is_set('allp') && !isset($_GET['page']))
	$SESSION->restore('allp', $_GET['page']);
	    
$page = (!isset($_GET['page']) ? 1 : $_GET['page']); 
$pagelimit = (!isset($LMS->CONFIG['phpui']['aliaslist_pagelimit']) ? $listdata['total'] : $LMS->CONFIG['phpui']['aliaslist_pagelimit']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('allp', $page);

$layout['pagetitle'] = trans('Aliases List');

$aliaslist = GetAliasList($o, $u, $k, $d);
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

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

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
