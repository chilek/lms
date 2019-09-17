<?php

use PragmaRX\Google2FA\Google2FA;

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$LMS->UserExists($id)) {
    $SESSION->redirect('?m=userlist');
}

if (isset($_GET['removetrusteddevices'])) {
    $AUTH->removeTrustedDevices($id, isset($_GET['deviceid']) ? $_GET['deviceid'] : null);
    $SESSION->redirect($_SERVER['HTTP_REFERER']);
}

$userinfo = isset($_POST['userinfo']) ? $_POST['userinfo'] : false;

if ($userinfo) {
    $acl = $_POST['acl'];
    $userinfo['id'] = $id;

    foreach ($userinfo as $key => $value) {
        if (!is_array($value)) {
            $userinfo[$key] = trim($value);
        }
    }

    if ($userinfo['login'] == '') {
        $error['login'] = trans('Login can\'t be empty!');
    } elseif (!preg_match('/^[a-z0-9._-]+$/i', $userinfo['login'])) {
        $error['login'] = trans('Login contains forbidden characters!');
    } elseif ($LMS->GetUserIDByLogin($userinfo['login']) && $LMS->GetUserIDByLogin($userinfo['login']) != $id) {
        $error['login'] = trans('User with specified login exists or that login was used in the past!');
    }

    if ($userinfo['firstname'] == '') {
        $error['firstname'] = trans('You have to enter first and lastname!');
    }
    if ($userinfo['lastname'] == '') {
        $error['lastname'] = trans('You have to enter first and lastname!');
    }

    if ($userinfo['email']!='' && !check_email($userinfo['email'])) {
        $error['email'] = trans('E-mail isn\'t correct!');
    }

    if (!empty($userinfo['accessfrom'])) {
        $accessfrom=date_to_timestamp($userinfo['accessfrom']);
        if (empty($accessfrom)) {
            $error['accessfrom'] = trans('Incorrect charging time!');
        }
    } else {
        $accessfrom = 0;
    }

    if (!empty($userinfo['accessto'])) {
        $accessto=date_to_timestamp($userinfo['accessto']);
        if (empty($accessto)) {
            $error['accessto'] = trans('Incorrect charging time!');
        }
    } else {
        $accessto = 0;
    }

    if ($accessto < $accessfrom && $accessto != 0 && $accessfrom != 0) {
        $error['accessto'] = trans('Incorrect date range!');
    }

    $rights = isset($acl) ? array_keys($acl) : array();
    $userinfo['rights'] = implode(',', $rights);

    if (!empty($userinfo['ntype'])) {
        $userinfo['ntype'] = array_sum(Utils::filterIntegers($userinfo['ntype']));
    }

    if ($userinfo['twofactorauth'] == 1) {
        if (strlen($userinfo['twofactorauthsecretkey']) != 16) {
            $error['twofactorauthsecretkey'] = trans('Incorrect secret key format!');
        } else {
            $google2fa = new Google2FA();
            if ($google2fa->removeInvalidChars($userinfo['twofactorauthsecretkey']) != $userinfo['twofactorauthsecretkey']) {
                $error['twofactorauthsecretkey'] = trans('Secret key contains invalid characters!');
            }
        }
    }

    if (!$error) {
        if ($userinfo['twofactorauth'] == -1) {
            $userinfo['twofactorauth'] = 1;
            $google2fa = new Google2FA();
            $userinfo['twofactorauthsecretkey'] = $google2fa->generateSecretKey();
        }

        $userinfo['accessfrom'] = $accessfrom;
        $userinfo['accessto'] = $accessto;
        $LMS->UserUpdate($userinfo);

        if ($SYSLOG) {
            $groups = $DB->GetAll(
                'SELECT id, customergroupid FROM excludedgroups WHERE userid = ?',
                array($userinfo['id'])
            );
            if (!empty($groups)) {
                foreach ($groups as $group) {
                    $args = array(
                    SYSLOG::RES_EXCLGROUP => $group['id'],
                    SYSLOG::RES_CUSTGROUP => $group['customergroupid'],
                    SYSLOG::RES_USER => $userinfo['id']
                    );
                    $SYSLOG->AddMessage(SYSLOG::RES_EXCLGROUP, SYSLOG::OPER_DELETE, $args);
                }
            }
        }
        $DB->Execute('DELETE FROM excludedgroups WHERE userid = ?', array($userinfo['id']));
        if (isset($_POST['selected'])) {
            foreach ($_POST['selected'] as $idx => $name) {
                $DB->Execute('INSERT INTO excludedgroups (customergroupid, userid)
						VALUES(?, ?)', array($idx, $userinfo['id']));
                if ($SYSLOG) {
                    $args = array(
                        SYSLOG::RES_EXCLGROUP =>
                        $DB->GetLastInsertID('excludedgroups'),
                        SYSLOG::RES_CUSTGROUP => $idx,
                        SYSLOG::RES_USER => $userinfo['id']
                    );
                    $SYSLOG->AddMessage(SYSLOG::RES_EXCLGROUP, SYSLOG::OPER_ADD, $args);
                }
            }
        }

        $SESSION->redirect('?m=userinfo&id='.$userinfo['id']);
    } else {
        $userinfo['selected'] = array();
        if (isset($_POST['selected'])) {
            foreach ($_POST['selected'] as $idx => $name) {
                $userinfo['selected'][$idx]['id'] = $idx;
                $userinfo['selected'][$idx]['name'] = $name;
            }
        }

        $access = AccessRights::getInstance();
        $accesslist = $access->getArray(array_keys($acl));
    }
} else {
    $rights = $LMS->GetUserRights($id);

    $access = AccessRights::getInstance();
    $accesslist = $access->getArray($rights);
}

foreach ($LMS->GetUserInfo($id) as $key => $value) {
    if (!isset($userinfo[$key])) {
        $userinfo[$key] = $value;
    }
}

if (!isset($userinfo['selected'])) {
    $userinfo['selected'] = $DB->GetAllByKey('SELECT g.id, g.name
		FROM customergroups g, excludedgroups
	        WHERE customergroupid = g.id AND userid = ?
		ORDER BY name', 'id', array($userinfo['id']));
}

$layout['pagetitle'] = trans('User Edit: $a', $userinfo['login']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if ($SYSLOG && (ConfigHelper::checkConfig('privileges.superuser') || ConfigHelper::checkConfig('privileges.transaction_logs'))) {
    $trans = $SYSLOG->GetTransactions(array('userid' => $id));
    if (!empty($trans)) {
        foreach ($trans as $idx => $tran) {
            $SYSLOG->DecodeTransaction($trans[$idx]);
        }
    }
    $SMARTY->assign('transactions', $trans);
    $SMARTY->assign('userid', $id);
}

$SMARTY->assign('accesslist', $accesslist);
$SMARTY->assign('available', $DB->GetAllByKey('SELECT id, name FROM customergroups ORDER BY name', 'id'));
$SMARTY->assign('userinfo', $userinfo);
$SMARTY->assign('error', $error);

$SMARTY->display('user/useredit.html');
