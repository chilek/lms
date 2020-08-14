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

if (!isset($resource_tabs['customernotes']) || $resource_tabs['customernotes']) {
    $customernotes = $LMS->getCustomerNotes($customerid);
}

if (!isset($resource_tabs['customerassignments']) || $resource_tabs['customerassignments']) {
    $commited             = !empty($_GET['commited']) ? true : false;
    $assignments = $LMS->GetCustomerAssignments($customerid, true, false);
}
if (!isset($resource_tabs['customergroups']) || $resource_tabs['customergroups']) {
    $customergroups = $LMS->CustomergroupGetForCustomer($customerid);
    $othercustomergroups = $LMS->GetGroupNamesWithoutCustomer($customerid);
}
if (!isset($resource_tabs['customerbalancebox']) || $resource_tabs['customerbalancebox']) {
    if (isset($_GET['aggregate_documents'])) {
        $aggregate_documents = !empty($_GET['aggregate_documents']);
    } else {
        $aggregate_documents = ConfigHelper::checkConfig('phpui.aggregate_documents');
    }

    $balancelist = $LMS->GetCustomerBalanceList($customerid, null, 'ASC', $aggregate_documents);
}
if (!isset($resource_tabs['customervoipaccountsbox']) || $resource_tabs['customervoipaccountsbox']) {
    $customervoipaccounts = $LMS->GetCustomerVoipAccounts($customerid);
}
if (!isset($resource_tabs['customerdocuments']) || $resource_tabs['customerdocuments']) {
    $documents = $LMS->GetDocuments($customerid, 10);

    if (!empty($documents)) {
        $SMARTY->assign('docrights', $DB->GetAllByKey('SELECT doctype, rights
            FROM docrights WHERE userid = ? AND rights > 1', 'doctype', array(Auth::GetCurrentUser())));
    }
}
$taxeslist            = $LMS->GetTaxes();
$allnodegroups        = $LMS->GetNodeGroupNames();
if (!isset($resource_tabs['customermessages']) || $resource_tabs['customermessages']) {
    $messagelist = $LMS->GetMessages($customerid);
}
if (!isset($resource_tabs['customerevents']) || $resource_tabs['customerevents']) {
    $params = array(
        'customerid' => $customerid,
    );
    if (isset($_GET['events-from-date'])) {
        $params['datefrom'] = date_to_timestamp($_GET['events-from-date']);
        $SMARTY->assign('events_from_date', $_GET['events-from-date']);
    }
    $allevents = isset($_GET['allevents']) && !empty($_GET['allevents']);
    if ($allevents) {
        $params['closed'] = '';
    }
    $eventlist = $LMS->EventSearch($params, 'date,desc', true);
}
if (!isset($resource_tabs['customernodesbox']) || $resource_tabs['customernodesbox']) {
    $customernodes = $LMS->GetCustomerNodes($customerid);
}
if (!isset($resource_tabs['customernetnodes']) || $resource_tabs['customernetnodes']) {
    $customernetnodes = $LMS->GetCustomerNetNodes($customerid);
}

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

if (!isset($resource_tabs['customernetworksbox']) || $resource_tabs['customernetworksbox']) {
    $customernetworks = $LMS->GetCustomerNetworks($customerid, 10);
}

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

if (!isset($resource_tabs['customerdevices']) || $resource_tabs['customerdevices']) {
    $customerdevices = $LMS->GetNetDevList('name,asc', array('ownerid' => intval($customerid)));
    unset($customerdevices['total']);
    unset($customerdevices['order']);
    unset($customerdevices['direction']);

    $counter = count($customerdevices);
    for ($i = 0; $i < $counter; ++$i) {
        $customerdevices[$i]['ips'] = $LMS->GetNetDevIPs($customerdevices[$i]['id']);
    }
}

if (!isset($resource_tabs['transactions']) || $resource_tabs['transactions']) {
    if ($SYSLOG && ConfigHelper::checkPrivilege('transaction_logs')) {
        $trans = $SYSLOG->GetTransactions(
            array(
                'key' => SYSLOG::getResourceKey(SYSLOG::RES_CUST),
                'value' => $customerid,
                'limit' => 300,
                'details' => true,
            )
        );
/*
        if (!empty($trans)) {
            foreach ($trans as $idx => $tran) {
                $SYSLOG->DecodeTransaction($trans[$idx]);
            }
        }
*/
        $SMARTY->assign('transactions', $trans);
        $SMARTY->assign('resourcetype', SYSLOG::RES_CUST);
        $SMARTY->assign('resourceid', $customerid);
    }
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
if ($receipt = $SESSION->get('receiptprint', true)) {
    $SMARTY->assign('receipt', $receipt);
    $SESSION->remove('receiptprint', true);
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
$SMARTY->assignByRef('customernotes', $customernotes);
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
