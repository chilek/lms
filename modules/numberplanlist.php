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

function GetNumberPlanList($filter=NULL)
{
	// Order proably doesn't have any place in here
	global $DB;

	$list = $DB->GetAll('SELECT * FROM numberplans ORDER BY id');

	foreach ($list as $item => $itemno) 
		{
			//temporary hack here, there should be right join with select max 
			// order by numberingplan from documents where type = 
			$list[$item]['nextid'] =1;
			// also a hack
			$list[$item]['issued'] =rand(5);
		}
	
	$list['total'] = sizeof($list);
	$list['filter'] = $filter;

	return $list;
}

if(!isset($_GET['o']))
	$SESSION->restore('dlo', $o);
else
	$o = $_GET['o'];
$SESSION->save('dlo', $o);

if ($SESSION->is_set('dlp') && !isset($_GET['page']))
	$SESSION->restore('dlp', $_GET['page']);
	    
$page = (!isset($_GET['page']) ? 1 : $_GET['page']); 
$pagelimit = (!isset($LMS->CONFIG['phpui']['numberplanlist_pagelimit']) ? $listdata['total'] : $LMS->CONFIG['phpui']['numberplanlist_pagelimit']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('dlp', $page);

$layout['pagetitle'] = trans('Number Plan List');

$numberplanlist = GetNumberPlanList();
$listdata['total'] = $numberplanlist['total'];
$listdata['order'] = $numberplanlist['order'];
$listdata['direction'] = $numberplanlist['direction'];
unset($numberplanlist['total']);
unset($numberplanlist['order']);
unset($numberplanlist['direction']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$today= array ();

// for exaple substitution:
$today['day'] = date('d');
$today['month'] = date('m');
$today['year'] = date('y');
$today['year_c'] = date('Y');

$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('start', $start);
$SMARTY->assign('numberplanlist', $numberplanlist);
$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('layout',$layout);
$SMARTY->assign('today',$today);
$SMARTY->display('numberplanlist.html');

?>
