<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

/*
 * \brief Pool range valid function.
 *
 * \param  $name  pool name
 * \param  $start pool start number
 * \param  $end   pool end number
 * \param  $id    pool id who want ignore
 * \return string error text message
 * \return 0      no error
 */
function valid_pool( $name, $pstart, $pend, $id = 0 ) {
    $error = array();
    $DB    = LMSDB::getInstance();
    $id    = (int) $id;
    $pool_list = $DB->GetAllByKey("SELECT id, name, poolstart, poolend FROM voip_pool_numbers;", 'name');

    if (empty($name)) {
        $error['name'] = trans('Name is required!');
    } else if (preg_match('/[^a-zA-Z0-9\s]+/', $name)) {
        $error['name'] = trans('Name contains forbidden characters!');
    } else if (isset($pool_list[$name]) && $pool_list[$name]['id'] != $id) {
        $error['name'] = trans('Name is already in use!');
    }

    if (empty($pstart)) {
        $error['poolstart'] = trans('Pool start is required!');
    } else if (strlen($pstart) > 20) {
        $error['poolstart'] = trans('Value is too long (max. $a characters)!', 20);
    } else if (preg_match('/[^0-9]/i', $pstart)) {
        $error['poolstart'] = trans('Incorrect format! Only values 0 to 9.');
    }

    if (empty($pend)) {
        $error['poolend'] = trans('Pool end is required!');
    } else if (strlen($pend) > 20) {
        $error['poolend'] = trans('Value is too long (max. $a characters)!', 20);
    } else if (preg_match('/[^0-9]/i', $pend)) {
        $error['poolend'] = trans('Incorrect format! Only values 0 to 9.');
    }

    $fval_pstart = floatval($pstart);
    $fval_pend   = floatval($pend);

    if (empty($error['poolstart']) && empty($error['poolend']) && $fval_pstart >= $fval_pend) {
        $error['poolstart'] = trans('Pool start must be lower that end!');
    }

    foreach ($pool_list as $v) {
        if ($id == $v['id'])
            continue;

        $v_pstart = floatval($v['poolstart']);
        $v_pend   = floatval($v['poolend']);

        if ($fval_pstart >= $v_pstart && $fval_pstart <= $v_pend)
            $error['poolstart'] = trans('Number coincides with pool `$a` !', $v['name']);

        if ($fval_pend >= $v_pstart && $fval_pend <= $v_pend)
            $error['poolend'] = trans('Number coincides with pool `$a` !', $v['name']);

        if ($fval_pstart < $v_pstart && $fval_pend > $v_pend)
            $error['poolstart'] = trans('Number range coincides with pool `$a` !', $v['name']);
    }

    return ($error) ? $error : 0;
}

if (empty($_GET['action'])) {
    $_GET['action'] = 'none';
}

switch($_GET['action']) {

    case 'add':
        $pool  = array_map('trim', $_POST);

        $error = valid_pool( $pool['name'], $pool['poolstart'], $pool['poolend'] );

        if ($error) {
            die( json_encode($error) );
        }

        $DB->BeginTrans();

        $status = ($pool['status'] == '1') ? 1 : 0;

        $query = $DB->Execute('INSERT INTO voip_pool_numbers (disabled, name, poolstart, poolend, description) VALUES (?,?,?,?,?)',
                               array($status, $pool['name'], $pool['poolstart'], $pool['poolend'], $pool['description']));

        if ($query == 1) {
            $DB->CommitTrans();
            die( json_encode( array('id' => $DB->GetLastInsertID("voip_pool_numbers")) ) );
        } else {
            $DB->RollbackTrans();
            die( json_encode( array('name' => trans("Operation failed!")) ) );
        }
        return 0;
    break;

    case 'edit':
        $id   = (empty($_POST['poolid'])) ? 0 : intval($_POST['poolid']);
        $pool = array_map('trim', $_POST);

        $error = valid_pool( $pool['name'], $pool['poolstart'], $pool['poolend'], $id );
        print_r($error);

        if ($error) {
            die( json_encode($error) );
        }

        $DB->BeginTrans();

        $status = ($pool['status'] == '1') ? 1 : 0;

        $query = $DB->Execute('INSERT INTO voip_pool_numbers (disabled, name, poolstart, poolend, description) VALUES (?,?,?,?,?)',
                               array($status, $pool['name'], $pool['poolstart'], $pool['poolend'], $pool['description']));

        if ($query == 1) {
            $DB->CommitTrans();
            die( json_encode( array('id' => $DB->GetLastInsertID("voip_pool_numbers")) ) );
        } else {
            $DB->RollbackTrans();
            die( json_encode( array('name' => trans("Operation failed!")) ) );
        }

        return 0;
    break;

    case 'remove':
        $id = (empty($_POST['poolid'])) ? 0 : intval($_POST['poolid']);

        $DB->BeginTrans();
        $query = $DB->Execute("DELETE FROM voip_pool_numbers WHERE id = ?", array($id));

        if ($query == 0) {
            die( json_encode( array( trans("Operation failed!")) ) );
            $DB->RollbackTrans();
        } else {
            $DB->CommitTrans();
        }

        return 0;
    break;

    case 'changestate':
        $id    = (empty($_POST['poolid'])) ? 0 : intval($_POST['poolid']);
        $state = (isset($_POST['state']) && intval($_POST['state']) == 1) ? 1 : 0;

        $DB->BeginTrans();
        $query = $DB->Execute("UPDATE voip_pool_numbers SET disabled=? WHERE id=?", array($state, $id));

        if ($query == 0) {
            echo json_encode( array( trans("Operation failed!")) );
            $DB->RollbackTrans();
        } else {
            $DB->CommitTrans();
        }

        return 0;
    break;
}

$pool_list = $DB->GetAll("SELECT id, disabled, name, poolstart, poolend, description,
                          (select count(*) from voip_numbers where phone between vpn.poolstart AND vpn.poolend) as used_phones
                          FROM voip_pool_numbers vpn;");

$layout['pagetitle'] = trans('Pool numbers');

if (!ConfigHelper::checkConfig('phpui.big_networks'))
    $SMARTY->assign('customers', $LMS->GetCustomerNames());

$SMARTY->assign('prefixlist', $LMS->GetPrefixList());
$SMARTY->assign('pool_list' , $pool_list);
$SMARTY->assign('hostlist'  , $LMS->DB->GetAll('SELECT id, name FROM hosts ORDER BY name'));
$SMARTY->display('voipaccount/voippoolnumberlist.html');

?>
