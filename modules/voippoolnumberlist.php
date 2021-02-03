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
 * \brief Function for validate pool type id.
 *
 * \param  int    $id number of pool type
 * \return string empty string if not found, string contains name if exists
 */
function getPoolTypeNameByNumber($number)
{
    global $VOIP_POOL_NUMBER_TYPES;
    $name = '';

    if (in_array($number, array_keys($VOIP_POOL_NUMBER_TYPES))) {
        $name = $VOIP_POOL_NUMBER_TYPES[ $number ];
    }

    return $name;
}

/*
 * \brief Pool range valid function.
 *
 * \param  $name  pool name
 * \param  $start pool start number
 * \param  $end   pool end number
 * \param  $id    pool id who want ignore
 * \return array  array with text messages
 * \return 0      no error
 */
function valid_pool($p, $id = 0)
{
    $error = array();
    $DB    = LMSDB::getInstance();
    $id    = (int) $id;
    $pool_list = $DB->GetAllByKey("SELECT id, name, poolstart, poolend FROM voip_pool_numbers;", 'name');

    $name   = (!empty($p['name']))      ? trim($p['name'])      : null;
    $pstart = (!empty($p['poolstart'])) ? trim($p['poolstart']) : null;
    $pend   = (!empty($p['poolend']))   ? trim($p['poolend'])   : null;
    $type   = (!empty($p['pooltype']))  ? trim($p['pooltype'])  : null;

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

    if (gmp_cmp($pstart, $pend) == 0 || gmp_cmp($pstart, $pend) == 1) {
        $error['poolstart'] = trans('Pool start must be lower that end!');
    }

    if (!getPoolTypeNameByNumber($type)) {
        $error['pooltype'] = trans("Incorrect pool type!");
    }

    foreach ($pool_list as $v) {
        if ($id == $v['id'] || !$id) {
            continue;
        }

        $check1 = gmp_cmp($pstart, $v['poolstart']);
        $check2 = gmp_cmp($pstart, $v['poolend']);

        if (($check1 == 1 || $check1 == 0) && ($check2 == -1 || $check2 == 0)) {
            $error['poolstart'] = trans('Number coincides with pool `$a` !', $v['name']);
        }

        $check1 = gmp_cmp($pend, $v['poolstart']);
        $check2 = gmp_cmp($pend, $v['poolend']);

        if (($check1 == 1 || $check1 == 0) && ($check2 == -1 || $check2 == 0)) {
            $error['poolend'] = trans('Number coincides with pool `$a` !', $v['name']);
        }

        if ($check1 == -1 && $check2 == 1) {
            $error['poolstart'] = trans('Number range coincides with pool `$a` !', $v['name']);
        }
    }

    return ($error) ? $error : 0;
}

/*
 * \brief Function return pool size by begining and end number.
 *
 * \param $begin start number
 * \param $end   end number
 * return string pool range
 */
function getPoolSize($begin, $end)
{
    // end - begin + 1
    $size = gmp_add(gmp_sub($end, $begin), 1);

    return gmp_strval($size);
}

if (empty($_GET['action'])) {
    $_GET['action'] = 'none';
}

