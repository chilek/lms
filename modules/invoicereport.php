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

// get taxes for calculations and for building report table
$taxes = $DB->GetAllByKey('SELECT taxid AS id, label, taxes.value AS value
	    FROM documents 
	    LEFT JOIN invoicecontents ON (documents.id = docid)
	    LEFT JOIN taxes ON (taxid = taxes.id)
	    WHERE type = ? AND (cdate BETWEEN ? AND ?) 
	    GROUP BY taxid, label, taxes.value 
	    ORDER BY value ASC', 'id', array(DOC_INVOICE,$unixfrom, $unixto));

// we can't here simply get documents with SUM(value*count)
// because we need here incoices-like round-off

// get documents items numeric values for calculations
$items = $DB->GetAll('SELECT docid, taxid, value, count
	    FROM documents 
	    LEFT JOIN invoicecontents ON docid = documents.id 
	    WHERE type = ? AND (cdate BETWEEN ? AND ?) 
	    ORDER BY cdate ASC, docid', array(DOC_INVOICE, $unixfrom, $unixto));

// get documents data
$docs = $DB->GetAllByKey('SELECT documents.id AS id, number, cdate, customerid, name, address, zip, city, ten, ssn, template 
	    FROM documents 
	    LEFT JOIN numberplans ON numberplanid = numberplans.id
	    WHERE type = ? AND (cdate BETWEEN ? AND ?) 
	    GROUP BY documents.id, number, cdate, customerid, name, address, zip, city, ten, ssn, template 
	    ORDER BY cdate ASC', 'id', array(DOC_INVOICE, $unixfrom, $unixto));

if($items)
{
	foreach($items as $row)
	{
		$idx = $row['docid'];
		$doc = $docs[$idx];
		$taxid = $row['taxid'];
		
		$invoicelist[$idx]['custname'] = $doc['name'];
		$invoicelist[$idx]['custaddress'] = $doc['zip'].' '.$doc['city'].', '.$doc['address'];
		$invoicelist[$idx]['ten'] = ($doc['ten'] ? trans('TEN').' '.$doc['ten'] : ($doc['ssn'] ? trans('SSN').' '.$doc['ssn'] : ''));
		$invoicelist[$idx]['number'] = docnumber($doc['number'], $doc['template'], $doc['cdate']);
		$invoicelist[$idx]['cdate'] = $doc['cdate'];
		$invoicelist[$idx]['customerid'] = $doc['customerid'];

		$sum = $row['value'] * $row['count'];
		$val = round($row['value'] / ($taxes[$taxid]['value']+100) * 100, 2) * $row['count'];
		$tax = $sum - $val;
		
		$invoicelist[$idx][$taxid]['tax'] += $tax;
		$invoicelist[$idx][$taxid]['val'] += $val;
		$invoicelist[$idx]['tax'] += $tax;
		$invoicelist[$idx]['brutto'] += $sum;
		
		$listdata[$taxid]['tax'] += $tax;
		$listdata[$taxid]['val'] += $val;
		$listdata['tax'] += $tax;
		$listdata['brutto'] += $sum;
	}
}

$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('taxes', $taxes);
$SMARTY->assign('taxescount', sizeof($taxes));
$SMARTY->assign('layout', $layout);
$SMARTY->assign('invoicelist', $invoicelist);
$SMARTY->display('invoicereport.html');

?>
