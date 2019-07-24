<?php

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

$permanent = ConfigHelper::checkPrivilege('permanent_customer_removal') && isset($_GET['type']) && $_GET['type'] == 'permanent';

if (!$LMS->CustomerExists($_GET['id'])) {
    $layout['pagetitle'] = trans($permanent ? 'Permanent Customer Remove: $a' : 'Customer Remove: $a', sprintf("%04d", $_GET['id']));
    $SMARTY->assign('customerid', $_GET['id']);
    $body = '<P>' . trans('Incorrect Customer ID.') . '</P>';
    $body .= '<A HREF="?' . $SESSION->get('backto') . '">' . trans('Back') . '</A></P>';
    $SMARTY->assign('body', $body);
    $SMARTY->display('dialog.html');
} else {
    $LMS->executeHook(
        'customerdel_before_submit',
        array(
            'id' => $_GET['id'],
            'permanent' => $permanent,
        )
    );

    if ($permanent) {
        $LMS->DeleteCustomerPermanent($_GET['id']);
    } else {
        $LMS->DeleteCustomer($_GET['id']);
    }

    $LMS->executeHook(
        'customerdel_after_submit',
        array(
            'id' => $_GET['id'],
            'permanent' => $permanent,
        )
    );

    $SESSION->redirect('?'.$SESSION->get('backto'));
}