switch ($_GET['action']) {
    case 'add':
        $p     = array_map('trim', $_POST);
        $error = valid_pool($p);

        if ($error) {
            die(json_encode($error));
        }

        $DB->BeginTrans();

        $status = ($p['status'] == '1') ? 1 : 0;

        $query = $DB->Execute(
            'INSERT INTO voip_pool_numbers (disabled, name, poolstart, poolend, description, type) VALUES (?,?,?,?,?,?)',
            array($status, $p['name'], $p['poolstart'], $p['poolend'], $p['description'], $p['pooltype'])
        );

        if ($query == 1) {
            $DB->CommitTrans();

            $size = getPoolSize($p['poolstart'], $p['poolend']);
            $phones_used = 0;

            $voip_phones = $DB->GetAll("SELECT phone FROM voip_numbers;");

            if (count($voip_phones)) {
                foreach ($voip_phones as $phone) {
                    $ph = $phone['phone'];

                    if (gmp_cmp($ph, $p['poolstart']) != -1 && gmp_cmp($ph, $p['poolend']) != 1) {
                        ++$phones_used;
                    }
                }
            }

            // return inserted pool data
            die(json_encode(array('id'            => $DB->GetLastInsertID("voip_pool_numbers"),
                                    'size'          => getPoolSize($p['poolstart'], $p['poolend']),
                                    'phones_used'   => $phones_used,
                                    'phones_unused' => gmp_strval(gmp_sub($size, $phones_used)),
                                    'type'          => getPoolTypeNameByNumber($p['pooltype']) )));
        } else {
            $DB->RollbackTrans();
            die(json_encode(array('name' => trans("Operation failed!"))));
        }

        return 0;
    break;

    case 'edit':
        $id = (empty($_POST['poolid'])) ? 0 : intval($_POST['poolid']);
        $p  = array_map('trim', $_POST);

        $error = valid_pool($p, $id);

        if ($error) {
            die(json_encode($error));
        }

        $DB->BeginTrans();

        $status = ($pool['status'] == '1') ? 1 : 0;

        $query = $DB->Execute(
            'UPDATE voip_pool_numbers SET
                               disabled = ?, name = ?, poolstart = ?, poolend = ?, description = ?, type = ?
                               WHERE id = ?',
            array($p['status'], $p['name'], $p['poolstart'], $p['poolend'], $p['description'], $p['pooltype'], $p['poolid'])
        );

        if ($query == 1) {
            $DB->CommitTrans();
            die(json_encode(array('id' => $p['poolid'], 'typeid' => $p['pooltype'])));
        } else {
            $DB->RollbackTrans();
            die(json_encode(array('name' => trans("Operation failed!"))));
        }

        return 0;
    break;

    case 'remove':
        $id = (empty($_POST['poolid'])) ? 0 : intval($_POST['poolid']);

        $DB->BeginTrans();
        $query = $DB->Execute("DELETE FROM voip_pool_numbers WHERE id = ?", array($id));

        if ($query == 0) {
            die(json_encode(array( trans("Operation failed!"))));
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
            echo json_encode(array( trans("Operation failed!") ));
            $DB->RollbackTrans();
        } else {
            $DB->CommitTrans();
        }

        return 0;
    break;
}

$pool_list = $DB->GetAll("SELECT id, disabled, name, poolstart, poolend, description, type as typeid,
                          (select count(*) from voip_numbers where phone between vpn.poolstart AND vpn.poolend) as used_phones
                          FROM voip_pool_numbers vpn;");

$voip_phones = $DB->GetAll("SELECT phone FROM voip_numbers;");

if ($pool_list) {
    global $VOIP_POOL_NUMBER_TYPES;

    $counter = count($pool_list);

    for ($i=0; $i<$counter; ++$i) {
        $pool_list[$i]['size']  = getPoolSize($pool_list[$i]['poolstart'], $pool_list[$i]['poolend']);
        $pool_list[$i]['phones_used'] = 0;

        foreach ($voip_phones as $phone) {
            $p = $phone['phone'];

            if (gmp_cmp($p, $pool_list[$i]['poolstart']) != -1 && gmp_cmp($p, $pool_list[$i]['poolend']) != 1) {
                ++$pool_list[$i]['phones_used'];
            }
        }

        $pool_list[$i]['phones_unused'] = gmp_strval(gmp_sub($pool_list[$i]['size'], $pool_list[$i]['phones_used']));

        if (!empty($VOIP_POOL_NUMBER_TYPES[$pool_list[$i]['typeid']])) {
            $pool_list[$i]['type'] = $VOIP_POOL_NUMBER_TYPES[$pool_list[$i]['typeid']];
        } else {
            $pool_list[$i]['type'] = trans('undefined');
        }
    }
}

$layout['pagetitle'] = trans('Pool numbers');

$SMARTY->assign('pooltypes', $VOIP_POOL_NUMBER_TYPES);
$SMARTY->assign('prefixlist', $LMS->GetPrefixList());
$SMARTY->assign('pool_list', $pool_list);
$SMARTY->assign('hostlist', $LMS->DB->GetAll('SELECT id, name FROM hosts ORDER BY name'));
$SMARTY->display('voipaccount/voippoolnumberlist.html');
