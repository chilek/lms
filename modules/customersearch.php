<?php

/*
 * LMS version 1.9-cvs
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
	$customersearch = $_POST['search'];

if(!isset($customersearch))
	$SESSION->restore('customersearch', $customersearch);
else
	$SESSION->save('customersearch', $customersearch);

if(!isset($_GET['o']))
	$SESSION->restore('uslo', $o);
else
	$o = $_GET['o'];
$SESSION->save('uslo', $o);

if(!isset($_POST['s']))
	$SESSION->restore('usls', $s);
else
	$s = $_POST['s'];
$SESSION->save('usls', $s);

if(!isset($_POST['n']))
	$SESSION->restore('usln', $n);
else
	$n = $_POST['n'];
$SESSION->save('usln', $n);

if(!isset($_POST['g']))
	$SESSION->restore('uslg', $g);
else
	$g = $_POST['g'];
$SESSION->save('uslg', $g);

if(!isset($_POST['k']))
	$SESSION->restore('uslk', $k);
else
	$k = $_POST['k'];
$SESSION->save('uslk', $k);

if(isset($_GET['search']))
{
	$layout['pagetitle'] = trans('Customer Search Results');
	$customerlist = $LMS->GetCustomerList($o, $s, $n, $g, $customersearch, NULL, $k);
	
	$listdata['total'] = $customerlist['total'];
	$listdata['direction'] = $customerlist['direction'];
	$listdata['order'] = $customerlist['order'];
	$listdata['state'] = $customerlist['state'];
	$listdata['network'] = $customerlist['network'];
	$listdata['customergroup'] = $customerlist['customergroup'];
	$listdata['below'] = $customerlist['below'];
	$listdata['over'] = $customerlist['over'];
	
	unset($customerlist['total']);
	unset($customerlist['state']);
	unset($customerlist['network']);
	unset($customerlist['customergroup']);
	unset($customerlist['direction']);
	unset($customerlist['order']);
	unset($customerlist['below']);
	unset($customerlist['over']);

	if (! isset($_GET['page']))
		$SESSION->restore('uslp', $_GET['page']);

	$page = (! $_GET['page'] ? 1 : $_GET['page']); 
	$pagelimit = (!isset($LMS->CONFIG['phpui']['customerlist_pagelimit']) ? $listdata['total'] : $LMS->CONFIG['phpui']['customerlist_pagelimit']);
	$start = ($page - 1) * $pagelimit;

	$SESSION->save('uslp', $page);
		
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
		$SMARTY->display('customersearchresults.html');
}
else
{
	$layout['pagetitle'] = trans('Customer Search');
	
	$SESSION->remove('uslp');
	
	$SMARTY->assign('networks', $LMS->GetNetworks());
	$SMARTY->assign('customergroups', $LMS->CustomergroupGetAll());
	$SMARTY->assign('k', $k);
	$SMARTY->display('customersearch.html');
}

?>


