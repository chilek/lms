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

function set_taxes($taxid)
{
	global $taxes, $DB;

	if (empty($taxes[$taxid]))
		$taxes[$taxid] = $DB->GetRow('SELECT id, value, label, taxed
			FROM taxes WHERE id = ?', array($taxid));
}

$from = $_POST['from'];
$to = $_POST['to'];

// date format 'yyyy/mm/dd'
if($from) {
	list($year, $month, $day) = explode('/',$from);
	$unixfrom = mktime(0,0,0,$month,$day,$year);
} else {
	$from = date('Y/m/d',time());
	$unixfrom = mktime(0,0,0); //today
}
if($to) {
	list($year, $month, $day) = explode('/',$to);
	$unixto = mktime(23,59,59,$month,$day,$year);
} else {
	$to = date('Y/m/d',time());
	$unixto = mktime(23,59,59); //today
}

$layout['pagetitle'] = trans('Sale Registry for period $a - $b', $from, $to);

$listdata = array('tax' => 0, 'brutto' => 0);
$invoicelist = array();
$taxeslist = array();
$taxes = array();
$taxescount = 0;

if(!empty($_POST['group']))
{
	if(is_array($_POST['group'])) {
		$groups = array_map('intval', $_POST['group']);
		$groups = implode(',', $groups);
	}
	else
		$groups = intval($_POST['group']);

	$groupwhere = ' AND '.(isset($_POST['groupexclude']) ? 'NOT' : '').' 
		EXISTS (SELECT 1 FROM customerassignments a
			WHERE a.customergroupid IN ('.$groups.')
			AND a.customerid = d.customerid)';

	$names = $DB->GetAll('SELECT name FROM customergroups WHERE id IN ('.$groups.')');

	$groupnames = '';
	foreach($names as $idx => $row)
		$groupnames .= ($idx ? ', ' : '') . $row['name'];

	if(isset($_POST['groupexclude']))
		$layout['group'] = trans('Group: all excluding $a', $groupnames);
	else
		$layout['group'] = trans('Group: $a', $groupnames);
}

if(!empty($_POST['division']))
{
	$divwhere = ' AND d.divisionid '.(isset($_POST['divexclude']) ? '!=' : '=').' '.intval($_POST['division']);

	$divname = $DB->GetOne('SELECT name FROM divisions WHERE id = ?', 
			array(intval($_POST['division'])));

	$layout['division'] = $divname;
}

// Sorting
switch ($_POST['datetype']) {
	case 'sdate':
		$sortcol = 'COALESCE(d.sdate, d.cdate)';
		break;
	case 'pdate':
		$sortcol = '(d.cdate + (d.paytime * 86400))';
		break;
	case 'cdate':
	default:
		$sortcol = 'd.cdate';
}

// we can't simply get documents with SUM(value*count)
// because we need here incoices-like round-off

// get documents items numeric values for calculations
$items = $DB->GetAll('SELECT c.docid, c.itemid, c.taxid, c.value, c.count,
	d.number, d.cdate, d.sdate, d.paytime, d.customerid, d.reference,
	d.name, d.address, d.zip, d.city, d.ten, d.ssn, n.template
	    FROM documents d
	    LEFT JOIN invoicecontents c ON c.docid = d.id
	    LEFT JOIN numberplans n ON d.numberplanid = n.id
	    WHERE (d.type = ? OR d.type = ?) AND ('.$sortcol.' BETWEEN ? AND ?) '
	    .($_POST['numberplanid'] ? 'AND d.numberplanid = '.intval($_POST['numberplanid']) : '')
	    .(isset($divwhere) ? $divwhere : '')
	    .(isset($groupwhere) ? $groupwhere : '')
	    .' AND NOT EXISTS (
                	    SELECT 1 FROM customerassignments a
			    JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
			    WHERE e.userid = lms_current_user() AND a.customerid = d.customerid)
	    ORDER BY CEIL('.$sortcol.'/86400), d.id',
	    array(DOC_INVOICE, DOC_CNOTE, $unixfrom, $unixto));

