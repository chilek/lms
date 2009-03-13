<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2009 LMS Developers
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

if(!eregi("^[0-9]+$",$_GET['id']))
{
	$SESSION->redirect('?m=customerlist');
}

if($LMS->CustomerExists($_GET['id']) == 0)
{
	$SESSION->redirect('?m=customerlist');
}

$expired = isset($_GET['expired']) ? $_GET['expired'] : false;

$customerinfo = $LMS->GetCustomer($_GET['id']);
$assigments = $LMS->GetCustomerAssignments($_GET['id'], $expired);
$customergroups = $LMS->CustomergroupGetForCustomer($_GET['id']);
$othercustomergroups = $LMS->GetGroupNamesWithoutCustomer($_GET['id']);
$balancelist = $LMS->GetCustomerBalanceList($_GET['id']);
$customernodes = $LMS->GetCustomerNodes($_GET['id']);
$customervoipaccounts = $LMS->GetCustomerVoipAccounts($_GET['id']);
$tariffs = $LMS->GetTariffs();
$documents = $LMS->GetDocuments($_GET['id'], 10);
$taxeslist = $LMS->GetTaxes();
$allnodegroups = $LMS->GetNodeGroupNames();
$eventlist = $LMS->EventSearch(array('customerid' => $_GET['id']), 'date,desc', true);

if($customerinfo['cutoffstop'] > mktime(0,0,0))
        $customerinfo['cutoffstopnum'] = floor(($customerinfo['cutoffstop'] - mktime(23,59,59))/86400);
		
if(isset($CONFIG['phpui']['ewx_support']) && chkconfig($CONFIG['phpui']['ewx_support']))
{
        $SMARTY->assign('ewx_channelid', $DB->GetOne('SELECT MAX(channelid) FROM ewx_stm_nodes, nodes
                                        WHERE nodeid = nodes.id AND ownerid = ?', array($_GET['id'])));
}

$time = $SESSION->get('addbt');
$value = $SESSION->get('addbv');
$taxid = $SESSION->get('addbtax');
$comment = $SESSION->get('addbc');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = trans('Customer Info: $0',$customerinfo['customername']);

$customernodes['ownerid'] = $_GET['id'];
$SMARTY->assign(
		array(
			'customernodes' => $customernodes,
			'customervoipaccounts' => $customervoipaccounts,
			'balancelist' => $balancelist,
			'assignments' => $assigments,
			'customergroups' => $customergroups,
			'allnodegroups' => $allnodegroups,
			'othercustomergroups' => $othercustomergroups,
			'customerinfo' => $customerinfo,
			'tariffs' => $tariffs,
			'documents' => $documents,
			'taxeslist' => $taxeslist,
			'eventlist' => $eventlist,
			'expired' => $expired,
			'time' => $time,
			'value' => $value,
			'taxid' => $taxid,
			'comment' => $comment,
		     )
		);
$SMARTY->display('customerinfo.html');

?>
