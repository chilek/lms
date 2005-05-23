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

if($covenantlist = $LMS->DB->GetAll('SELECT invoiceid, itemid, MIN(cdate) AS cdate, 
			SUM(CASE type WHEN 3 THEN value ELSE value*-1 END)*-1 AS value
			FROM cash LEFT JOIN invoices ON (invoiceid = invoices.id)
			WHERE customerid = ? AND invoiceid > 0 AND itemid > 0
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

$SESSION->remove('unpaid.'.$customerid);

$SMARTY->assign('covenantlist',$covenantlist);
$SMARTY->assign('customerid', $customerid);
$SMARTY->display('choosecovenants.html');

?>
