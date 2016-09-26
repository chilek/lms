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

$customerid = intval($_GET['id']);

$LMS->InitXajax();

if (!isset($_POST['xjxfun'])) {
	include(MODULES_DIR.'/customer.inc.php');
	require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'customercontacttypes.php');

	//if($customerinfo['cutoffstop'] > mktime(0,0,0))
	//        $customerinfo['cutoffstopnum'] = floor(($customerinfo['cutoffstop'] - mktime(23,59,59))/86400);

	$SESSION->save('backto', $_SERVER['QUERY_STRING']);

	$layout['pagetitle'] = trans('Customer Info: $a',$customerinfo['customername']);
}

$hook_data = $LMS->executeHook(
	'customerinfo_before_display',
	array(
		'customerinfo' => $customerinfo,
		'smarty' => $SMARTY,
	)
);
$customerinfo = $hook_data['customerinfo'];

$SMARTY->assign('xajax', $LMS->RunXajax());
$SMARTY->assign('customerinfo_sortable_order', $SESSION->get_persistent_setting('customerinfo-sortable-order'));
$SMARTY->display('customer/customerinfo.html');

?>
