<?php

/*
 * LMS version 1.7-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

$from = $_POST['from'];
$to = $_POST['to'];

// date format 'yyyy/mm/dd'	
if($from) {
	list($year, $month, $day) = split('/',$from);
	$unixfrom = mktime(0,0,0,$month,$day,$year);
} else { 
	$from = date('Y/m/d',time());
	$unixfrom = mktime(0,0,0); //pocz±tek dnia dzisiejszego
}
if($to) {
	list($year, $month, $day) = split('/',$to);
	$unixto = mktime(23,59,59,$month,$day,$year);
} else { 
	$to = date('Y/m/d',time());
	$unixto = mktime(23,59,59); //koniec dnia dzisiejszego
}

$layout['pagetitle'] = trans('Sale Registry for period $0 - $1', $from, $to);

$listdata = array();
$invoicelist = array();

$taxes = $DB->GetAllByKey('SELECT taxid AS id, label, taxes.value AS value
	    FROM documents 
	    LEFT JOIN invoicecontents ON (documents.id = docid)
	    LEFT JOIN taxes ON (taxid = taxes.id)
	    WHERE type = ? AND (cdate BETWEEN ? AND ?) 
	    GROUP BY taxid, label, taxes.value 
	    ORDER BY value ASC', 'id', array(DOC_INVOICE,$unixfrom, $unixto));

if($result = $DB->GetAll('SELECT id, number, cdate, customerid, name, address, zip, city, ten, ssn, taxid, SUM(value*count) AS value, template 
	    FROM documents 
	    LEFT JOIN numberplans ON numberplanid = numberplans.id
	    LEFT JOIN invoicecontents ON docid = documents.id 
	    WHERE type = ? AND (cdate BETWEEN ? AND ?) 
	    GROUP BY documents.id, number, taxid, cdate, customerid, name, address, zip, city, ten, ssn, template 
	    ORDER BY cdate ASC', array(DOC_INVOICE, $unixfrom, $unixto)))
{
	foreach($result as $idx => $row)
	{
		$id = $row['id'];
		$taxid = $row['taxid'];
		$value = round($row['value'], 2);
		
		$invoicelist[$id]['custname'] = $row['name'];
		$invoicelist[$id]['custaddress'] = $row['zip'].' '.$row['city'].', '.$row['address'];
		$invoicelist[$id]['ten'] = ($row['ten'] ? trans('TEN').' '.$row['ten'] : ($row['ssn'] ? trans('SSN').' '.$row['ssn'] : ''));
		$invoicelist[$id]['number'] = docnumber($row['number'], $row['template'], $row['cdate']);
		$invoicelist[$id]['cdate'] = $row['cdate'];
		$invoicelist[$id]['customerid'] = $row['customerid'];

		$invoicelist[$id][$taxid]['tax'] += round($value / ($taxes[$taxid]['value']+100) * 100, 2);
		$invoicelist[$id][$taxid]['val'] += $value - $invoicelist[$id][$taxid]['tax'];
		$invoicelist[$id]['tax'] += $invoicelist[$id][$taxid]['tax'];
		$invoicelist[$id]['brutto'] += $value;
		
		$listdata[$taxid]['tax'] += $invoicelist[$id][$taxid]['tax'];
		$listdata[$taxid]['val'] += $invoicelist[$id][$taxid]['val'];
		$listdata['tax'] += $invoicelist[$id][$taxid]['tax'];
		$listdata['brutto'] += $value;
	}
}

$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('taxes', $taxes);
$SMARTY->assign('taxescount', sizeof($taxes));
$SMARTY->assign('layout', $layout);
$SMARTY->assign('invoicelist', $invoicelist);
$SMARTY->display('invoicereport.html');

?>
