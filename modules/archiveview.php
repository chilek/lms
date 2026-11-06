<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2023 LMS Developers
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


if (isset($_GET['action'])) {
    $SYSLOG = SYSLOG::getInstance();

    $result = array();

    switch ($_GET['action']) {
        case 'get-property-names':
            if (isset($_GET['resource-type'])) {
                $result = $SYSLOG->GetResourcePropertyNames($_GET['resource-type']);
            }
            break;
        case 'get-property-values':
            if (isset($_GET['resource-type'], $_GET['property-name'])) {
                $values = $SYSLOG->GetResourcePropertyValues($_GET['resource-type'], $_GET['property-name']);
                if (!empty($values)) {
                    foreach ($values as $value) {
                        if (count($values) <= 19) {
                            $data = array('resource' => $_GET['resource-type'], 'name' => $_GET['property-name'], 'value' => $value);
                            $SYSLOG->DecodeMessageData($data);
                            $result[] = array(
                                'value' => $value,
                                'label' => strlen($data['value']) > 50 ? substr($data['value'], 0, 50) . '...' : $data['value'],
                            );
                        } else {
                            $result[] = $value;
                        }
                    }
                }
            }
            break;
    }

    header('Content-Type: application/json');
    die(json_encode($result));
}

$limit = ConfigHelper::getConfig('phpui.archiveview_limit', 100);
$SESSION->add_history_entry();

if (isset($_POST['search'])) {
    $s = $_POST['search'];
    if (isset($s['userid'])) {
        $SESSION->save('arvuser', intval($s['userid']));
    }
    if (isset($s['resourcetype'])) {
        $SESSION->save('arvrt', intval($s['resourcetype']));
    }
    if (isset($s['resourceid'])) {
        $SESSION->save('arvrid', intval($s['resourceid']));
    }
    $SESSION->remove('arvpn');
    $SESSION->remove('arvpv');
}

if (isset($_POST['datefrom'])) {
    $datefrom = strtotime($_POST['datefrom']);
    if (empty($datefrom)) {
        $datefrom = 0;
    }
} else {
    $SESSION->restore('arvdf', $datefrom);
}
$SESSION->save('arvdf', $datefrom);

if (isset($_POST['dateto'])) {
    $dateto = strtotime($_POST['dateto'] . ' + 1 day');
    if (empty($dateto)) {
        $dateto = 0;
    } else {
        $dateto--;
    }
} else {
    $SESSION->restore('arvdt', $dateto);
}
$SESSION->save('arvdt', $dateto);

if (isset($_POST['user'])) {
    $user = intval($_POST['user']);
} else {
    $SESSION->restore('arvuser', $user);
}
$SESSION->save('arvuser', $user);

if (isset($_POST['module'])) {
    $module = $_POST['module'];
} else {
    $SESSION->restore('arvmodule', $module);
}
$SESSION->save('arvmodule', $module);

if (isset($_POST['resourcetype'])) {
    $resourcetype = intval($_POST['resourcetype']);
} else {
    $SESSION->restore('arvrt', $resourcetype);
}
$SESSION->save('arvrt', $resourcetype);

if (isset($_POST['resourceid'])) {
    $resourceid = intval($_POST['resourceid']);
} else {
    $SESSION->restore('arvrid', $resourceid);
}
$SESSION->save('arvrid', $resourceid);

if (isset($_POST['propertyname'])) {
    $propertyname = $_POST['propertyname'];
} else {
    $SESSION->restore('arvpn', $propertyname);
}
$SESSION->save('arvpn', $propertyname);

if (isset($_POST['propertyvalue'])) {
    $propertyvalue = $_POST['propertyvalue'];
} else {
    $SESSION->restore('arvpv', $propertyvalue);
}
$SESSION->save('arvpv', $propertyvalue);

$page = isset($_GET['page']) ? intval($_GET['page']) : 0;

$listdata['page'] = $page;
$listdata['user'] = $user;
$listdata['users'] = $DB->GetAllByKey('SELECT id, login FROM users ORDER BY login', 'id');
$listdata['module'] = $module;
$listdata['modules'] = $DB->GetCol('SELECT DISTINCT module FROM logtransactions ORDER BY module');
$listdata['resourcetype'] = $resourcetype;
$listdata['resourceid'] = $resourceid;
$listdata['propertyname'] = $propertyname;
$listdata['propertyvalue'] = $propertyvalue;
$listdata['datefrom'] = $datefrom;
$listdata['dateto'] = $dateto;

if ($SYSLOG) {
    $args = array('limit' => $limit + 1);
    if (!empty($user)) {
        $args['userid'] = $user;
    }
    if (!empty($module)) {
        $args['module'] = $module;
    }
    if (!empty($resourcetype)) {
        $args['key'] = SYSLOG::getResourceKey($resourcetype);
        $args['value'] = $resourceid;
    }
    if (!empty($datefrom)) {
        $args['datefrom'] = $datefrom;
    }
    if (!empty($dateto)) {
        $args['dateto'] = $dateto;
    }
    $args['offset'] = $page * $limit;
    if (!empty($propertyname)) {
        $args['propname'] = $propertyname;
        if (strlen($propertyvalue)) {
            $args['propvalue'] = $propertyvalue;
        }
    }
    $trans = $SYSLOG->GetTransactions($args);
    if (!empty($trans)) {
        if (count($trans) > $limit) {
            $listdata['prev'] = true;
            unset($trans[100]);
        }
        foreach ($trans as $idx => $tran) {
            $SYSLOG->DecodeTransaction($trans[$idx]);
        }
    }
    $layout['pagetitle'] = trans('Transaction Log View ($a transactions)', empty($trans) ? 0 : count($trans));
    $SMARTY->assign('transactions', $trans);
}

$SMARTY->assign('listdata', $listdata);
//$SMARTY->assign('pagelimit',$pagelimit);
//$SMARTY->assign('start', ($page - 1) * $pagelimit);
//$SMARTY->assign('page', $page);
$SMARTY->assign('transactions', empty($trans) ? null : $trans);
$SMARTY->display('archive/archiveview.html');
