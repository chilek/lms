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
 
if (!empty($_GET['ajax'])) {
	$pool = array_map('trim', $_POST);
	$error = array();
	
	$pool_list = $DB->GetAllByKey("SELECT name, poolstart, poolend FROM voip_pool_numbers;", 'name');

	if (empty($pool['name'])) {
        $error['name'] = trans('Name is required!');
    } elseif (preg_match('/[^a-zA-Z0-9\s]+/', $pool['name'])) {
        $error['name'] = trans('Name contains forbidden characters!');
    } else if (isset($pool_list[$pool['name']])) {
        $error['name'] = trans('Name is already in use!');
    }
        
    if (empty($pool['poolstart'])) {
        $error['poolstart'] = trans('Pool start is required!');
    } else if (strlen($pool['poolstart']) > 20) {
        $error['poolstart'] = trans('Value is too long (max. $a characters)!', 20);
    } else if (preg_match('/[^0-9]/i', $pool['poolstart'])) {
        $error['poolstart'] = trans('Incorrect format! Only values 0 to 9.');
    }
    
    if (empty($pool['poolend'])) {
        $error['poolend'] = trans('Pool end is required!');
    } else if (strlen($pool['poolend']) > 20) {
        $error['poolend'] = trans('Value is too long (max. $a characters)!', 20);
    } else if (preg_match('/[^0-9]/i', $pool['poolend'])) {
        $error['poolend'] = trans('Incorrect format! Only values 0 to 9.');
    } 

	$p_start = floatval($pool['poolstart']);
    $p_end   = floatval($pool['poolend']);

	if (empty($error['poolstart']) && empty($error['poolend']) && $p_start >= $p_end) {
		$error['poolstart'] = trans('Pool start must be lower that end number!');
    }
    
    foreach ($pool_list as $v) {
    	if ($p_start >= floatval($v['poolstart']) && $p_start <= floatval($v['poolend']))
    		$error['poolstart'] = trans('Pool start is already in use!');
    	
    	if ($p_end >= floatval($v['poolstart']) && $p_end <= floatval($v['poolend']))
    		$error['poolend'] = trans('Pool end is already in use!');
    }
    
    if ($error) {
    	echo json_encode($error);
    	return 0;
    }
    
    $DB->BeginTrans();

    $status = ($pool['status'] == '1') ? 1 : 0;

    $DB->Execute('INSERT INTO voip_pool_numbers (disabled, name, poolstart, poolend, description)
                  VALUES (?,?,?,?,?)', array($status, $pool['name'], $pool['poolstart'], $pool['poolend'], $pool['description']));
    
    $DB->CommitTrans();
	return 0;
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
