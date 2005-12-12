<?php

/*
 * LMS version 1.9-cvs
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

$type = isset($_GET['type']) ? $_GET['type'] : '';

switch($type)
{
	case 'customerbalance': /********************************************/
	
		$from = $_POST['from'];
		$to = $_POST['to'];

		// date format 'yyyy/mm/dd'	
		list($year, $month, $day) = split('/',$from);
		$date['from'] = mktime(0,0,0,$month,$day,$year);

		if($to) {
			list($year, $month, $day) = split('/',$to);
			$date['to'] = mktime(23,59,59,$month,$day,$year);
		} else { 
			$to = date('Y/m/d',time());
			$date['to'] = mktime(23,59,59); //koniec dnia dzisiejszego
		}

		$layout['pagetitle'] = trans('Customer $0 Balance Sheet ($1 to $2)',$LMS->GetCustomerName($_POST['customer']), ($from ? $from : ''), $to);
		
		$id = $_POST['customer'];

		if($tslist = $DB->GetAll('SELECT cash.id AS id, time, cash.value AS value, taxes.label AS taxlabel, customerid, comment, name AS username 
				    FROM cash 
				    LEFT JOIN taxes ON (taxid = taxes.id)
				    LEFT JOIN users ON users.id=userid 
				    WHERE customerid=? ORDER BY time', array($id))
		)
			foreach($tslist as $row)
				foreach($row as $column => $value)
					$saldolist[$column][] = $value;

		if(sizeof($saldolist['id']) > 0)
		{
			foreach($saldolist['id'] as $i => $v)
			{
				$saldolist['after'][$i] = $saldolist['balance'] + $saldolist['value'][$i];
				$saldolist['balance'] += $saldolist['value'][$i];
			        $saldolist['date'][$i] = date('Y/m/d H:i', $saldolist['time'][$i]);
				    
				if($saldolist['time'][$i]>=$date['from'] && $saldolist['time'][$i]<=$date['to'])
				{
					$list['id'][] = $saldolist['id'][$i];
					$list['after'][] = $saldolist['after'][$i];
					$list['before'][] = $saldolist['balance'];
					$list['value'][] = $saldolist['value'][$i];
					$list['taxlabel'][] = $saldolist['taxlabel'][$i];
					$list['date'][] = date('Y/m/d H:i',$saldolist['time'][$i]);
					$list['username'][] = $saldolist['username'][$i];
					$list['comment'][] = $saldolist['comment'][$i];
					$list['summary'] += $saldolist['value'][$i];
				}
			}
			
			$list['total'] = sizeof($list['id']);

		} else
			$list['balance'] = 0;

		$list['customerid'] = $id;
		
		$SMARTY->assign('balancelist', $list);
		$SMARTY->display('printcustomerbalance.html');
	break;	
	
	case 'balancelist': /********************************************/
	
		$from = $_POST['balancefrom'];
		$to = $_POST['balanceto'];

		// date format 'yyyy/mm/dd'	
		list($year, $month, $day) = split('/',$from);
		$date['from'] = mktime(0,0,0,$month,$day,$year);
		
		if($to) {
			list($year, $month, $day) = split('/',$to);
			$date['to'] = mktime(23,59,59,$month,$day,$year);
		} else {
			$to = date('Y/m/d',time());
			$date['to'] = mktime(23,59,59); //koniec dnia dzisiejszego
		}

		if($user = $_POST['user'])
			$layout['pagetitle'] = trans('Balance Sheet of User: $0 ($1 to $2)', $LMS->GetUserName($user), ($from ? $from : ''), $to);
		else
			$layout['pagetitle'] = trans('Balance Sheet ($0 to $1)', ($from ? $from : ''), $to);
			
		$customerslist = $DB->GetAllByKey('SELECT id, '.$DB->Concat('UPPER(lastname)',"' '",'name').' AS customername FROM customers','id');
		
		if($date['from'])
			$lastafter = $DB->GetOne('SELECT SUM(CASE WHEN customerid!=0 AND type=0 THEN 0 ELSE value END) FROM cash WHERE time<?', array($date['from']));
		
		if($balancelist = $DB->GetAll('SELECT cash.id AS id, time, userid, cash.value AS value, taxes.label AS taxlabel, customerid, comment, cash.type AS type
			    FROM cash LEFT JOIN taxes ON (taxid = taxes.id)
			    WHERE time>=? AND time<=? ORDER BY time ASC', array($date['from'], $date['to'])))
		{
			$x = 0;
			foreach($balancelist as $idx => $row)
			{
				if($user)
					if($row['userid']!=$user)
					{
						if($row['value']>0 || !$row['customerid'])  // skip cust. covenants
							$lastafter += $row['value'];
						unset($balancelist[$idx]);
						continue;
					}

				$list[$x]['value'] = $row['value'];
				$list[$x]['taxlabel'] = $row['taxlabel'];
				$list[$x]['time'] = $row['time'];
				$list[$x]['comment'] = $row['comment'];
				$list[$x]['customername'] = $customerslist[$row['customerid']]['customername'];

				if($row['customerid'])
				{
					if($row['type']==0)
	        			{
		                		// customer covenant
				                $list[$x]['after'] = $lastafter;
						$list[$x]['covenant'] = true;
						$list[$x]['value'] *= -1;
					}
					else
					{
						//customer payment
						$list[$x]['after'] = $lastafter + $list[$x]['value'];
						$listdata['incomeu'] += $list[$x]['value'];
					}
				}
				else
				{
					$list[$x]['after'] = $lastafter + $list[$x]['value'];
					
					if($row['value'] > 0)
        					//income
						$listdata['income'] += $list[$x]['value'];
			    		else
				        	//expense
						$listdata['expense'] -= $list[$x]['value'];
				}

				$lastafter = $list[$x]['after'];
				$x++;
				unset($balancelist[$idx]);
			}
		}
		
		$listdata['total'] = $listdata['income'] + $listdata['incomeu'] - $listdata['expense'];
		
		$SMARTY->assign('listdata', $listdata);
		$SMARTY->assign('balancelist', $list);
		$SMARTY->display('printbalancelist.html');
	break;

	case 'incomereport': /********************************************/
	
		$from = $_POST['from'];
		$to = $_POST['to'];

		// date format 'yyyy/mm/dd'	
		list($year, $month, $day) = split('/',$from);
		$date['from'] = mktime(0,0,0,$month,$day,$year);
		
		if($to) {
			list($year, $month, $day) = split('/',$to);
			$date['to'] = mktime(23,59,59,$month,$day,$year);
		} else {
			$to = date("Y/m/d",time());
			$date['to'] = mktime(23,59,59); // end of today
		}
		
		$layout['pagetitle'] = trans('Total Invoiceless Income ($0 to $1)',($from ? $from : ''), $to);
		
		$incomelist = $DB->GetAll('SELECT floor(time/86400)*86400 AS date, SUM(value) AS value
			FROM cash
			WHERE value>0 AND time>=? AND time<=? AND docid=0
			GROUP BY date ORDER BY date ASC',
			array($date['from'], $date['to']));

		$SMARTY->assign('incomelist', $incomelist);
		$SMARTY->display('printincomereport.html');
	break;

	case 'invoices': /********************************************/
	
		$from = $_POST['invoicefrom'];
		$to = $_POST['invoiceto'];

		// date format 'yyyy/mm/dd'	
		if($to) {
			list($year, $month, $day) = split('/',$to);
			$date['to'] = mktime(23,59,59,$month,$day,$year);
		} else { 
			$to = date('Y/m/d',time());
			$date['to'] = mktime(23,59,59); //koniec dnia dzisiejszego
		}

		if($from) {
			list($year, $month, $day) = split('/',$from);
			$date['from'] = mktime(0,0,0,$month,$day,$year);
		} else { 
			$from = date('Y/m/d',time());
			$date['from'] = mktime(0,0,0); //pocz±tek dnia dzisiejszego
		}

		$type = '';
		$type .= $_POST['invoiceorg'] ? '&original=1' : '';
		$type .= $_POST['invoicecopy'] ? '&copy=1' : '';
		$type .= $_POST['invoicedup'] ? '&duplicate=1' : '';
		if(!$type) $type = '&oryginal=1';
		
		$layout['pagetitle'] = trans('Invoices');
		header('Location: ?m=invoice&fetchallinvoices=1'.$type.'&customerid='.$_POST['customer'].'&from='.$date['from'].'&to='.$date['to']);
	break;	

	case 'transferforms': /********************************************/
	
		$from = $_POST['invoicefrom'];
		$to = $_POST['invoiceto'];

		// date format 'yyyy/mm/dd'	
		list($year, $month, $day) = split('/',$from);
		$date['from'] = mktime(0,0,0,$month,$day,$year);

		if($to) {
			list($year, $month, $day) = split('/',$to);
			$date['to'] = mktime(23,59,59,$month,$day,$year);
		} else { 
			$to = date('Y/m/d',time());
			$date['to'] = mktime(23,59,59); //koniec dnia dzisiejszego
		}
		
		$_GET['from'] = $date['from'];
		$_GET['to'] = $date['to'];
		require_once($_MODULES_DIR.'/transferforms.php');
		
	break;	

	case 'transferforms2': /********************************************/
		
		require_once($_MODULES_DIR.'/transferforms2.php');
	break;

	case 'liabilityreport': /********************************************/
	
		if(isset($_POST['day']) && $_POST['day']) 
		{
			list($year, $month, $day) = split('/',$_POST['day']);
			$reportday = mktime(0,0,0,$month,$day,$year);
			$today = $reportday;
		} else 
		{
			$reportday = time();
			$today = mktime(0,0,0);
		}

		$layout['pagetitle'] = trans('Liability Report on $0',date('Y/m/d', $reportday));

		$order = $_POST['order'];
		$direction = $_POST['direction'];
		$customerid = (isset($_POST['customer']) ? $_POST['customer'] : 0);

		$yearday = date('z', $reportday);
		$month = date('n', $reportday);
		$monthday = date('j', $reportday);
		$weekday = date('w', $reportday);
		
		switch($month) 
		{
		    case 1:
		    case 4:
		    case 7:
		    case 10: $quarterday = $monthday; break;
		    case 2:
		    case 5:
		    case 8:
		    case 11: $quarterday = $monthday + 100; break;
		    default: $quarterday = $monthday + 200; break;
		}
		
		$suspension_percentage = $CONFIG['finances']['suspension_percentage'];
		
		if($taxes = $LMS->GetTaxes($reportday, $reportday))
		{
			foreach($taxes as $tax)
			{
				$list1 =  $DB->GetAllByKey('SELECT customerid AS id, '.$DB->Concat('UPPER(lastname)',"' '",'customers.name').' AS customername, '
					.$DB->Concat('city',"' '",'address').' AS address, ten, 
					SUM(CASE suspended 
					    WHEN 0 THEN 
						(CASE discount 
						    WHEN 0 THEN tariffs.value 
						    ELSE ((100 - discount) * tariffs.value) / 100 
						END) 
					    ELSE 
						(CASE discount 
						    WHEN 0 THEN tariffs.value * '.$suspension_percentage.' / 100 
						    ELSE tariffs.value * discount * '.$suspension_percentage.' / 10000 
						END) 
					    END) AS value
						
					FROM assignments, tariffs, customers
					WHERE customerid = customers.id 
					AND tariffid = tariffs.id AND taxid=?
					AND deleted=0 
					AND (datefrom<=? OR datefrom=0) AND (dateto>=? OR dateto=0) 
					AND ((period='.DISPOSABLE.' AND at=?)
					    OR (period='.WEEKLY.'. AND at=?) 
					    OR (period='.MONTHLY.' AND at=?) 
					    OR (period='.QUARTERLY.' AND at=?) 
					    OR (period='.YEARLY.' AND at=?)) '
					.($customerid ? 'AND customerid='.$customerid : ''). 
					' GROUP BY customerid, lastname, customers.name, city, address, ten ', 'id',
					array($tax['id'], $reportday, $reportday, $today, $weekday, $monthday, $quarterday, $yearday));

				$list2 =  $DB->GetAllByKey('SELECT customerid AS id, '.$DB->Concat('UPPER(lastname)',"' '",'customers.name').' AS customername, '
					.$DB->Concat('city',"' '",'address').' AS address, ten, 
					SUM(CASE suspended 
					    WHEN 0 THEN 
						(CASE discount 
						    WHEN 0 THEN liabilities.value 
						    ELSE ((100 - discount) * liabilities.value) / 100 
						END) 
					    ELSE 
						(CASE discount 
						    WHEN 0 THEN liabilities.value * '.$suspension_percentage.' / 100 
						    ELSE liabilities.value * discount * '.$suspension_percentage.' / 10000 
						END) 
					    END) AS value
					FROM assignments, liabilities, customers
					WHERE customerid = customers.id 
					AND liabilityid = liabilities.id AND taxid=?
					AND deleted=0 
					AND (datefrom<=? OR datefrom=0) AND (dateto>=? OR dateto=0) 
					AND ((period='.DISPOSABLE.' AND at=?)
					    OR (period='.WEEKLY.'. AND at=?) 
					    OR (period='.MONTHLY.' AND at=?) 
					    OR (period='.QUARTERLY.' AND at=?) 
					    OR (period='.YEARLY.' AND at=?)) '
					.($customerid ? 'AND customerid='.$customerid : ''). 
					' GROUP BY customerid, lastname, customers.name, city, address, ten ', 'id',
					array($tax['id'], $reportday, $reportday, $today, $weekday, $monthday, $quarterday, $yearday));
				
				$list = array_merge($list1, $list2);

				if($list)
				{
					foreach($list as $row)
					{
						$idx = $row['id'];
						if(!isset($reportlist[$idx]))
						{ 
							$reportlist[$idx]['id'] = $row['id'];
							$reportlist[$idx]['customername'] = $row['customername'];
							$reportlist[$idx]['address'] = $row['address'];
							$reportlist[$idx]['ten'] = $row['ten'];
						}
						$reportlist[$idx]['value'] += $row['value'];
						$reportlist[$idx][$tax['id']]['netto'] = round($row['value']-$row['value']*$tax['value']/100, 2);
						$reportlist[$idx][$tax['id']]['tax'] = $row['value'] - $reportlist[$idx][$tax['id']]['netto'];
						$reportlist[$idx]['taxsum'] += $reportlist[$idx][$tax['id']]['tax'];
						$total['netto'][$tax['id']] += $reportlist[$idx][$tax['id']]['netto'];
						$total['tax'][$tax['id']] += $reportlist[$idx][$tax['id']]['tax'];
					}
				}
			}

			switch($order)
			{
				case 'customername':
					foreach($reportlist as $idx => $row)
					{
    						$table['idx'][] = $idx;
	        				$table['customername'][] = $row['customername'];
					}
	    				if(is_array($table))
	    				{
	            				array_multisort($table['customername'],($direction == 'desc' ? SORT_DESC : SORT_ASC), $table['idx']);
			        		foreach($table['idx'] as $idx)
				    			$tmplist[] = $reportlist[$idx];
					}
					$reportlist = $tmplist;		
				break;
	
				default:
					foreach($reportlist as $idx => $row)
					{
    						$table['idx'][] = $idx;
        					$table['value'][] = $row['value'];
					}
		    			if(is_array($table))
	    				{
	            				array_multisort($table['value'],($direction == 'desc' ? SORT_DESC : SORT_ASC), $table['idx']);
			    	    		foreach($table['idx'] as $idx)
					    		$tmplist[] = $reportlist[$idx];
					}
					$reportlist = $tmplist;				
				break;
			}
		}

		$SMARTY->assign('reportlist', $reportlist);
		$SMARTY->assign('total',$total);
		$SMARTY->assign('taxes', $taxes);
		$SMARTY->assign('taxescount', sizeof($taxes));
		$SMARTY->display('printliabilityreport.html');
	break;

	default: /*******************************************************/
	
		$layout['pagetitle'] = trans('Printing');
		
		$SMARTY->assign('customers', $LMS->GetCustomerNames());
		$SMARTY->assign('groups', $LMS->CustomergroupGetAll());
		$SMARTY->assign('users', $LMS->GetUserNames());
		$SMARTY->assign('networks', $LMS->GetNetworks());
		$SMARTY->assign('customergroups', $LMS->CustomergroupGetAll());
		$SMARTY->assign('printmenu', 'finances');
		$SMARTY->display('printindex.html');
	break;
}

?>
