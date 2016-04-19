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

if($layout['module'] != 'customeredit')
{
	$customerinfo = $LMS->GetCustomer($customerid);

    if(!$customerinfo)
    {
        $SESSION->redirect('?m=customerlist');
    }

	$SMARTY->assignByRef('customerinfo', $customerinfo);

}

$expired = !empty($_GET['expired']) ? true : false;
$assignments = $LMS->GetCustomerAssignments($customerid, !empty($expired) ? $expired : NULL);
$customergroups = $LMS->CustomergroupGetForCustomer($customerid);
$othercustomergroups = $LMS->GetGroupNamesWithoutCustomer($customerid);
$balancelist = $LMS->GetCustomerBalanceList($customerid);
$customervoipaccounts = $LMS->GetCustomerVoipAccounts($customerid);
$documents = $LMS->GetDocuments($customerid, 10);
$taxeslist = $LMS->GetTaxes();
$allnodegroups = $LMS->GetNodeGroupNames();
$messagelist = $LMS->GetMessages($customerid, 10);
$eventlist = $LMS->EventSearch(array('customerid' => $customerid), 'date,desc', true);
$customernodes = $LMS->GetCustomerNodes($customerid);
$customernetworks = $LMS->GetCustomerNetworks($customerid, 10);
$customerstats = array(
	'tickets' => $DB->GetRow('SELECT COUNT(*) AS all, SUM(CASE WHEN state < ? THEN 1 ELSE 0 END) AS notresolved
		FROM rttickets WHERE customerid = ?', array(RT_RESOLVED, $customerid)),
	'domains' => $DB->GetOne('SELECT COUNT(*) FROM domains WHERE ownerid = ?', array($customerid)),
	'accounts' => $DB->GetOne('SELECT COUNT(*) FROM passwd WHERE ownerid = ?', array($customerid))
);

if ($SYSLOG && (ConfigHelper::checkConfig('privileges.superuser') || ConfigHelper::checkConfig('privileges.transaction_logs'))) {
	$trans = $SYSLOG->GetTransactions(array('key' => $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST], 'value' => $customerid));
	if (!empty($trans))
		foreach ($trans as $idx => $tran)
			$SYSLOG->DecodeTransaction($trans[$idx]);
	$SMARTY->assign('transactions', $trans);
	$SMARTY->assign('resourcetype', SYSLOG_RES_CUST);
	$SMARTY->assign('resourceid', $customerid);
}

if(!empty($documents))
{
        $SMARTY->assign('docrights', $DB->GetAllByKey('SELECT doctype, rights
	        FROM docrights WHERE userid = ? AND rights > 1', 'doctype', array($AUTH->id)));
}

$SMARTY->assign(array(
	'expired' => $expired, 
	'time' => $SESSION->get('addbt'),
	'taxid' => $SESSION->get('addbtax'),
	'comment' => $SESSION->get('addbc'),
	'sourceid' => $SESSION->get('addsource'),
));

$SMARTY->assign('sourcelist', $DB->GetAll('SELECT id, name FROM cashsources WHERE deleted = 0 ORDER BY name'));
$SMARTY->assignByRef('customernodes', $customernodes);
$SMARTY->assignByRef('customernetworks', $customernetworks);
$SMARTY->assignByRef('customerstats', $customerstats);
$SMARTY->assignByRef('assignments', $assignments);
$SMARTY->assignByRef('customergroups', $customergroups);
$SMARTY->assignByRef('othercustomergroups', $othercustomergroups);
$SMARTY->assignByRef('balancelist', $balancelist);
$SMARTY->assignByRef('customervoipaccounts', $customervoipaccounts);
$SMARTY->assignByRef('documents', $documents);
$SMARTY->assignByRef('taxeslist', $taxeslist);
$SMARTY->assignByRef('allnodegroups', $allnodegroups);
$SMARTY->assignByRef('messagelist', $messagelist);
$SMARTY->assignByRef('eventlist', $eventlist);

?>
