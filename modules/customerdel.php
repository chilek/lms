<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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
$layout['pagetitle'] = trans($permanent ? 'Permanent Customer Remove: $a' : 'Customer Remove: $a', sprintf("%04d", $_GET['id']));
$SMARTY->assign('customerid', $_GET['id']);

if (!$LMS->CustomerExists($_GET['id']))
	$body = '<P>' . trans('Incorrect Customer ID.') . '</P>';
else {
	if ($_GET['is_sure'] != 1) {
		if ($permanent) {
			$body = '<P>' . trans('Do you want to permanently remove $a customer?', $LMS->GetCustomerName($_GET['id'])) . '</P>';
			$body .= '<P>' . trans('This operation will be irreversible!') . '</P>';
		} else {
			$body = '<P>' . trans('Do you want to remove $a customer?', $LMS->GetCustomerName($_GET['id'])) . '</P>';
			$body .= '<P>' . trans('All customer data and computers bound to this customer will be lost!') . '</P>';
		}
		$body .= '<P><A HREF="?m=customerdel&id=' . $_GET['id'] . ($permanent ? '&type=permanent' : '') . '&is_sure=1">' . trans('Yes, I do.') . '</A></P>';
	} else {
		header("Location: ?" . $SESSION->get('backto'));
		$body = '<P>' . trans($permanent ? 'Customer $a has been permanently removed.' : 'Customer $a has been removed.', $LMS->GetCustomerName($_GET['id'])) . '</P>';

		$LMS->executeHook(
			'customerdel_before_submit', array(
				'id' => $_GET['id'],
				'permanent' => $permanent,
			)
		);

		if ($permanent)
			$LMS->DeleteCustomerPermanent($_GET['id']);
		else
			$LMS->DeleteCustomer($_GET['id']);

		$LMS->executeHook(
			'customerdel_after_submit', array(
				'id' => $_GET['id'],
				'permanent' => $permanent,
			)
		);
	}
}

$SMARTY->assign('body',$body);
$SMARTY->display('dialog.html');

?>
