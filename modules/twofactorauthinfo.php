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

$userinfo = $LMS->GetUserInfo(Auth::GetCurrentUser());

if (!$userinfo || $userinfo['deleted']) {
    $SESSION->redirect('?m=userlist');
}

$layout['pagetitle'] = trans('Authentication Settings: $a', $userinfo['login']);

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
    $SMARTY->assign('qrcode_image', base64_encode($barcodeObj->getPngData()));
}

$SMARTY->assign('userinfo', $userinfo);

$SMARTY->display('twofactorauth/twofactorauthinfo.html');