if($items)
{
	foreach($items as $row)
	{
		$idx = $row['docid'];
		$taxid = $row['taxid'];

		set_taxes($taxid);

		$invoicelist[$idx]['custname'] = $row['name'];
		$invoicelist[$idx]['custaddress'] = $row['zip'].' '.$row['city'].', '.$row['address'];
		$invoicelist[$idx]['ten'] = ($row['ten'] ? trans('TEN').' '.$row['ten'] : ($row['ssn'] ? trans('SSN').' '.$row['ssn'] : ''));
		$invoicelist[$idx]['number'] = docnumber($row['number'], $row['template'], $row['cdate']);
		$invoicelist[$idx]['cdate'] = $row['cdate'];
		$invoicelist[$idx]['sdate'] = $row['sdate'];
		$invoicelist[$idx]['pdate'] = $row['cdate'] + ($row['paytime'] * 86400);
		$invoicelist[$idx]['customerid'] = $row['customerid'];

		if(!isset($invoicelist[$idx][$taxid]))
		{
			$invoicelist[$idx][$taxid]['tax'] = 0;
			$invoicelist[$idx][$taxid]['val'] = 0;
		}

		if(!isset($invoicelist[$idx]['tax'])) $invoicelist[$idx]['tax'] = 0;
		if(!isset($invoicelist[$idx]['brutto'])) $invoicelist[$idx]['brutto'] = 0;

		if($row['reference'])
		{
			// I think we can simply do query here instead of building
			// big sql join in $items query, we've got so many credit notes?
			$item = $DB->GetRow('SELECT taxid, value, count
						FROM invoicecontents 
						WHERE docid=? AND itemid=?', 
						array($row['reference'], $row['itemid']));

			$row['value'] += $item['value'];
			$row['count'] += $item['count'];

			set_taxes($item['taxid']);

			$refitemsum = $item['value'] * $item['count'];
			$refitemval = round($refitemsum / ($taxes[$item['taxid']]['value']+100) * 100, 2);
			$refitemtax = $refitemsum - $refitemval;

			$invoicelist[$idx][$item['taxid']]['tax'] -= $refitemtax;
			$invoicelist[$idx][$item['taxid']]['val'] -= $refitemval;
			$invoicelist[$idx]['tax'] -= $refitemtax;
			$invoicelist[$idx]['brutto'] -= $refitemsum;

			$listdata[$item['taxid']]['tax'] -= $refitemtax;
			$listdata[$item['taxid']]['val'] -= $refitemval;
			$listdata['tax'] -= $refitemtax;
			$listdata['brutto'] -= $refitemsum;
		}

		$sum = $row['value'] * $row['count'];
		$val = round($sum / ($taxes[$taxid]['value']+100) * 100, 2);
		$tax = $sum - $val;

		$invoicelist[$idx][$taxid]['tax'] += $tax;
		$invoicelist[$idx][$taxid]['val'] += $val;
		$invoicelist[$idx]['tax'] += $tax;
		$invoicelist[$idx]['brutto'] += $sum;

		if(!isset($listdata[$taxid]))
		{
			$listdata[$taxid]['tax'] = 0;
			$listdata[$taxid]['val'] = 0;
		}

		$listdata[$taxid]['tax'] += $tax;
		$listdata[$taxid]['val'] += $val;
		$listdata['tax'] += $tax;
		$listdata['brutto'] += $sum;
	}

	// get used tax rates for building report table
	foreach($listdata as $idx => $val)
		if(is_int($idx)) {
		    $tax = $taxes[$idx];
		    $tax['value'] = f_round($tax['value']);
			$taxeslist[$idx] = $tax;
			$taxescount += $tax['value'] ? 2 : 1;
        }
}

$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('taxes', $taxeslist);
$SMARTY->assign('taxescount', $taxescount);
$SMARTY->assign('layout', $layout);
$SMARTY->assign('invoicelist', $invoicelist);

if(isset($_POST['extended']))
{
	$pages = array();
	$totals = array();
	$reccount = sizeof($invoicelist);

	// hidden option: records count for one page of printout
	// I thinks 20 records is fine, but someone needs 19.
	$rows = isset($CONFIG['phpui']['printout_pagelimit']) ? $CONFIG['phpui']['printout_pagelimit'] : 20;

	// create a new array for use with {section}
	// and do some calculations (summaries)
	$i=1;
	foreach($invoicelist as $row)
	{
		$invoicelist2[] = $row;

		$page = ceil($i/$rows);

		$totals[$page]['total'] += $row['brutto'];
		$totals[$page]['sumtax'] += $row['tax'];

		foreach($taxeslist as $idx => $tax)
		{
			$totals[$page]['val'][$idx] += $row[$idx]['val'];
			$totals[$page]['tax'][$idx] += $row[$idx]['tax'];
		}

		$i++;
	}

	foreach($totals as $page => $t)
	{
		$pages[] = $page;

		$totals[$page]['alltotal'] = $totals[$page-1]['alltotal'] + $t['total'];
		$totals[$page]['allsumtax'] = $totals[$page-1]['allsumtax'] + $t['sumtax'];

		foreach($taxeslist as $idx => $tax)
		{
			$totals[$page]['allval'][$idx] = $totals[$page-1]['allval'][$idx] + $t['val'][$idx];
			$totals[$page]['alltax'][$idx] = $totals[$page-1]['alltax'][$idx] + $t['tax'][$idx];
		}
	}

	$SMARTY->assign('invoicelist', $invoicelist2);
	$SMARTY->assign('pages', $pages);
	$SMARTY->assign('rows', $rows);
	$SMARTY->assign('totals', $totals);
	$SMARTY->assign('pagescount', sizeof($pages));
	$SMARTY->assign('reccount', $reccount);
	if (strtolower($CONFIG['phpui']['report_type']) == 'pdf') {
		$output = $SMARTY->fetch('invoicereport-ext.html');
		html2pdf($output, trans('Reports'), $layout['pagetitle'], NULL, NULL, 'L', array(5, 5, 5, 5), ($_GET['save'] == 1) ? true : false);
	} else {
		$SMARTY->display('invoicereport-ext.html');
	}
}
else {
	if (strtolower($CONFIG['phpui']['report_type']) == 'pdf') {
		$output = $SMARTY->fetch('invoicereport.html');
		html2pdf($output, trans('Reports'), $layout['pagetitle'], NULL, NULL, 'L', array(5, 5, 5, 5), ($_GET['save'] == 1) ? true : false);
	} else {
		$SMARTY->display('invoicereport.html');
	}
}

?>
