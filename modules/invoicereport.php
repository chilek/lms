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

if($result = $DB->GetAll('SELECT id, number, cdate, customerid, name, address, zip, city, ten, ssn, taxid, SUM(value*count) AS value FROM documents LEFT JOIN invoicecontents ON docid = documents.id WHERE type = 1 AND (cdate BETWEEN ? AND ?) GROUP BY documents.id, number, taxid, cdate, customerid, name, address, zip, city, ten, ssn ORDER BY cdate ASC', array($unixfrom, $unixto)))
{
	foreach($result as $idx => $row)
	{
		$id = $row['id'];
		$value = round($row['value'], 2);
		$invoicelist[$id]['custname'] = $row['name'];
		$invoicelist[$id]['custaddress'] = $row['zip'].' '.$row['city'].', '.$row['address'];
		$invoicelist[$id]['ten'] = ($row['ten'] ? trans('TEN').' '.$row['ten'] : ($row['ssn'] ? trans('SSN').' '.$row['ssn'] : ''));
		$invoicelist[$id]['number'] = $row['number'];
		$invoicelist[$id]['cdate'] = $row['cdate'];
		$invoicelist[$id]['customerid'] = $row['customerid'];
		$invoicelist[$id]['year'] = date('Y',$row['cdate']);
		$invoicelist[$id]['month'] = date('m',$row['cdate']);
		$invoicelist[$id]['brutto'] += $value;

		$listdata['brutto'] += $value;
		if ($row['taxvalue'] == '')
		{
			$invoicelist[$id]['valfree'] += $value;
			$listdata['valfree'] += $value;
		}
		else
			switch(round($row['taxvalue'],1))
			{
			    case '0.0':
				    $invoicelist[$id]['val0'] += $value;
				    $listdata['val0'] += $value;
			    break;
			    case '7.0':
				     $invoicelist[$id]['tax7'] += round($value - ($value/1.07), 2);
				     $invoicelist[$id]['val7'] += $value - $invoicelist[$id]['tax7'];
			    	     $invoicelist[$id]['tax']   += $invoicelist[$id]['tax7'];
				     $listdata['tax7'] += $invoicelist[$id]['tax7'];
				     $listdata['val7'] += $invoicelist[$id]['val7'];
				     $listdata['tax']  += $invoicelist[$id]['tax7'];
			    break;
			    case '22.0':
				     $invoicelist[$id]['tax22'] += round($value - ($value/1.22), 2);
				     $invoicelist[$id]['val22'] += $value - $invoicelist[$id]['tax22'];
			    	     $invoicelist[$id]['tax']   += $invoicelist[$id]['tax22'];
				     $listdata['tax22'] += $invoicelist[$id]['tax22'];
				     $listdata['val22'] += $invoicelist[$id]['val22'];
				     $listdata['tax']   += $invoicelist[$id]['tax22'];
			    break;
		    }
	}
	
}

$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('layout', $layout);
$SMARTY->assign('invoicelist', $invoicelist);
$SMARTY->display('invoicereport.html');

?>
