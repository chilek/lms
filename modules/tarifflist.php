<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2008 LMS Developers
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

function GetTariffList($order='name,asc', $type=NULL)
{
	global $DB, $LMS;

	if($order=='')
                $order='name,asc';
	
	list($order,$direction) = sscanf($order, '%[^,],%s');
	
	($direction=='desc') ? $direction = 'desc' : $direction = 'asc';
	
	switch($order)
	{
		case 'id':
			$sqlord = ' ORDER BY id';
		break;
		case 'description':
		        $sqlord = ' ORDER BY t.description';
		break;
		case 'value':
		        $sqlord = ' ORDER BY t.value';
		break;
		case 'downrate':
		        $sqlord = ' ORDER BY t.downrate';
		break;
		case 'downceil':
		        $sqlord = ' ORDER BY t.downceil';
		break;
		case 'uprate':
		        $sqlord = ' ORDER BY t.uprate';
		break;
		case 'upceil':
		        $sqlord = ' ORDER BY t.upceil';
		break;
		case 'count':
		        $sqlord = ' ORDER BY customerscount';
		break;
		default:
	                $sqlord = ' ORDER BY t.name';
		break;
	}
	
	$totalincome = 0;
	$totalcustomers = 0;
	$totalcount = 0;
	$totalactivecount = 0;

	if($tarifflist = $DB->GetAll('SELECT t.id AS id, t.name, t.value AS value, 
			taxes.label AS tax, taxes.value AS taxvalue, prodid, 
			t.description AS description, uprate, downrate, 
			upceil, downceil, climit, plimit,
			a.customerscount, a.count, a.value AS sumval
			FROM tariffs t 
			LEFT JOIN (SELECT tariffid, COUNT(*) AS count,
				COUNT(DISTINCT customerid) AS customerscount,
				SUM(CASE period 
					WHEN '.DAILY.' THEN tt.value*30 
					WHEN '.WEEKLY.' THEN tt.value*4 
					WHEN '.MONTHLY.' THEN tt.value 
					WHEN '.QUARTERLY.' THEN tt.value/3 
					WHEN '.YEARLY.' THEN tt.value/12 END) AS value
				FROM assignments
				JOIN tariffs tt ON (tt.id = tariffid)
				GROUP BY tariffid
			) a ON (a.tariffid = t.id)
			LEFT JOIN taxes ON (t.taxid = taxes.id)'
			.($type ? ' WHERE t.type = '.intval($type) : '')
			.($sqlord != '' ? $sqlord.' '.$direction : '')))
	{
		$unactive = $DB->GetAllByKey('SELECT tariffid, COUNT(*) AS count, 
			SUM(CASE x.period 
				WHEN '.DAILY.' THEN x.value*30 
				WHEN '.WEEKLY.' THEN x.value*4 
				WHEN '.MONTHLY.' THEN x.value 
				WHEN '.QUARTERLY.' THEN x.value/3 
				WHEN '.YEARLY.' THEN x.value/12 END) AS value
			FROM (SELECT a.tariffid, a.period, t.value
				FROM assignments a 
				JOIN tariffs t ON (t.id = a.tariffid)
				WHERE (
					a.suspended = 1
				        OR a.datefrom > ?NOW?
				        OR (a.dateto <= ?NOW? AND a.dateto != 0)
					OR EXISTS (
					        SELECT 1 FROM assignments b 
						WHERE b.customerid = a.customerid 
							AND liabilityid = 0 AND tariffid = 0
						        AND (b.datefrom <= ?NOW? OR b.datefrom = 0)
							AND (b.dateto > ?NOW? OR b.dateto = 0)
					)
				)'
				.($type ? ' AND t.type = '.intval($type) : '')
			.') x GROUP BY tariffid', 'tariffid');

		foreach($tarifflist as $idx => $row)
		{
			// count of 'active' assignments
			$tarifflist[$idx]['activecount'] = $row['count'] - (isset($unactive[$row['id']]) ? $unactive[$row['id']]['count'] : 0);
			// avg monthly income
			$tarifflist[$idx]['income'] = $row['sumval'] - (isset($unactive[$row['id']]) ? $unactive[$row['id']]['value'] : 0);

			$totalincome += $tarifflist[$idx]['income'];
			$totalcount += $tarifflist[$idx]['count'];
			$totalcustomers += $tarifflist[$idx]['customerscount'];
			$totalactivecount += $tarifflist[$idx]['activecount'];
		}

		switch($order)
		{
        		case 'income':
	            		foreach($tarifflist as $idx => $row)
			        {
				        $table['idx'][] = $idx;
				        $table['income'][] = $row['income'];
				}
				if(isset($table))
				{
					array_multisort($table['income'],($direction == "desc" ? SORT_DESC : SORT_ASC), $table['idx']);
					foreach($table['idx'] as $idx)
				                $ntarifflist[] = $tarifflist[$idx];
	
					$tarifflist = $ntarifflist;
				}
			break;
		}
	}

	$tarifflist['total'] = sizeof($tarifflist);
	$tarifflist['totalincome'] = $totalincome;
	$tarifflist['totalcustomers'] = $totalcustomers;
	$tarifflist['totalcount'] = $totalcount;
	$tarifflist['totalactivecount'] = $totalactivecount;
	$tarifflist['type'] = $type;
	$tarifflist['order'] = $order;
	$tarifflist['direction'] = $direction;

	return $tarifflist;
}

if(!isset($_GET['o']))
        $SESSION->restore('tlo', $o);
else
        $o = $_GET['o'];
$SESSION->save('tlo', $o);

if(!isset($_GET['t']))
        $SESSION->restore('tlt', $t);
else
        $t = $_GET['t'];
$SESSION->save('tlt', $t);

$tarifflist = GetTariffList($o, $t);

$listdata['total'] = $tarifflist['total'];
$listdata['totalincome'] = $tarifflist['totalincome'];
$listdata['totalcustomers'] = $tarifflist['totalcustomers'];
$listdata['totalcount'] = $tarifflist['totalcount'];
$listdata['totalactivecount'] = $tarifflist['totalactivecount'];
$listdata['type'] = $tarifflist['type'];
$listdata['order'] = $tarifflist['order'];
$listdata['direction'] = $tarifflist['direction'];

unset($tarifflist['total']);
unset($tarifflist['totalincome']);
unset($tarifflist['totalcustomers']);
unset($tarifflist['totalcount']);
unset($tarifflist['totalactivecount']);
unset($tarifflist['type']);
unset($tarifflist['order']);
unset($tarifflist['direction']);

$layout['pagetitle'] = trans('Subscription List');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('tarifflist',$tarifflist);
$SMARTY->assign('listdata',$listdata);
$SMARTY->display('tarifflist.html');

?>
