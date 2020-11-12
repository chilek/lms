<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

if (isset($_POST['search'])) {
    $search = $_POST['search'];

    if (!empty($search['tariffs'])) {
        $search['tariffs'] = implode(",", $search['tariffs']);
    }

    if ($search['createdfrom']) {
        list($year, $month, $day) = explode('/', $search['createdfrom']);
        $search['createdfrom'] = mktime(0, 0, 0, $month, $day, $year);
    }
    if ($search['createdto']) {
        list($year, $month, $day) = explode('/', $search['createdto']);
        $search['createdto'] = mktime(23, 59, 59, $month, $day, $year);
    }
    if ($search['deletedfrom']) {
        list($year, $month, $day) = explode('/', $search['deletedfrom']);
        $search['deletedfrom'] = mktime(0, 0, 0, $month, $day, $year);
    }
    if ($search['deletedto']) {
        list($year, $month, $day) = explode('/', $search['deletedto']);
        $search['deletedto'] = mktime(23, 59, 59, $month, $day, $year);
    }
    if ($search['balance_date']) {
        list ($year, $month, $day) = explode('/', $search['balance_date']);
        $search['balance_date'] = mktime(23, 59, 59, $month, $day, $year);
    }
}

if (!isset($search)) {
    $SESSION->restore('customersearch', $search);
} else {
    $SESSION->save('customersearch', $search);
}

if (isset($search['balance_date']) && !empty($search['balance_date'])) {
    $time = intval($search['balance_date']);
}

if (!isset($_GET['o'])) {
    $SESSION->restore('cslo', $order);
} else {
    $order = $_GET['o'];
}
$SESSION->save('cslo', $order);

if (!isset($_POST['s'])) {
    $SESSION->restore('csls', $state);
} else {
    $state = $_POST['s'];
}
$SESSION->save('csls', $state);

if (!isset($_POST['sk'])) {
    $SESSION->restore('cslsk', $statesqlskey);
} else {
    $statesqlskey = $_POST['sk'];
}
$SESSION->save('cslsk', $statesqlskey);

if (!isset($_POST['flags'])) {
    $SESSION->restore('cslf', $flags);
} else {
    $flags = $_POST['flags'];
}
$SESSION->save('cslf', $flags);

if (!isset($_POST['fk'])) {
    $SESSION->restore('cslfk', $flagsqlskey);
} else {
    $flagsqlskey = $_POST['fk'];
}
$SESSION->save('cslfk', $flagsqlskey);

if (!isset($_POST['karma'])) {
    $SESSION->restore('cslkarma', $karma);
} else {
    $karma = $_POST['karma'];
}
$SESSION->save('cslkarma', $karma);

if (!isset($_POST['n'])) {
    $SESSION->restore('csln', $network);
} else if ($_POST['n'] == 'all') {
        $network = array();
} else {
    $network = Utils::filterIntegers($_POST['n']);
}
$SESSION->save('csln', $network);

if (!isset($_POST['g'])) {
    $SESSION->restore('cslg', $customergroup);
} else {
    if (count($_POST['g']) == 1 && intval($_POST['g'][0]) <= 0) {
        $customergroup = reset($_POST['g']);
    } else {
        $customergroup = $_POST['g'];
    }
}
$SESSION->save('cslg', $customergroup);

if (!isset($_POST['k'])) {
    $SESSION->restore('cslk', $sqlskey);
} else {
    $sqlskey = $_POST['k'];
}
$SESSION->save('cslk', $sqlskey);

if (!isset($_POST['ng'])) {
    $SESSION->restore('cslng', $nodegroup);
} else {
    $nodegroup = $_POST['ng'];
}
$SESSION->save('cslng', $nodegroup);

if (!isset($_POST['d'])) {
    $SESSION->restore('csld', $division);
} else {
    $division = $_POST['d'];
}
$SESSION->save('csld', $division);

if (isset($_GET['search'])) {
    $layout['pagetitle'] = trans('Customer Search Results');
    $customerlist = $LMS->GetCustomerList(compact(
        "order",
        "state",
        "statesqlskey",
        "flags",
        "flagsqlskey",
        "karma",
        "network",
        "customergroup",
        "search",
        "time",
        "sqlskey",
        "nodegroup",
        "division"
    ));

    $listdata['total'] = $customerlist['total'];
    $listdata['direction'] = $customerlist['direction'];
    $listdata['order'] = $customerlist['order'];
    $listdata['below'] = $customerlist['below'];
    $listdata['over'] = $customerlist['over'];
    $listdata['state'] = $state;
    $listdata['flags'] = $flags;
    $listdata['karma'] = $karma;
    $listdata['network'] = $network;
    $listdata['customergroup'] = empty($customergroup) ? array() : $customergroup;
    $listdata['nodegroup'] = $nodegroup;
    $listdata['division'] = $division;

    unset($customerlist['total']);
    unset($customerlist['state']);
    unset($customerlist['flags']);
    unset($customerlist['karma']);
    unset($customerlist['direction']);
    unset($customerlist['order']);
    unset($customerlist['below']);
    unset($customerlist['over']);

    if (! isset($_GET['page'])) {
        $SESSION->restore('cslp', $_GET['page']);
    }

    $page = (! $_GET['page'] ? 1 : $_GET['page']);
    $pagelimit = ConfigHelper::getConfig('phpui.customerlist_pagelimit', $listdata['total']);
    $start = ($page - 1) * $pagelimit;

    $SESSION->save('cslp', $page);

    $SMARTY->assign('customerlist', $customerlist);
    $SMARTY->assign('listdata', $listdata);
    $SMARTY->assign('pagelimit', $pagelimit);
    $SMARTY->assign('page', $page);
    $SMARTY->assign('start', $start);

    if (isset($_GET['print'])) {
        $SMARTY->display('print/printcustomerlist.html');
    } elseif (isset($_GET['export'])) {
        $SMARTY->assign('contactlist', $DB->GetAllByKey(
            'SELECT customerid, (' . $DB->GroupConcat('contact') . ') AS phone
			FROM customercontacts WHERE contact <> \'\' AND type & ? > 0 GROUP BY customerid',
            'customerid',
            array(CONTACT_MOBILE | CONTACT_LANDLINE | CONTACT_LINE)
        ));

        $filename = 'customers-' . date('YmdHis') . '.csv';
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: public');
        $SMARTY->display('print/printcustomerlist-csv.html');
    } elseif ($listdata['total'] == 1) {
        $SESSION->redirect('?m=customerinfo&id=' . $customerlist[0]['id']);
    } else {
        include(LIB_DIR . DIRECTORY_SEPARATOR . 'customercontacttypes.php');
        $SMARTY->assign('customergroups', $LMS->CustomergroupGetAll());
        $SMARTY->display('customer/customersearchresults.html');
    }
} else {
    $layout['pagetitle'] = trans('Customer Search');

    $SESSION->remove('cslp');

    $SMARTY->assign('networks', $LMS->GetNetworks());
    $SMARTY->assign('customergroups', $LMS->CustomergroupGetAll());
    $SMARTY->assign('nodegroups', $LMS->GetNodeGroupNames());
    $SMARTY->assign('cstateslist', $LMS->GetCountryStates());
    $SMARTY->assign('tariffs', $LMS->GetTariffs());
    $SMARTY->assign('divisions', $LMS->GetDivisions());
    $SMARTY->assign('k', $sqlskey);
    $SMARTY->assign('sk', $statesqlskey);
    $SMARTY->assign('fk', $flagsqlskey);
    $SMARTY->assign('karma', $karma);
    $SMARTY->display('customer/customersearch.html');
}
