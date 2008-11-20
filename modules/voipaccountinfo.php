<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2008 LMS Developers
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

if(!eregi('^[0-9]+$',$_GET['id']))
{
	$SESSION->redirect('?m=voipaccountlist');
}

if(!$LMS->VoipAccountExists($_GET['id']))
	if(isset($_GET['ownerid']))
	{
		$SESSION->redirect('?m=customerinfo&id='.$_GET['ownerid']);
	}
	else
	{
		$SESSION->redirect('?m=voipaccountlist');
	}

$voipaccountid = $_GET['id'];
$ownerid = $LMS->GetVoipAccountOwner($voipaccountid);
$tariffs = $LMS->GetTariffs();
$customerinfo = $LMS->GetCustomer($ownerid);
$voipaccountinfo = $LMS->GetVoipAccount($voipaccountid);
$balancelist = $LMS->GetCustomerBalanceList($ownerid);
$assignments = $LMS->GetCustomerAssignments($ownerid);
$documents = $LMS->GetDocuments($ownerid, 10);
$customergroups = $LMS->CustomergroupGetForCustomer($ownerid);
$othercustomergroups = $LMS->GetGroupNamesWithoutCustomer($ownerid);
$taxeslist = $LMS->GetTaxes();
$customernodes = $LMS->GetCustomerNodes($ownerid);
$customervoipaccounts = $LMS->GetCustomerVoipAccounts($ownerid);
$allnodegroups = $LMS->GetNodeGroupNames();
$nodegroups = $LMS->GetNodeGroupNamesByNode($nodeid);
$othernodegroups = $LMS->GetNodeGroupNamesWithoutNode($nodeid);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if(!isset($_GET['ownerid']))
	$SESSION->save('backto', $SESSION->get('backto').'&ownerid='.$ownerid);

if($nodeinfo['netdev'] == 0) 
	$netdevices = $LMS->GetNetDevNames();
else
	$netdevices = $LMS->GetNetDev($nodeinfo['netdev']);

if(isset($CONFIG['phpui']['ewx_support']) && chkconfig($CONFIG['phpui']['ewx_support']))
{
	$SMARTY->assign('ewx_channelid', $DB->GetOne('SELECT MAX(channelid) FROM ewx_stm_nodes, nodes 
					WHERE nodeid = nodes.id AND ownerid = ?', array($ownerid)));
}

$layout['pagetitle'] = trans('Voip Account Info: $0',$voipaccountinfo['login']);

$SMARTY->assign('netdevices',$netdevices);
$SMARTY->assign('balancelist',$balancelist);
$SMARTY->assign('customerinfo',$customerinfo);
$SMARTY->assign('voipaccountinfo',$voipaccountinfo);
$SMARTY->assign('assignments',$assignments);
$SMARTY->assign('customernodes',$customernodes);
$SMARTY->assign('customervoipaccounts',$customervoipaccounts);
$SMARTY->assign('customergroups',$customergroups);
$SMARTY->assign('othercustomergroups',$othercustomergroups);
$SMARTY->assign('allnodegroups',$allnodegroups);
$SMARTY->assign('nodegroups',$nodegroups);
$SMARTY->assign('othernodegroups',$othernodegroups);
$SMARTY->assign('documents', $documents);
$SMARTY->assign('taxeslist', $taxeslist);
$SMARTY->assign('tariffs',$tariffs);
$SMARTY->display('voipaccountinfo.html');

?>
