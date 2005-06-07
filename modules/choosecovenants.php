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

$layout['pagetitle'] = trans('Select Unpaid Covenants');

$customerid = $_GET['id'];

if(isset($_POST['marks']))
{
	$SESSION->save('unpaid.'.$customerid, $_POST['marks']);
	$SESSION->close();
	die;
}

if($covenantlist = $DB->GetAll('SELECT a.docid AS docid, a.itemid AS itemid, MIN(cdate) AS cdate, 
			ROUND(SUM(CASE a.type WHEN 3 THEN a.value*-1 ELSE a.value END)/(CASE COUNT(b.id) WHEN 0 THEN 1 ELSE COUNT(b.id) END),2)
			+ COALESCE(SUM(CASE b.type WHEN 3 THEN b.value*-1 ELSE b.value END),0) AS value
			FROM cash a 
			LEFT JOIN documents d ON (a.docid = d.id)
			LEFT JOIN cash b ON (a.id = b.reference)
			WHERE d.customerid = ? AND d.type = 1 
			AND a.docid > 0 AND a.itemid > 0
			GROUP BY a.docid, a.itemid
			HAVING ROUND(SUM(CASE a.type WHEN 3 THEN a.value*-1 ELSE a.value END)/(CASE COUNT(b.id) WHEN 0 THEN 1 ELSE COUNT(b.id) END),2)
			+ COALESCE(SUM(CASE b.type WHEN 3 THEN b.value*-1 ELSE b.value END),0) > 0
			ORDER BY cdate', array($customerid)))
{
	foreach($covenantlist as $idx => $row)
	{
		$record = $DB->GetRow('SELECT cash.id AS id, number, taxvalue, comment
					    FROM cash LEFT JOIN documents ON (docid = documents.id)
					    WHERE docid = ? AND itemid = ? AND cash.type = 4',
					    array($row['docid'], $row['itemid']));
		
		$record['invoice'] = $CONFIG['invoices']['number_template'];
		$record['invoice'] = str_replace('%M', date('m', $row['cdate']), $record['invoice']);
		$record['invoice'] = str_replace('%Y', date('Y', $row['cdate']), $record['invoice']);
		$record['invoice'] = str_replace('%N', $record['number'], $record['invoice']);

		if(in_array($record['id'], (array) $SESSION->get('unpaid.'.$customerid)))
			$record['selected'] = TRUE;
		
		$covenantlist[$idx] = array_merge($record, $covenantlist[$idx]);
	}
}

$SESSION->remove('unpaid.'.$customerid);

$SMARTY->assign('covenantlist',$covenantlist);
$SMARTY->assign('customerid', $customerid);
$SMARTY->display('choosecovenants.html');

?>
