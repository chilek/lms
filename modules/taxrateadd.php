<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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

$taxrateadd = isset($_POST['taxrateadd']) ? $_POST['taxrateadd'] : NULL;

if(sizeof($taxrateadd)) 
{
	foreach($taxrateadd as $idx => $key)
		$taxrateadd[$idx] = trim($key);

	if($taxrateadd['label']=='' && $taxrateadd['value']=='')
	{
		$SESSION->redirect('?m=taxratelist');
	}
	
	if($taxrateadd['label'] == '')
		$error['label'] = trans('Tax rate label is required!');
	elseif(strlen($taxrateadd['label'])>16)
		$error['label'] = trans('Label is too long (max.16)!');

	$taxrateadd['value'] = str_replace(',','.', $taxrateadd['value']);
	if(!is_numeric($taxrateadd['value']))
		$error['value'] = trans('Tax rate value is not numeric!');
	elseif($taxrateadd['value']<0 || $taxrateadd['value']>100)
		$error['value'] = trans('Incorrect tax rate percentage value (0-100)!');

	if(!$taxrateadd['taxed'])
		$taxrateadd['taxed'] = 0;
		
	if(!$taxrateadd['taxed'] && $taxrateadd['value']!=0)
		$error['value'] = trans('Incorrect tax rate percentage value (non-zero value and taxing not checked)!');

	if($taxrateadd['validfrom'] == '')
		$validfrom = 0;
	else
	{
		list($fyear, $fmonth, $fday) = explode('/',$taxrateadd['validfrom']);
		if(!checkdate($fmonth, $fday, $fyear))
			$error['validfrom'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
		else
			$validfrom = mktime(0, 0, 0, $fmonth, $fday, $fyear);
	}

	if($taxrateadd['validto'] == '')
		$validto = 0;
	else
	{
		list($tyear, $tmonth, $tday) = explode('/',$taxrateadd['validto']);
		if(!checkdate($tmonth, $tday, $tyear))
			$error['validto'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
		else
			$validto = mktime(23, 59, 59, $tmonth, $tday, $tyear);
	}

	
	if(!$error)
	{

		$DB->Execute('INSERT INTO taxes (label, value, taxed, validfrom, validto) 
			    VALUES (?,?,?,?,?)',array(
				    $taxrateadd['label'], 
				    $taxrateadd['value'],
				    $taxrateadd['taxed'],
				    $validfrom,
				    $validto,
				    ));
		
		if(!isset($taxrateadd['reuse']))
		{
			$SESSION->redirect('?m=taxratelist');
		}
		unset($taxrateadd['label']);
		unset($taxrateadd['value']);
		unset($taxrateadd['validfrom']);
		unset($taxrateadd['validto']);
	}
}	

$layout['pagetitle'] = trans('New Tax Rate');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('taxrateadd', $taxrateadd);
$SMARTY->assign('error', $error);
$SMARTY->display('taxrateadd.html');

?>
