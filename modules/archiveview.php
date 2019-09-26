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

function GetPropertyNames($resource, $params)
{
    $SYSLOG = SYSLOG::getInstance();

    $result = new XajaxResponse();
    $names = $SYSLOG->GetResourcePropertyNames($resource);

    $script = "var option;var propname = xjx.$('propertyname');
		while (propname.length > 1)
			propname.remove(1);";
    if (!empty($names)) {
        foreach ($names as $name) {
            $script .= "option = document.createElement('option');
				option.text = '" . $name . "';
				option.value = '" . $name . "';
				propname.add(option, null);"
            . (!empty($params) && $params['propertyname'] == $name ? "option.selected = true;" : '');
        }
    }
    $script .= "propname.disabled = false;";
    $result->script($script);
    if (!empty($params) && isset($params['propertyvalue'])) {
        $result->script("GetPropertyValues('" . $params['propertyvalue'] . "');");
    }
    $result->assign('propertyvaluedata', 'innerHTML', '<input type="text" size="20" name="propertyvalue" id="propertyvalue">');

    return $result;
}

function GetPropertyValues($resource, $propname, $propvalue)
{
    $SYSLOG = SYSLOG::getInstance();

    $result = new XajaxResponse();
    $values = $SYSLOG->GetResourcePropertyValues($resource, $propname);
    if (empty($values) || count($values) > 19) {
        $result->assign('propertyvaluedata', 'innerHTML', '<input type="text" size="20" name="propertyvalue" id="propertyvalue"'
            . (strlen($propvalue) ? ' value="' . $propvalue . '"' : '') . '>');
    } else {
        $options = '<SELECT size="1" name="propertyvalue" id="propertyvalue">';
        $options .= '<OPTION value="">' . trans('- all -') . '</OPTION>';
        foreach ($values as $value) {
            $data = array('resource' => $resource, 'name' => $propname, 'value' => $value);
            $SYSLOG->DecodeMessageData($data);
            $options .= '<OPTION value="' . $value . '"' . (strlen($propvalue) && $propvalue == $value ? ' selected' : '') . '>'
                . (strlen($data['value']) > 50 ? substr($data['value'], 0, 50) . '...' : $data['value'])
                . '</OPTION>';
        }
        $options .= '</SELECT>';
        $result->assign('propertyvaluedata', 'innerHTML', $options);
    }
    return $result;
}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction(array('GetPropertyNames', 'GetPropertyValues'));
$SMARTY->assign('xajax', $LMS->RunXajax());

$limit = ConfigHelper::getConfig('phpui.archiveview_limit', 100);
$SESSION->save('backto', $_SERVER['QUERY_STRING']);

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
    $datefrom = date_to_timestamp($_POST['datefrom']);
    if (empty($datefrom)) {
        $datefrom = 0;
    }
} else {
    $SESSION->restore('arvdf', $datefrom);
}
$SESSION->save('arvdf', $datefrom);

if (isset($_POST['dateto'])) {
    $dateto = date_to_timestamp($_POST['dateto']);
    if (empty($dateto)) {
        $dateto = 0;
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

unset($invoicelist['page']);

$listdata['page'] = $page;
$listdata['user'] = $user;
$listdata['users'] = $DB->GetAllByKey('SELECT id, login FROM users ORDER BY login', 'id');
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
$SMARTY->assign('transactions', $trans);
$SMARTY->display('archive/archiveview.html');
