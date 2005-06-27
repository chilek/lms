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

$customerid = $_GET['id'];

if(!$customerid || $LMS->CustomerExists($customerid)!=TRUE)
{
	$SESSION->redirect('?m='.$SESSION->get('backto'));
}

$pmarks = $_POST['pmarks'];
$cmarks = $_POST['cmarks'];

if(sizeof($pmarks) && sizeof($cmarks))
{
	foreach($pmarks as $mark)
	{
		$mark = $DB->GetRow('SELECT id, value, comment, userid, time, type, customerid
					FROM cash WHERE id = ?', array($mark));

		while($mark['value'] > 0 && !$finish)
		{
			foreach($cmarks as $idx => $item)
			{
				$row = $DB->GetRow('SELECT itemid, docid FROM cash WHERE id = ?', array($item));
				$value = $LMS->GetItemUnpaidValue($row['docid'], $row['itemid']);

				if($value>=$mark['value'])
				{
					$DB->Execute('UPDATE cash SET reference = ? WHERE id = ?', array($item, $mark['id']));
					$mark['value'] = 0;	
					break;
				}
				else
				{
					$DB->Execute('UPDATE cash SET reference = ?, value = ? WHERE id = ?', array($item, $value, $mark['id']));
					
					$mark['value'] -= $value;
					$LMS->AddBalance($mark);
					
					$mark['id'] = $DB->GetOne('SELECT id FROM cash WHERE customerid = ? AND docid = 0 AND value = ? AND time = ? AND type = 3 AND comment = ?',
								    array($mark['customerid'], $mark['value'], $mark['time'], $mark['comment']));
					
					if(sizeof($cmarks)>1) 
						unset($cmarks[$idx]);
					else 
						$finish = 1;
				}
			}
		}
	}
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
		$record = $DB->GetRow('SELECT cash.id AS id, number, taxes.label AS tax, comment
					    FROM cash LEFT JOIN documents ON (docid = documents.id)
					    LEFT JOIN taxes ON (taxid = taxes.id)
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
// join with documents here is for backward compatybility
// before revolution we've bind payments with invoices by docid (not reference)
$prepaymentlist = $DB->GetAll('SELECT cash.id AS id, time, value, comment
			FROM cash LEFT JOIN documents ON (docid = documents.id AND documents.type=1)
			WHERE cash.customerid = ? AND reference = 0 AND cash.type = 3 AND documents.type IS NULL
			ORDER BY time', array($customerid));

$layout['pagetitle'] = trans('Prepayments of Customer: $0', '<A href="?m=customerinfo&id='.$customerid.'">'.$LMS->GetCustomerName($customerid).'</A>');

$SMARTY->assign('covenantlist',$covenantlist);
$SMARTY->assign('prepaymentlist',$prepaymentlist);
$SMARTY->assign('customerid', $customerid);
$SMARTY->display('prepayments.html');

?>
