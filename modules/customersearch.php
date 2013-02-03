<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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
{
	$customersearch = $_POST['search'];

	if(!empty($customersearch['tariffs']))
		$customersearch['tariffs'] = implode(",", $customersearch['tariffs']);
	
	if($customersearch['createdfrom'])
	{
		list($year, $month, $day) = explode('/', $customersearch['createdfrom']);
		$customersearch['createdfrom'] = mktime(0, 0, 0, $month, $day, $year);
	}
	if($customersearch['createdto'])
	{
		list($year, $month, $day) = explode('/', $customersearch['createdto']);
		$customersearch['createdto'] = mktime(23, 59, 59, $month, $day, $year);
	}
	if($customersearch['deletedfrom'])
	{
		list($year, $month, $day) = explode('/', $customersearch['deletedfrom']);
		$customersearch['deletedfrom'] = mktime(0, 0, 0, $month, $day, $year);
	}
	if($customersearch['deletedto'])
	{
		list($year, $month, $day) = explode('/', $customersearch['deletedto']);
		$customersearch['deletedto'] = mktime(23, 59, 59, $month, $day, $year);
	}
}

if(!isset($customersearch))
	$SESSION->restore('customersearch', $customersearch);
else
	$SESSION->save('customersearch', $customersearch);

if(!isset($_GET['o']))
	$SESSION->restore('cslo', $o);
else
	$o = $_GET['o'];
$SESSION->save('cslo', $o);

if(!isset($_POST['s']))
	$SESSION->restore('csls', $s);
else
	$s = $_POST['s'];
$SESSION->save('csls', $s);

if(!isset($_POST['n']))
	$SESSION->restore('csln', $n);
else
	$n = $_POST['n'];
$SESSION->save('csln', $n);

if(!isset($_POST['g']))
	$SESSION->restore('cslg', $g);
else
	$g = $_POST['g'];
$SESSION->save('cslg', $g);

if(!isset($_POST['k']))
	$SESSION->restore('cslk', $k);
else
	$k = $_POST['k'];
$SESSION->save('cslk', $k);

if(!isset($_POST['ng']))
	$SESSION->restore('cslng', $ng);
else
	$ng = $_POST['ng'];
$SESSION->save('cslng', $ng);

if(!isset($_POST['d']))
	$SESSION->restore('csld', $d);
else
	$d = $_POST['d'];
$SESSION->save('csld', $d);

if(isset($_GET['search']))
{
	$layout['pagetitle'] = trans('Customer Search Results');
	$customerlist = $LMS->GetCustomerList($o, $s, $n, $g, $customersearch, NULL, $k, $ng, $d);
	
	$listdata['total'] = $customerlist['total'];
	$listdata['direction'] = $customerlist['direction'];
	$listdata['order'] = $customerlist['order'];
	$listdata['below'] = $customerlist['below'];
	$listdata['over'] = $customerlist['over'];
	$listdata['state'] = $s;
	$listdata['network'] = $n;
	$listdata['customergroup'] = $g;
	$listdata['nodegroup'] = $ng;
	$listdata['division'] = $d;

	unset($customerlist['total']);
	unset($customerlist['state']);
	unset($customerlist['direction']);
	unset($customerlist['order']);
	unset($customerlist['below']);
	unset($customerlist['over']);

	if (! isset($_GET['page']))
		$SESSION->restore('cslp', $_GET['page']);

	$page = (! $_GET['page'] ? 1 : $_GET['page']); 
	$pagelimit = (!isset($CONFIG['phpui']['customerlist_pagelimit']) ? $listdata['total'] : $CONFIG['phpui']['customerlist_pagelimit']);
	$start = ($page - 1) * $pagelimit;

	$SESSION->save('cslp', $page);

	$SMARTY->assign('customerlist',$customerlist);
	$SMARTY->assign('listdata',$listdata);
	$SMARTY->assign('pagelimit',$pagelimit);
	$SMARTY->assign('page',$page);
	$SMARTY->assign('start',$start);

	if(isset($_GET['print']))
	{
		$SMARTY->display('printcustomerlist.html');
	}
	elseif($listdata['total'] == 1)
	{
		$SESSION->redirect('?m=customerinfo&id='.$customerlist[0]['id']);
	}
	else
	{
		$SMARTY->assign('customergroups', $LMS->CustomergroupGetAll());
		$SMARTY->display('customersearchresults.html');
	}
}
else
{
	$layout['pagetitle'] = trans('Customer Search');
	
	$SESSION->remove('cslp');
	
	$SMARTY->assign('networks', $LMS->GetNetworks());
	$SMARTY->assign('customergroups', $LMS->CustomergroupGetAll());
	$SMARTY->assign('nodegroups', $LMS->GetNodeGroupNames());
	$SMARTY->assign('cstateslist', $LMS->GetCountryStates());
	$SMARTY->assign('tariffs', $LMS->GetTariffs());
	$SMARTY->assign('divisions', $DB->GetAll('SELECT id, shortname FROM divisions ORDER BY shortname'));
	$SMARTY->assign('k', $k);
	$SMARTY->display('customersearch.html');
}

?>
