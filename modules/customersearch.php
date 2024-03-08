<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

$SESSION->add_history_entry();

if (isset($_POST['search'])) {
    $search = $_POST['search'];

    if (!empty($search['tariffs'])) {
        $search['tariffs'] = implode(",", $search['tariffs']);
    }

    if ($search['balance_date']) {
        [$year, $month, $day] = explode('/', $search['balance_date']);
        $search['balance_date'] = mktime(23, 59, 59, $month, $day, $year);
    }
}

if (isset($search)) {
    $SESSION->save('customersearch', $search);
} else {
    $SESSION->restore('customersearch', $search);
}

if (!empty($search['balance_date'])) {
    $time = (int) $search['balance_date'];
}

if (isset($search['balance_days'])) {
    $days = strlen($search['balance_days']) !== 0 ? (int) $search['balance_days'] : -1;
}

if (!isset($_GET['o'])) {
    $SESSION->restore('cslo', $order);
} else {
    $order = $_GET['o'];
}
$SESSION->save('cslo', $order);

if (isset($_POST['s'])) {
    $state = $_POST['s'];
} else {
    $SESSION->restore('csls', $state);
}
$SESSION->save('csls', $state);

if (isset($_POST['sk'])) {
    $statesqlskey = $_POST['sk'];
} else {
    $SESSION->restore('cslsk', $statesqlskey);
}
$SESSION->save('cslsk', $statesqlskey);

if (isset($_POST['flags'])) {
    $flags = $_POST['flags'];
} else {
    $SESSION->restore('cslf', $flags);
}
$SESSION->save('cslf', $flags);

if (isset($_POST['hidessn'])) {
    $hidessn = (int) $_POST['hidessn'];
} else {
    $SESSION->restore('cshidessn', $hidessn);
    if (!isset($hidessn)) {
        $hidessn = 1;
    }
}
$SESSION->save('cshidessn', $hidessn);

if (isset($_POST['showassignments'])) {
    $showassignments = (int) $_POST['showassignments'];
} else {
    $SESSION->restore('csshowassignments', $showassignments);
    if (!isset($showassignments)) {
        $showassignments = 1;
    }
}
$SESSION->save('csshowassignments', $showassignments);

if (!isset($_POST['fk'])) {
    $flagsqlskey = $_POST['fk'];
} else {
    $SESSION->restore('cslfk', $flagsqlskey);
}
$SESSION->save('cslfk', $flagsqlskey);

if (isset($_POST['consents'])) {
    $consents = $_POST['consents'];
} else {
    $SESSION->restore('csconsents', $consents);
}
$SESSION->save('csconsents', $consents);

if (isset($_POST['karma'])) {
    $karma = $_POST['karma'];
} else {
    $SESSION->restore('cslkarma', $karma);
}
$SESSION->save('cslkarma', $karma);

if (!isset($_POST['n'])) {
    $SESSION->restore('csln', $network);
} elseif ($_POST['n'] == 'all') {
    $network = array();
} else {
    $network = Utils::filterIntegers($_POST['n']);
}
$SESSION->save('csln', $network);

if (!isset($_POST['g'])) {
    $SESSION->restore('cslg', $customergroup);
} elseif ($_POST['g'] == 'all') {
    $customergroup = array();
} elseif (count($_POST['g']) == 1 && (int) $_POST['g'][0] <= 0) {
    $customergroup = reset($_POST['g']);
} else {
    $customergroup = $_POST['g'];
}
$SESSION->save('cslg', $customergroup);

if (isset($_POST['cgk'])) {
    $customergroupsqlskey = $_POST['cgk'];
} else {
    $SESSION->restore('cslcgk', $customergroupsqlskey);
}
$SESSION->save('cslcgk', $customergroupsqlskey);

if (isset($_POST['cgnot'])) {
    $customergroupnegation = !empty($_POST['cgnot']);
} else {
    $SESSION->restore('cslcgnot', $customergroupnegation);
}
$SESSION->save('cslcgnot', $customergroupnegation);

if (isset($_POST['k'])) {
    $sqlskey = $_POST['k'];
} else {
    $SESSION->restore('cslk', $sqlskey);
}
$SESSION->save('cslk', $sqlskey);

if (isset($_POST['ng'])) {
    $nodegroup = $_POST['ng'];
} else {
    $SESSION->restore('cslng', $nodegroup);
}
$SESSION->save('cslng', $nodegroup);

