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

$taxrate = $DB->GetRow('SELECT * FROM taxes WHERE id=?', array($_GET['id']));

if(!$taxrate)
{
	$SESSION->redirect('?m=taxratelist');
}

$label = $taxrate['label'];

if(!$taxrate['validfrom'])
	$taxrate['validfrom'] = '';
else
	$taxrate['validfrom'] = date('Y/m/d', $taxrate['validfrom']);
if(!$taxrate['validto'])
	$taxrate['validto'] = '';
else
	$taxrate['validto'] = date('Y/m/d', $taxrate['validto']);

$taxrateedit = isset($_POST['taxrateedit']) ? $_POST['taxrateedit'] : NULL;

if(sizeof($taxrateedit)) 
{
	foreach($taxrateedit as $idx => $key)
		$taxrateedit[$idx] = trim($key);

	$taxrateedit['id'] = $taxrate['id'];

	if($taxrateedit['label'] == '')
		$error['label'] = trans('Tax rate label is required!');
	elseif(strlen($taxrateedit['label'])>16)
		$error['label'] = trans('Label is too long (max.16)!');

	$taxrateedit['value'] = str_replace(',','.', $taxrateedit['value']);
	if(!is_numeric($taxrateedit['value']))
		$error['value'] = trans('Tax rate value is not numeric!');
	elseif($taxrateedit['value']<0 || $taxrateedit['value']>100)
		$error['value'] = trans('Incorrect tax rate percentage value (0-100)!');
	elseif($taxrateedit['value'] != $taxrate['value'] )
	{
		if( $DB->GetOne('SELECT COUNT(*) FROM cash WHERE taxid=?',array($taxrateedit['id'])) +
		    $DB->GetOne('SELECT COUNT(*) FROM invoicecontents WHERE taxid=?',array($taxrateedit['id'])) > 0 )
			$error['value'] = trans('Can\'t change value of tax rate which was used in the past!');
	}

	if(!$taxrateedit['taxed'])
		$taxrateedit['taxed'] = 0;
		
	if(!$taxrateedit['taxed'] && $taxrateedit['value']!=0)
		$error['value'] = trans('Incorrect tax rate percentage value (non-zero value and taxing not checked)!');

	if($taxrateedit['validfrom'] == '')
		$validfrom = 0;
	else
	{
		list($fyear, $fmonth, $fday) = explode('/',$taxrateedit['validfrom']);
		if(!checkdate($fmonth, $fday, $fyear))
			$error['validfrom'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
		else
			$validfrom = mktime(0, 0, 0, $fmonth, $fday, $fyear);
	}

	if($taxrateedit['validto'] == '')
		$validto = 0;
	else
	{
		list($tyear, $tmonth, $tday) = explode('/',$taxrateedit['validto']);
		if(!checkdate($tmonth, $tday, $tyear))
			$error['validto'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
		else
			$validto = mktime(23, 59, 59, $tmonth, $tday, $tyear);
	}

	if(!$error)
	{
		$DB->Execute('UPDATE taxes SET label=?, value=?, taxed=?,validfrom=?,validto=? WHERE id=?',
			    array(
				    $taxrateedit['label'], 
				    $taxrateedit['value'],
				    $taxrateedit['taxed'],
				    $validfrom,
				    $validto,
				    $taxrateedit['id']
				    ));
		
		$SESSION->redirect('?m=taxratelist');
	}
	else
		$taxrate = $taxrateedit;
}	

$layout['pagetitle'] = trans('Tax Rate Edit: $a', $label);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('taxrateedit', $taxrate);
$SMARTY->assign('error', $error);
$SMARTY->display('taxrateedit.html');

?>
<?php
