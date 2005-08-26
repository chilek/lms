<?php

/*
 * LMS version 1.6-cvs
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

$items = $DB->GetAll('SELECT invoiceid, taxvalue, value, count
			FROM invoices 
			LEFT JOIN invoicecontents ON invoiceid = id 
			WHERE finished = 1 AND (cdate BETWEEN ? AND ?) 
			ORDER BY cdate ASC, invoiceid', array($unixfrom, $unixto));

$invoices = $DB->GetAllByKey('SELECT id, number, cdate, customerid, name, address, zip, city, nip, pesel 
			    FROM invoices 
			    WHERE finished = 1 AND (cdate BETWEEN ? AND ?)',
			    'id', array($unixfrom, $unixto));

if($items)
{
	foreach($items as $item)
	{
		$id = $item['invoiceid'];
		$inv = $invoices[$id];

		$invoicelist[$id]['custname'] = $inv['name'];
		$invoicelist[$id]['custaddress'] = $inv['zip'].' '.$inv['city'].', '.$inv['address'];
		$invoicelist[$id]['nip'] = ($inv['nip'] ? trans('TEN').' '.$inv['nip'] : ($inv['pesel'] ? trans('SSN').' '.$inv['pesel'] : ''));
		$invoicelist[$id]['number'] = $inv['number'];
		$invoicelist[$id]['cdate'] = $inv['cdate'];
		$invoicelist[$id]['customerid'] = $inv['customerid'];
		$invoicelist[$id]['year'] = date('Y',$inv['cdate']);
		$invoicelist[$id]['month'] = date('m',$inv['cdate']);
		
		$sum = $item['value'] * $item['count'];
		
		$invoicelist[$id]['brutto'] += $sum;
		$listdata['brutto'] += $sum;
		
		if($item['taxvalue'] == '')
		{
			$invoicelist[$id]['valfree'] += $sum;
			$listdata['valfree'] += $sum;
		}
		else
			switch(round($item['taxvalue'],1))
			{
			    case '0.0':
				    $invoicelist[$id]['val0'] += $sum;
				    $listdata['val0'] += $sum;
			    break;
			    case '7.0':
				     $tax = round($item['value'] - ($item['value']/1.07), 2) * $item['count'];
				     $val = $sum - $tax;
				     $invoicelist[$id]['tax7'] += $tax;
				     $invoicelist[$id]['val7'] += $val;
			    	     $invoicelist[$id]['tax']  += $tax;
				     $listdata['tax7'] += $tax;
				     $listdata['val7'] += $val;
				     $listdata['tax']  += $tax;
			    break;
			    case '22.0':
				     $tax = round($item['value'] - ($item['value']/1.22), 2) * $item['count'];
				     $val = $sum - $tax;
				     $invoicelist[$id]['tax22'] += $tax;
				     $invoicelist[$id]['val22'] += $val;
			    	     $invoicelist[$id]['tax']   += $tax;
				     $listdata['tax22'] += $tax;
				     $listdata['val22'] += $val;
				     $listdata['tax']   += $tax;
			    break;
		    }
	}
	
}

$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('layout', $layout);
$SMARTY->assign('invoicelist', $invoicelist);
$SMARTY->display('invoicereport.html');

?>
