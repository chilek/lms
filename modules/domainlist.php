<?php

/*
 * LMS version 1.5-cvs
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

function GetDomainList($order='name,asc')
{
	global $LMS;

	list($order,$direction) = explode(',',$order);

	($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

	switch($order)
	{
		case 'id':
			$sqlord = " ORDER BY id $direction";
		break;
		case 'description':
			$sqlord = " ORDER BY description $direction";
		break;
		default:
			$sqlord = " ORDER BY name $direction";
		break;
	}

	$list = $LMS->DB->GetAll('SELECT id, name, description FROM domains'.($sqlord != '' ? $sqlord : ''));
	
	$list['total'] = sizeof($list);
	$list['order'] = $order;
	$list['direction'] = $direction;

	return $list;
}

if(!isset($_GET['o']))
	$o = $_SESSION['dlo'];
else
	$o = $_GET['o'];
$_SESSION['dlo'] = $o;

if (isset($_SESSION['dlp']) && !isset($_GET['page']))
	$_GET['page'] = $_SESSION['dlp'];
	    
$page = (! $_GET['page'] ? 1 : $_GET['page']); 
$pagelimit = (! $LMS->CONFIG['phpui']['domainlist_pagelimit'] ? $listdata['total'] : $LMS->CONFIG['phpui']['domainlist_pagelimit']);
$start = ($page - 1) * $pagelimit;

$_SESSION['dlp'] = $page;

$layout['pagetitle'] = trans('Domains List');

$domainlist = GetDomainList($o);
$listdata['total'] = $domainlist['total'];
$listdata['order'] = $domainlist['order'];
$listdata['direction'] = $domainlist['direction'];
unset($domainlist['total']);
unset($domainlist['order']);
unset($domainlist['direction']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('start', $start);
$SMARTY->assign('domainlist', $domainlist);
$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('layout',$layout);
$SMARTY->display('domainlist.html');

?>
