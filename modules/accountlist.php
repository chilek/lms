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

function GetAccountList($order='username,asc', $user=NULL, $type=NULL)
{
	global $LMS;

	list($order,$direction) = explode(',',$order);

	($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

	switch($order)
	{
		case 'id':
			$sqlord = " ORDER BY passwd.id $direction";
		break;
		case 'username':
			$sqlord = " ORDER BY username $direction, login";
		break;
		case 'lastlogin':
			$sqlord = " ORDER BY lastlogin $direction, username, login";
		break;
		case 'domain':
			$sqlord = " ORDER BY domain $direction, login";
		break;
		case 'expdate':
			$sqlord = " ORDER BY expdate $direction, login";
		break;
		default:
			$sqlord = " ORDER BY login $direction, username";
		break;
	}

	$list = $LMS->DB->GetAll(
	        'SELECT passwd.id AS id, ownerid, login, lastlogin, expdate, domain, type, '
		.$LMS->DB->Concat('users.lastname', "' '",'users.name').
		' AS username FROM passwd LEFT JOIN users ON users.id = ownerid WHERE 1=1'
		.($user != '' ? ' AND ownerid = '.$user : '')
		.($type ? ' AND type & '.$type.' = '.$type : '')
		.($sqlord != '' ? $sqlord : '')
		);
	
	$list['total'] = sizeof($list);
	$list['order'] = $order;
	$list['type'] = $type;
	$list['user'] = $user;
	$list['direction'] = $direction;

	return $list;
}

if(!isset($_GET['o']))
	$o = $_SESSION['alo'];
else
	$o = $_GET['o'];
$_SESSION['alo'] = $o;

if(!isset($_GET['u']))
	$u = $_SESSION['alu'];
else
	$u = $_GET['u'];
$_SESSION['alu'] = $u;

if(!isset($_GET['t']))
	$t = $_SESSION['alt'];
else
	$t = $_GET['t'];
$_SESSION['alt'] = $t;

if (isset($_SESSION['alp']) && !isset($_GET['page']))
	$_GET['page'] = $_SESSION['alp'];
	    
$page = (! $_GET['page'] ? 1 : $_GET['page']); 
$pagelimit = (! $LMS->CONFIG['phpui']['accountlist_pagelimit'] ? $listdata['total'] : $LMS->CONFIG['phpui']['accountlist_pagelimit']);
$start = ($page - 1) * $pagelimit;

$_SESSION['ulp'] = $page;

$layout['pagetitle'] = 'Zarz±dzanie kontami';

$accountlist = GetAccountList($o, $u, $t);
$listdata['total'] = $accountlist['total'];
$listdata['order'] = $accountlist['order'];
$listdata['direction'] = $accountlist['direction'];
$listdata['type'] = $accountlist['type'];
$listdata['user'] = $accountlist['user'];
unset($accountlist['total']);
unset($accountlist['order']);
unset($accountlist['type']);
unset($accountlist['user']);
unset($accountlist['direction']);

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('start', $start);
$SMARTY->assign('accountlist',$accountlist);
$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('userlist',$LMS->GetUserNames());
$SMARTY->assign('layout',$layout);
$SMARTY->display('accountlist.html');

?>
