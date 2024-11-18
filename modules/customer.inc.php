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

if (isset($_GET['oper']) && $_GET['oper'] == 'loadtransactionlist') {
    header('Content-Type: text/html');

    if ($SYSLOG && ConfigHelper::checkPrivilege('transaction_logs')) {
        $trans = $SYSLOG->GetTransactions(
            array(
                'key' => SYSLOG::getResourceKey(SYSLOG::RES_CUST),
                'value' => $customerid,
                'limit' => 300,
                'details' => true,
            )
        );
        $SMARTY->assign('transactions', $trans);
        die($SMARTY->fetch('transactionlist.html'));
    }

    die();
}

if ($layout['module'] != 'customeredit') {
    $customerinfo = $LMS->GetCustomer($customerid);

    if (!$customerinfo) {
        $SESSION->redirect('?m=customerlist');
    }

    $SMARTY->assignByRef('customerinfo', $customerinfo);
}

if (!isset($resource_tabs['customerextids']) || $resource_tabs['customerextids']) {
    $customerextids = $LMS->getCustomerExternalIDs($customerid, null, true);
}

if (!isset($resource_tabs['customernotes']) || $resource_tabs['customernotes']) {
    $customernotes = $LMS->getCustomerNotes($customerid);
}

if (!isset($resource_tabs['customerassignments']) || $resource_tabs['customerassignments']) {
    $commited = ConfigHelper::checkConfig(
        'assignments.default_show_approved_only',
        ConfigHelper::checkConfig('phpui.default_show_approved_assignments_only', true)
    );
    $expired = ConfigHelper::checkConfig(
        'assignments.default_show_expired',
        ConfigHelper::checkConfig('phpui.default_show_expired_assignments')
    );
    $default_show_period = intval(ConfigHelper::getConfig(
        'assignments.default_show_period',
        ConfigHelper::getConfig('phpui.default_show_period_assignments', -1)
    ));
    if ($default_show_period != -1) {
        $period = $PERIODS[$default_show_period];
    } else {
        $period = null;
    }
    $assignments = $LMS->GetCustomerAssignments($customerid, true, false);
}
if (!isset($resource_tabs['customergroups']) || $resource_tabs['customergroups']) {
    $customergroups = $LMS->CustomergroupGetForCustomer($customerid);
    $othercustomergroups = $LMS->GetGroupNamesWithoutCustomer($customerid);
}
if ((ConfigHelper::checkPrivilege('read_only') || ConfigHelper::checkPrivilege('finances_view') || ConfigHelper::checkPrivilege('financial_operations') || ConfigHelper::checkPrivilege('finances_management'))
    && (!isset($resource_tabs['customerbalancebox']) || $resource_tabs['customerbalancebox'])) {
    if (isset($_GET['aggregate_documents'])) {
        $aggregate_documents = !empty($_GET['aggregate_documents']);
    } else {
        $aggregate_documents = ConfigHelper::checkConfig('phpui.aggregate_documents');
    }

    if (!ConfigHelper::checkConfig('phpui.big_networks')) {
        $SMARTY->assign('customers', $LMS->GetCustomerNames());
    }

    $balancelist = $LMS->GetCustomerBalanceList($customerid, null, 'ASC', $aggregate_documents);
}
if (!isset($resource_tabs['customervoipaccountsbox']) || $resource_tabs['customervoipaccountsbox']) {
    $customervoipaccounts = $LMS->GetCustomerVoipAccounts($customerid);
}

