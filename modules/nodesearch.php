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

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if(isset($_POST['search']))
	$search = $_POST['search'];

if(!isset($search))
	$SESSION->restore('nodesearch', $search);
else
	$SESSION->save('nodesearch', $search);
if(!isset($_GET['o']))
	$SESSION->restore('nslo', $o);
else
	$o = $_GET['o'];
$SESSION->save('nslo', $o);

if(!isset($_POST['k']))
	$SESSION->restore('nslk', $k);
else
	$k = $_POST['k'];
$SESSION->save('nslk', $k);

if(isset($_GET['search'])) 
{
	$layout['pagetitle'] = trans('Nodes Search Results');

	$nodelist = $LMS->GetNodeList($o, $search, $k);

	$listdata['total'] = $nodelist['total'];
	$listdata['order'] = $nodelist['order'];
	$listdata['direction'] = $nodelist['direction'];
	$listdata['totalon'] = $nodelist['totalon'];
	$listdata['totaloff'] = $nodelist['totaloff'];

	unset($nodelist['total']);
	unset($nodelist['order']);
	unset($nodelist['direction']);
	unset($nodelist['totalon']);
	unset($nodelist['totaloff']);
	
	if ($SESSION->is_set('nslp') && !isset($_GET['page']))
		$SESSION->restore('nslp', $_GET['page']);
		
	$page = (!isset($_GET['page']) ? 1 : $_GET['page']);
	
	$pagelimit = (! $LMS->CONFIG['phpui']['nodelist_pagelimit'] ? $listdata['total'] : $LMS->CONFIG['phpui']['nodelist_pagelimit']);
	$start = ($page - 1) * $pagelimit;
	$SESSION->save('nslp', $page);
	
	$SMARTY->assign('page',$page);
	$SMARTY->assign('pagelimit',$pagelimit);
	$SMARTY->assign('start',$start);
	$SMARTY->assign('nodelist',$nodelist);
	$SMARTY->assign('listdata',$listdata);
	
	if(isset($_GET['print']))
		$SMARTY->display('printnodelist.html');
	elseif($listdata['total']==1)
		$SESSION->redirect('?m=nodeinfo&id='.$nodelist[0]['id']);
	else
		$SMARTY->display('nodesearchresults.html');
}
else
{
	$layout['pagetitle'] = trans('Nodes Search');

	$SMARTY->assign('k',$k);
	$SMARTY->display('nodesearch.html');
}

?>
