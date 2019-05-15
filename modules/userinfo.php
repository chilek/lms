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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$userinfo = $LMS->GetUserInfo($id);

if (!$userinfo || $userinfo['deleted']) {
    $SESSION->redirect('?m=userlist');
}

$rights = $LMS->GetUserRights($id);
$access = AccessRights::getInstance();
$accesslist = $access->getArray($rights);

$ntype = array();
if ($userinfo['ntype'] & MSG_MAIL) {
    $ntype[] = trans('email');
}
if ($userinfo['ntype'] & MSG_SMS) {
    $ntype[] = trans('sms');
}
$userinfo['ntype'] = implode(', ', $ntype);

$layout['pagetitle'] = trans('User Info: $a', $userinfo['login']);

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

$SMARTY->assign('userinfo', $userinfo);
$SMARTY->assign('accesslist', $accesslist);
$SMARTY->assign('excludedgroups', $DB->GetAll('SELECT g.id, g.name FROM customergroups g, excludedgroups 
					    WHERE customergroupid = g.id AND userid = ?
					    ORDER BY name', array($userinfo['id'])));

$SMARTY->display('user/userinfo.html');