if (isset($_POST['ngnot'])) {
    $nodegroupnegation = !empty($_POST['ngnot']);
} else {
    $SESSION->restore('cslngnot', $nodegroupnegation);
}
$SESSION->save('cslngnot', $nodegroupnegation);

if (isset($_POST['d'])) {
    $division = $_POST['d'];
} else {
    $SESSION->restore('csld', $division);
}
$SESSION->save('csld', $division);

if (isset($_GET['search'])) {
    $layout['pagetitle'] = trans('Customer Search Results');
    if (!isset($time)) {
        $time = null;
    }
    if (!isset($days)) {
        $days = null;
    }
    $customerlist = $LMS->GetCustomerList(compact(
        "order",
        "state",
        "statesqlskey",
        "customergroupsqlskey",
        "customergroupnegation",
        "flags",
        "flagsqlskey",
        "consents",
        "karma",
        "network",
        "customergroup",
        "search",
        "time",
        "days",
        "sqlskey",
        "nodegroupnegation",
        "nodegroup",
        "division"
    ));

    $listdata = array(
        'total' => $customerlist['total'],
        'direction' => $customerlist['direction'],
        'order' => $customerlist['order'],
        'below' => $customerlist['below'],
        'over' => $customerlist['over'],
        'state' => $state,
        'flags' => $flags,
        'hidessn' => $hidessn,
        'showassignments' => $showassignments,
        'karma' => $karma,
        'network' => $network,
        'customergroup' => empty($customergroup) ? array() : $customergroup,
        'nodegroup' => $nodegroup,
        'division' => $division
    );

    unset(
        $customerlist['total'],
        $customerlist['state'],
        $customerlist['flags'],
        $customerlist['karma'],
        $customerlist['direction'],
        $customerlist['order'],
        $customerlist['below'],
        $customerlist['over']
    );

    if ($showassignments) {
        foreach ($customerlist as $idx => $c) {
            $ca = $LMS->GetCustomerAssignments($c['id'], false, false);
            if (isset($ca)) {
                $customerlist[$idx]['assignmentsnames'] = implode(",", array_column($ca, 'name'));
            }
        }
    }

    if (!isset($_GET['page'])) {
        $SESSION->restore('cslp', $_GET['page']);
    }

    $page = $_GET['page'] ?? 1;
    $pagelimit = ConfigHelper::getConfig('phpui.customerlist_pagelimit', $listdata['total']);
    $start = ($page - 1) * $pagelimit;

    $SESSION->save('cslp', $page);

    $SMARTY->assign(
        array(
            'customerlist' => $customerlist,
            'listdata' => $listdata,
            'pagelimit' => $pagelimit,
            'page' => $page,
            'start' => $start
        )
    );

    if (isset($_GET['print'])) {
        $SMARTY->display('print/printcustomerlist.html');
    } elseif (isset($_GET['export'])) {
        $SMARTY->assign('contactlist', $DB->GetAllByKey(
            'SELECT customerid, (' . $DB->GroupConcat('contact') . ') AS phone
                FROM customercontacts WHERE contact <> \'\' AND type & ? > 0 GROUP BY customerid',
            'customerid',
            array(CONTACT_MOBILE | CONTACT_LANDLINE)
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

    $listdata = array(
        'state' => $state,
        'flags' => $flags,
        'hidessn' => $hidessn,
        'showassignments' => $showassignments,
        'karma' => $karma,
        'network' => $network,
        'customergroup' => empty($customergroup) ? array() : $customergroup,
        'nodegroup' => $nodegroup,
        'division' => $division
    );

    $SESSION->remove('cslp');

    $SMARTY->assign(
        array(
            'listdata' => $listdata,
            'networks' => $LMS->GetNetworks(),
            'customergroups' => $LMS->CustomergroupGetAll(),
            'nodegroups' => $LMS->GetNodeGroupNames(),
            'cstateslist' => $LMS->GetCountryStates(),
            'tariffs' => $LMS->GetTariffs(),
            'divisions' => $LMS->GetDivisions(),
            'k' => $sqlskey,
            'sk' => $statesqlskey,
            'cgk' => $customergroupsqlskey,
            'cgnot' => $customergroupnegation,
            'fk' => $flagsqlskey,
            'ngnot' => $nodegroupnegation,
            'karma' => $karma
        )
    );
    
    $SMARTY->display('customer/customersearch.html');
}
