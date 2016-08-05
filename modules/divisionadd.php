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

if(!empty($_POST['division'])) 
{
	$division = $_POST['division'];
	
	foreach($division as $key => $value)
	        $division[$key] = trim($value);
			
	if($division['name']=='' && $division['description']=='' && $division['shortname']=='')
	{
		$SESSION->redirect('?m=divisionlist');
	}
	
	if($division['name'] == '')
		$error['name'] = trans('Division long name is required!');

	if($division['shortname'] == '')
		$error['shortname'] = trans('Division short name is required!');
	elseif($DB->GetOne('SELECT 1 FROM divisions WHERE shortname = ?', array($division['shortname'])))
		$error['shortname'] = trans('Division with specified name already exists!');

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
			SYSLOG::RES_COUNTRY => $division['countryid'],
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
		);
		$DB->Execute('INSERT INTO divisions (name, shortname, address, city, zip,
			countryid, ten, regon, account, inv_header, inv_footer, inv_author,
			inv_cplace, inv_paytime, inv_paytype, description) 
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));

		if ($SYSLOG) {
			$args[SYSLOG::RES_DIV] = $DB->GetLastInsertID('divisions');
			$SYSLOG->AddMessage(SYSLOG::RES_DIV, SYSLOG::OPER_ADD, $args);
		}

		if(!isset($division['reuse']))
		{
			$SESSION->redirect('?m=divisionlist');
		}
	}
}	

$default_zip = ConfigHelper::getConfig('phpui.default_zip');
$default_city = ConfigHelper::getConfig('phpui.default_city');
$default_address = ConfigHelper::getConfig('phpui.default_address');

if (!isset($division['zip']) && $default_zip) {
	$division['zip'] = $default_zip;
} if (!isset($division['city']) && $default_city) {
	$division['city'] = $default_city;
} if (!isset($division['address']) && $default_address) {
	$division['address'] = $default_address;
}

$layout['pagetitle'] = trans('New Division');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('division', $division);
$SMARTY->assign('countries', $LMS->GetCountries());
$SMARTY->assign('error', $error);
$SMARTY->display('division/divisionadd.html');

?>
