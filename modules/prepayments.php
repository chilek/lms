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

if(!$customerid || $LMS->UserExists($customerid)!=TRUE)
{
	$SESSION->redirect('?m='.$SESSION->get('backto'));
}

$pmarks = $_POST['pmarks'];
$cmarks = $_POST['cmarks'];

if(sizeof($pmarks) && sizeof($cmarks))
{
	foreach($pmarks as $mark)
	{
		$mark = $LMS->DB->GetRow('SELECT id, value, comment, adminid, time, type, customerid, taxvalue
					FROM cash WHERE id = ?', array($mark));

		while($mark['value'] > 0 && !$finish)
		{
			foreach($cmarks as $idx => $item)
			{
				$row = $LMS->DB->GetRow('SELECT itemid, invoiceid, taxvalue FROM cash WHERE id = ?', array($item));
				$value = $LMS->GetItemUnpaidValue($row['invoiceid'], $row['itemid']);

				if($value>=$mark['value'])
				{
					if($row['taxvalue']=='')
						$LMS->DB->Execute('UPDATE cash SET itemid = ?, invoiceid = ?, taxvalue = NULL
							WHERE id = ?', array($row['itemid'], $row['invoiceid'], $mark['id']));
					else
						$LMS->DB->Execute('UPDATE cash SET itemid = ?, invoiceid = ?, taxvalue = ?
							WHERE id = ?', array($row['itemid'], $row['invoiceid'], $row['taxvalue'], $mark['id']));
					$mark['value'] = 0;	
					break;
				}
				else
				{
					if($row['taxvalue']=='')
						$LMS->DB->Execute('UPDATE cash SET itemid = ?, invoiceid = ?, value = ?, taxvalue = NULL
							    WHERE id = ?', array($row['itemid'], $row['invoiceid'], $value, $mark['id']));
					else
						$LMS->DB->Execute('UPDATE cash SET itemid = ?, invoiceid = ?, value = ?, taxvalue = ?
							    WHERE id = ?', array($row['itemid'], $row['invoiceid'], $value, $row['taxvalue'], $mark['id']));
					
					$mark['value'] -= $value;
					$LMS->AddBalance($mark);
					
					$mark['id'] = $LMS->DB->GetOne('SELECT id FROM cash WHERE customerid = ? AND invoiceid = 0 AND value = ? AND time = ? AND type = 3 AND comment = ?',
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

if($covenantlist = $LMS->DB->GetAll('SELECT invoiceid, itemid, MIN(cdate) AS cdate, 
			SUM(CASE type WHEN 3 THEN value ELSE value*-1 END)*-1 AS value
			FROM cash LEFT JOIN invoices ON (invoiceid = invoices.id)
			WHERE invoices.customerid = ? AND invoiceid > 0 AND itemid > 0
			GROUP BY invoiceid, itemid
			HAVING SUM(CASE type WHEN 3 THEN value ELSE value*-1 END)*-1 > 0
			ORDER BY cdate', array($customerid)))
{
	foreach($covenantlist as $idx => $row)
	{
		$record = $LMS->DB->GetRow('SELECT cash.id AS id, number, taxvalue, comment
					    FROM cash LEFT JOIN invoices ON (invoiceid = invoices.id)
					    WHERE invoiceid = ? AND itemid = ? AND type = 4',
					    array($row['invoiceid'], $row['itemid']));
		
		$record['invoice'] = $_CONFIG['invoices']['number_template'];
		$record['invoice'] = str_replace('%M', date('m', $row['cdate']), $record['invoice']);
		$record['invoice'] = str_replace('%Y', date('Y', $row['cdate']), $record['invoice']);
		$record['invoice'] = str_replace('%N', $record['number'], $record['invoice']);

		if(in_array($record['id'], (array) $SESSION->get('unpaid.'.$customerid)))
			$record['selected'] = TRUE;
		
		$covenantlist[$idx] = array_merge($record, $covenantlist[$idx]);
	}
}

$prepaymentlist = $LMS->DB->GetAll('SELECT id, time, value, taxvalue, comment
			FROM cash WHERE customerid = ? AND invoiceid = 0 AND type = 3
			ORDER BY time', array($customerid));

$layout['pagetitle'] = trans('Prepayments of Customer: $0', '<A href="?m=userinfo&id='.$customerid.'">'.$LMS->GetUserName($customerid).'</A>');

$SMARTY->assign('covenantlist',$covenantlist);
$SMARTY->assign('prepaymentlist',$prepaymentlist);
$SMARTY->assign('customerid', $customerid);
$SMARTY->display('prepayments.html');

?>
