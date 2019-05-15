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

/*
 * types of account:
 *    shell = 1  (0000000000000001)
 *    mail = 2   (0000000000000010)
 *    www = 4    (0000000000000100)
 *    ftp = 8    (0000000000001000)
 *    sql = 16   (0000000000010000)
 *    cloud = 32 (0000000000100000)
 */

if (isset($_POST['account'])) {
    $account = $_POST['account'];
    $quota = $_POST['quota'];
    
    foreach ($account as $key => $value) {
        if (!is_array($value)) {
                $account[$key] = trim($value);
        }
    }

    if (!($account['login'] || $account['domainid'] || $account['passwd1'] || $account['passwd2'])) {
        $SESSION->redirect('?m=accountlist');
    }
    
    if (isset($account['type'])) {
        $account['type'] = array_sum($account['type']);
    } else {
        $error['type'] = true;
    }
    
    if ($account['login'] == '') {
                $error['login'] = trans('You have to specify login!');
    } elseif (!preg_match('/^[a-z0-9._-]+$/', $account['login'])) {
            $error['login'] = trans('Login contains forbidden characters!');
    } elseif (!$account['domainid']) {
                $error['domainid'] = trans('You have to select domain for account!');
    } elseif ($DB->GetOne(
        'SELECT id FROM passwd WHERE login = ? AND domainid = ?',
        array($account['login'], $account['domainid'])
    )) {
        $error['login'] = trans('Account with that login name exists!');
    } elseif ($account['domainid'] && ($account['type'] & 2)) {
    // if account is of type mail, check if we've got an alias with the same login@domain
        if ($DB->GetOne('SELECT 1 FROM aliases WHERE login=? AND domainid=?', array($account['login'], $account['domainid']))) {
            $error['login'] = trans('Alias with that login name already exists in that domain!');
        }
    }

    if ($account['mail_forward'] != '' && !check_email($account['mail_forward'])) {
            $error['mail_forward'] = trans('Incorrect email!');
    }

    if ($account['mail_bcc'] != '' && !check_email($account['mail_bcc'])) {
            $error['mail_bcc'] = trans('Incorrect email!');
    }
            
    if ($account['passwd1'] != $account['passwd2']) {
        $error['passwd'] = trans('Passwords does not match!');
    }
        
    if ($account['passwd1'] == '') {
        $error['passwd'] = trans('Empty passwords are not allowed!');
    }
    
    if ($account['expdate'] == '') {
            $account['expdate'] = 0;
    } else {
        $date = date_to_timestamp($account['expdate']);
        if (empty($date)) {
                $error['expdate'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
        } else {
            $account['expdate'] = $date;
        }
    }

    if ($account['domainid'] && $account['ownerid']) {
        if (!$DB->GetOne('SELECT 1 FROM domains WHERE id=? AND (ownerid IS NULL OR ownerid=?)', array($account['domainid'], $account['ownerid']))) {
            $error['domainid'] = trans('Selected domain has other owner!');
        }
    }

    foreach ($ACCOUNTTYPES as $idx => $type) {
        if (!preg_match('/^[0-9]+$/', $quota[$idx])) {
            $error['quota[' . $idx . ']'] = trans('Integer value expected!');
        }
    }

    // finally lets check limits
    if ($account['ownerid']) {
        $limits = $LMS->GetHostingLimits($account['ownerid']);

        foreach ($ACCOUNTTYPES as $idx => $type) {
            // quota limit
            if (!isset($error['quota[' . $idx . ']']) && $limits['quota'][$idx] !== null && ($account['type'] & $idx) == $idx) {
                if ($quota[$idx] > $limits['quota'][$idx]) {
                    $error['quota[' . $idx . ']'] = trans(
                        'Exceeded \'$a\' account quota limit of selected customer ($b)!',
                        $type['label'],
                        $limits['quota'][$idx]
                    );
                }
            }

            // count limit
            if ($limits['count'][$idx] !== null && ($account['type'] & $idx) == $idx) {
                if ($limits['count'][$idx] > 0) {
                    $cnt = $DB->GetOne('SELECT COUNT(*) FROM passwd WHERE ownerid = ?
						AND (type & ?) > 0', array($account['ownerid'], $idx));
                }

                if (!$error && ($limits['count'][$idx] == 0 || $limits['count'][$idx] <= $cnt)) {
                    $error['ownerid'] = trans(
                        'Exceeded \'$a\' accounts limit of selected customer ($b)!',
                        $type['label'],
                        $limits['count'][$idx]
                    );
                }
            }
        }
    }

    if (!$error) {
        $DB->BeginTrans();

        $args = array(
            'ownerid' => empty($account['ownerid']) ? null : $account['ownerid'],
            'login' => $account['login'],
            'password' => crypt($account['passwd1']),
            'home' => $account['home'],
            'expdate' => $account['expdate'],
            'domainid' => $account['domainid'],
            'type' => $account['type'],
            'realname' => $account['realname'],
            'mail_forward' => $account['mail_forward'],
            'mail_bcc' => $account['mail_bcc'],
            'description' => $account['description'],
        );
        foreach ($ACCOUNTTYPES as $typeidx => $type) {
            $args['quota_' . $type['alias']] = $quota[$typeidx];
        }
        $DB->Execute('INSERT INTO passwd (' . implode(',', array_keys($args)) . ')
				VALUES (' . implode(',', array_fill(0, count($args), '?')) . ')', array_values($args));
        $id = $DB->GetLastInsertId('passwd');

        $DB->Execute('UPDATE passwd SET uid = id + 2000 WHERE id = ?', array($id));
        
        $DB->CommitTrans();
        
        if (!isset($account['reuse'])) {
            $SESSION->redirect('?m=accountinfo&id='.$id);
        }
        
        unset($account['login']);
        unset($account['home']);
        unset($account['realname']);
        unset($account['passwd1']);
        unset($account['passwd2']);
        unset($account['mail_forward']);
        unset($account['description']);
    }
    
    $SMARTY->assign('error', $error);
} else {
    $quota = array();

    if (!empty($_GET['did'])) {
        $account['domainid'] = intval($_GET['did']);
    }

    if (!empty($_GET['cid'])) {
        $account['ownerid'] = intval($_GET['cid']);
        $limits = $LMS->GetHostingLimits($account['ownerid']);

        foreach ($ACCOUNTTYPES as $idx => $type) {
            $quota[$idx] = intval($limits['quota'][$idx]);
        }
    } else {
        foreach ($ACCOUNTTYPES as $idx => $type) {
            $quota[$idx] = intval(ConfigHelper::getConfig('phpui.quota_' . $type['alias'], 0));
        }
    }

    $account_type = ConfigHelper::getConfig('phpui.account_type');
    if (!empty($account_type)) {
        $account['type'] = intval($account_type);
    }
}

$layout['pagetitle'] = trans('New Account');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if (!isset($account['type'])) {
    $account['type'] = 32767;
}

$SMARTY->assign('quota', $quota);
$SMARTY->assign('account', $account);
$SMARTY->assign('customers', $LMS->GetCustomerNames());
$SMARTY->assign('domainlist', $DB->GetAll('SELECT id, name FROM domains ORDER BY name'));

$SMARTY->display('account/accountadd.html');
