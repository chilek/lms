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

$id = intval($_GET['id']);

if (!empty($_GET['changestatus'])) {
	if ($SYSLOG) {
		$div = $DB->GetRow('SELECT countryid, status FROM divisions WHERE id = ?', array($id));
		$args = array(
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DIV] => $id,
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_COUNTRY] => $div['countryid'],
			'status' => intval($div['status']) ? 0 : 1
		);
		$SYSLOG->AddMessage(SYSLOG_RES_DIV, SYSLOG_OPER_UPDATE, $args,
			array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DIV], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_COUNTRY]));
	}
	$DB->Execute('UPDATE divisions SET status = (CASE WHEN status > 0 THEN 0 ELSE 1 END)
		WHERE id = ?', array($id));
	$SESSION->redirect('?m=divisionlist');
}

$olddiv = $DB->GetRow('SELECT * FROM divisions WHERE id = ?', array($_GET['id']));

if(!empty($_POST['division'])) 
{
	$division = $_POST['division'];
		
	foreach($division as $key => $value)
	        $division[$key] = trim($value);
			
	if($division['name']=='' && $division['description']=='' && $division['shortname']=='')
	{
		$SESSION->redirect('?m=divisionlist');
	}
	
	$division['id'] = $olddiv['id'];
	
	if($division['name'] == '')
		$error['name'] = trans('Division long name is required!');

	if($division['shortname'] == '')
		$error['shortname'] = trans('Division short name is required!');
	elseif($olddiv['shortname'] != $division['shortname']
		&& $DB->GetOne('SELECT 1 FROM divisions WHERE shortname = ?', array($division['shortname'])))
	{
		$error['shortname'] = trans('Division with specified name already exists!');
	}
	
	if($division['address'] == '')
		$error['address'] = trans('Address is required!');

	if($division['city'] == '')
		$error['city'] = trans('City is required!');
	
	if($division['zip'] == '')
		$error['zip'] = trans('Zip code is required!');
	elseif(!check_zip($division['zip']))
		$error['zip'] = trans('Incorrect ZIP code!');

        if($division['ten'] != '' && !check_ten($division['ten']) && !isset($division['tenwarning']))
        {
                $error['ten'] = trans('Incorrect Tax Exempt Number! If you are sure you want to accept it, then click "Submit" again.');
                $division['tenwarning'] = 1;
        }

        if($division['regon'] != '' && !check_regon($division['regon']))
                $error['regon'] = trans('Incorrect Business Registration Number!');

	if($division['account'] != '' && (strlen($division['account'])>48 || !preg_match('/^([A-Z][A-Z])?[0-9]+$/', $division['account'])))
		$error['account'] = trans('Wrong account number!');

	if($division['inv_paytime'] == '')
                $division['inv_paytime'] = NULL;

	if (!$error) {
		$args = array(
			'name' => $division['name'],
			'shortname' => $division['shortname'],
			'address' => $division['address'],
			'city' => $division['city'],
			'zip' => $division['zip'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_COUNTRY] => $division['countryid'],
			'ten' => $division['ten'],
			'regon' => $division['regon'],
			'account' => $division['account'],
			'inv_header' => $division['inv_header'],
			'inv_footer' => $division['inv_footer'],
			'inv_author' => $division['inv_author'],
			'inv_cplace' => $division['inv_cplace'],
			'inv_paytime' => $division['inv_paytime'],
			'inv_paytype' => $division['inv_paytype'] ? $division['inv_paytype'] : null,
			'description' => $division['description'],
			'status' => !empty($division['status']) ? 1 : 0,
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DIV] => $division['id']
		);
		$DB->Execute('UPDATE divisions SET name=?, shortname=?, address=?, 
			city=?, zip=?, countryid=?, ten=?, regon=?, account=?, inv_header=?, 
			inv_footer=?, inv_author=?, inv_cplace=?, inv_paytime=?,
			inv_paytype=?, description=?, status=? 
			WHERE id=?', array_values($args));

		if ($SYSLOG)
			$SYSLOG->AddMessage(SYSLOG_RES_DIV, SYSLOG_OPER_UPDATE, $args,
				array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_COUNTRY],
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DIV]));

		$SESSION->redirect('?m=divisionlist');
	}
}

$layout['pagetitle'] = trans('Edit Division: $a', $olddiv['shortname']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('division', !empty($division) ? $division : $olddiv);
$SMARTY->assign('countries', $LMS->GetCountries());
$SMARTY->assign('error', $error);
$SMARTY->display('divisionedit.html');

?>
