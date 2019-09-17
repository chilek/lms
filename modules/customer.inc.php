<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

if ($layout['module'] != 'customeredit') {
    $customerinfo = $LMS->GetCustomer($customerid);

    if (!$customerinfo) {
        $SESSION->redirect('?m=customerlist');
    }

    $SMARTY->assignByRef('customerinfo', $customerinfo);
}

if (isset($_GET['aggregate_documents'])) {
    $aggregate_documents = !empty($_GET['aggregate_documents']);
} else {
    $aggregate_documents = ConfigHelper::checkConfig('phpui.aggregate_documents');
}

$commited             = !empty($_GET['commited']) ? true : false;
$allevents            = isset($_GET['allevents']) && !empty($_GET['allevents']);
$assignments          = $LMS->GetCustomerAssignments($customerid, true, false);
$customergroups       = $LMS->CustomergroupGetForCustomer($customerid);
$othercustomergroups  = $LMS->GetGroupNamesWithoutCustomer($customerid);
$balancelist          = $LMS->GetCustomerBalanceList($customerid, null, 'ASC', $aggregate_documents);
$customervoipaccounts = $LMS->GetCustomerVoipAccounts($customerid);
$documents            = $LMS->GetDocuments($customerid, 10);
$taxeslist            = $LMS->GetTaxes();
$allnodegroups        = $LMS->GetNodeGroupNames();
$messagelist          = $LMS->GetMessages($customerid);
$params = array(
    'customerid' => $customerid,
);
if ($allevents) {
    $params['closed'] = '';
}
$eventlist            = $LMS->EventSearch($params, 'date,desc', true);
$customernodes        = $LMS->GetCustomerNodes($customerid);
$customernetnodes = $LMS->GetCustomerNetNodes($customerid);

// prepare node assignments array which allows to easily map nodes to assignments
$nodeassignments = array();
if (!empty($assignments)) {
    foreach ($assignments as $assignment) {
        if (!empty($assignment['nodes'])) {
            foreach ($assignment['nodes'] as $node) {
                if (!isset($nodeassignments[$node['id']])) {
                    $nodeassignments[$node['id']] = array();
                }
                $nodeassignments[$node['id']][] = $assignment;
            }
        }
    }
}

$customernetworks     = $LMS->GetCustomerNetworks($customerid, 10);

$userid = Auth::GetCurrentUser();
$user_permission_checks = ConfigHelper::checkConfig('phpui.helpdesk_additional_user_permission_checks');
$customerstats = array(
    'tickets' => $DB->GetRow('SELECT COUNT(*) AS "all", SUM(CASE WHEN state < ? THEN 1 ELSE 0 END) AS notresolved
		FROM rttickets t
		LEFT JOIN rtrights r ON r.queueid = t.queueid AND r.userid = ?
		WHERE (r.queueid IS NOT NULL' . ($user_permission_checks ? ' OR t.owner = ' . $userid . ' OR t.verifierid = ' . $userid : '') . ')'
        . (!ConfigHelper::checkConfig('privileges.superuser') ? ' AND t.deleted = 0': '')
        . ' AND customerid = ' . intval($customerid), array(RT_RESOLVED, $userid)),
    'domains' => $DB->GetOne('SELECT COUNT(*) FROM domains WHERE ownerid = ?', array($customerid)),
    'accounts' => $DB->GetOne('SELECT COUNT(*) FROM passwd WHERE ownerid = ?', array($customerid))
);

$customerdevices = $LMS->GetNetDevList('name,asc', array('ownerid' => intval($customerid)));
unset($customerdevices['total']);
unset($customerdevices['order']);
unset($customerdevices['direction']);

$counter = count($customerdevices);
for ($i=0; $i<$counter; ++$i) {
    $customerdevices[$i]['ips'] = $LMS->GetNetDevIPs($customerdevices[$i]['id']);
}

if ($SYSLOG && (ConfigHelper::checkConfig('privileges.superuser') || ConfigHelper::checkConfig('privileges.transaction_logs'))) {
    $trans = $SYSLOG->GetTransactions(array('key' => SYSLOG::getResourceKey(SYSLOG::RES_CUST), 'value' => $customerid, 'limit' => 300));
    if (!empty($trans)) {
        foreach ($trans as $idx => $tran) {
            $SYSLOG->DecodeTransaction($trans[$idx]);
        }
    }
    $SMARTY->assign('transactions', $trans);
    $SMARTY->assign('resourcetype', SYSLOG::RES_CUST);
    $SMARTY->assign('resourceid', $customerid);
}

if (!empty($documents)) {
    $SMARTY->assign('docrights', $DB->GetAllByKey('SELECT doctype, rights
        FROM docrights WHERE userid = ? AND rights > 1', 'doctype', array(Auth::GetCurrentUser())));
}

// try to determine preselected cash registry numberplan for instant cash receipt creations
$cashregistries = $LMS->GetCashRegistries($customerid);
if (!empty($cashregistries)) {
    if (count($cashregistries) == 1) {
        $SMARTY->assign('instantpayment', 1);
    } else {
        $cashregistries = array_filter($cashregistries, function ($cashreg) {
            return !empty($cashreg['isdefault']);
        });
        if (count($cashregistries) == 1) {
            $SMARTY->assign('instantpayment', 1);
        }
    }
}

// prepare saved receipt to print
if ($receipt = $SESSION->get('receiptprint')) {
    $SMARTY->assign('receipt', $receipt);
    $SESSION->remove('receiptprint');
}

$SMARTY->assign(array(
    'id' => $customerinfo['id'],
    'objectid' => $customerinfo['id'],
    'aggregate_documents' => $aggregate_documents,
    'commited' => $commited,
    'allevents' => $allevents,
    'time' => $SESSION->get('addbt'),
    'taxid' => $SESSION->get('addbtax'),
    'comment' => $SESSION->get('addbc'),
    'sourceid' => $SESSION->get('addsource'),
));

$SMARTY->assign('sourcelist', $DB->GetAll('SELECT id, name FROM cashsources WHERE deleted = 0 ORDER BY name'));
$SMARTY->assignByRef('customernodes', $customernodes);
$SMARTY->assignByRef('customernetworks', $customernetworks);
$SMARTY->assignByRef('customerdevices', $customerdevices);
$SMARTY->assignByRef('customernetnodes', $customernetnodes);
$SMARTY->assignByRef('customerstats', $customerstats);
$SMARTY->assignByRef('assignments', $assignments);
$SMARTY->assignByRef('nodeassignments', $nodeassignments);
$SMARTY->assignByRef('customergroups', $customergroups);
$SMARTY->assignByRef('othercustomergroups', $othercustomergroups);
$SMARTY->assignByRef('balancelist', $balancelist);
$SMARTY->assignByRef('customervoipaccounts', $customervoipaccounts);
$SMARTY->assignByRef('documents', $documents);
$SMARTY->assignByRef('taxeslist', $taxeslist);
$SMARTY->assignByRef('allnodegroups', $allnodegroups);
$SMARTY->assignByRef('messagelist', $messagelist);
$SMARTY->assignByRef('eventlist', $eventlist);
