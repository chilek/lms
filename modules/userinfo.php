<?php

use PragmaRX\Google2FA\Google2FA;
use Com\Tecnick\Barcode\Barcode;

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
$userinfo = $LMS->GetUserInfo($id);

if (!$userinfo || $userinfo['deleted']) {
    $SESSION->redirect('?m=userlist');
}

if (isset($_GET['oper']) && $_GET['oper'] == 'loadtransactionlist') {
    header('Content-Type: text/html');

    if ($SYSLOG && ConfigHelper::checkPrivilege('transaction_logs')) {
        $trans = $SYSLOG->GetTransactions(array(
            'userid' => $id,
            'limit' => 300,
            'details' => true,
        ));
        $SMARTY->assign('transactions', $trans);
        $SMARTY->assign('userid', $id);
        die($SMARTY->fetch('transactionlist.html'));
    }

    die();
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
$SESSION->save('backto', $_SERVER['QUERY_STRING'], true);

if (!empty($userinfo['twofactorauth'])) {
    $google2fa = new Google2FA();
    $url = $google2fa->getQRCodeUrl(
        $userinfo['name'],
        'http' . ($_SERVER['HTTPS'] == 'on' ? 's' : '') . '://' . $_SERVER['HTTP_HOST']
            . substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1),
        $userinfo['twofactorauthsecretkey']
    );

    $barcode = new Barcode();
    $barcodeObj = $barcode->getBarcodeObj('QRCODE', $url, 150, 150);
    $SMARTY->assign('qrcode_image', base64_encode($barcodeObj->getPngData(false)));
}

$customercalls = $LMS->getCustomerCalls(array(
    'userid' => $userinfo['id'],
    'limit' => -1,
));

$SMARTY->assign('userinfo', $userinfo);
$SMARTY->assign('customercalls', $customercalls);
$SMARTY->assign('accesslist', $accesslist);
$SMARTY->assign('excludedgroups', $DB->GetAll('SELECT g.id, g.name FROM customergroups g, excludedgroups 
					    WHERE customergroupid = g.id AND userid = ?
					    ORDER BY name', array($userinfo['id'])));
$SMARTY->assign('user_divisions', $LMS->GetDivisions(array('userid' => $userinfo['id'])));

$SMARTY->display('user/userinfo.html');