if ($SYSLOG && (ConfigHelper::checkConfig('privileges.superuser') || ConfigHelper::checkConfig('privileges.transaction_logs'))) {
    $SMARTY->assign('resourcetype', SYSLOG::RES_CUST);
    $SMARTY->assign('resourceid', $customerid);
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

    if (isset($_GET['clear-event-filter'])) {
        $events_from_date = null;
    } elseif (isset($_GET['events-from-date'])) {
        $events_from_date = $_GET['events-from-date'];
    } elseif ($SESSION->is_set('events_from_date')) {
        $SESSION->restore('events_from_date', $events_from_date);
    } else {
        $events_from_date = null;
    }
    if (isset($events_from_date)) {
        $params['datefrom'] = date_to_timestamp($events_from_date);
        $SESSION->save('events_from_date', $events_from_date);
    } else {
        $SESSION->remove('events_from_date');
    }
    $SMARTY->assign('events_from_date', $events_from_date);

    if (isset($_GET['allevents'])) {
        $allevents = !empty($_GET['allevents']);
    } elseif (isset($_GET['clear-event-filter']) || !$SESSION->is_set('allevents')) {
        $allevents = ConfigHelper::checkConfig(
            'timetable.default_show_closed_events',
            ConfigHelper::checkConfig('phpui.default_show_closed_events')
        );
    } else {
        $SESSION->restore('allevents', $allevents);
    }
    $SESSION->save('allevents', $allevents);

    if ($allevents) {
        $params['closed'] = '';
    }
    $eventlist = $LMS->EventSearch($params, 'date,desc', true);
}
if (!isset($resource_tabs['customertickets']) || $resource_tabs['customertickets']) {
    $aet = ConfigHelper::getConfig('rt.allow_modify_resolved_tickets_newer_than', 86400);
    $params = array(
        'cid' => $customerid,
        'short' => true,
    );

    if (isset($_GET['alltickets'])) {
        $alltickets = !empty($_GET['alltickets']);
    } else {
        $alltickets = ConfigHelper::checkConfig(
            'rt.default_show_closed_tickets',
            ConfigHelper::checkConfig('phpui.default_show_closed_tickets')
        );
    }
    if (empty($alltickets)) {
        $params['state'] = -1;
    }
    $ticketlist = $LMS->GetQueueContents($params);
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
$user_permission_checks = ConfigHelper::checkConfig('rt.additional_user_permission_checks', ConfigHelper::checkConfig('phpui.helpdesk_additional_user_permission_checks'));
$customerstats = array(
    'tickets' => $DB->GetRow('SELECT COUNT(*) AS "all", SUM(CASE WHEN state NOT IN ? THEN 1 ELSE 0 END) AS notresolved
		FROM rttickets t
		LEFT JOIN rtrights r ON r.queueid = t.queueid AND r.userid = ?
		WHERE (r.queueid IS NOT NULL' . ($user_permission_checks ? ' OR t.owner = ' . $userid . ' OR t.verifierid = ' . $userid : '') . ')'
        . (!ConfigHelper::checkConfig('privileges.superuser') ? ' AND t.deleted = 0': '')
        . ' AND customerid = ' . intval($customerid), array(array(RT_RESOLVED, RT_DEAD), $userid)),
    'domains' => $DB->GetOne('SELECT COUNT(*) FROM domains WHERE ownerid = ?', array($customerid)),
    'accounts' => $DB->GetOne('SELECT COUNT(*) FROM passwd WHERE ownerid = ?', array($customerid))
);

if ((ConfigHelper::checkPrivilege('read_only')
    || ConfigHelper::checkPrivilege('customer_call_view')
    || ConfigHelper::checkPrivilege('customer_call_management'))
    && (!isset($resource_tabs['customercallbox']) || $resource_tabs['customercallbox'])) {
    $customercalls = $LMS->getCustomerCalls(array(
        'customerid' => $customerid,
        'limit' => -1,
    ));
}

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
    'aggregate_documents' => $aggregate_documents ?? false,
    'commited' => $commited ?? true,
    'expired' => $expired ?? false,
    'period' => $period ?? null,
    'allevents' => $allevents ?? 0,
    'alltickets' => $alltickets ?? 0,
//    'time' => $SESSION->get('addbt'),
    'taxid' => $SESSION->get('addbtax'),
    'comment' => $SESSION->get('addbc'),
    'sourceid' => $SESSION->get('addsource'),
));

$SMARTY->assign('sourcelist', $DB->GetAll('SELECT id, name FROM cashsources WHERE deleted = 0 ORDER BY name'));
$SMARTY->assignByRef('customerextids', $customerextids);
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
$SMARTY->assignByRef('customercalls', $customercalls);
$SMARTY->assignByRef('ticketlist', $ticketlist);
$SMARTY->assignByRef('aet', $aet);
