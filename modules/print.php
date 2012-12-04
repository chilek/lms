<?php

/*
 * LMS version 1.11-cvs
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

if (!check_conf('privileges.reports'))
	access_denied();

$type = isset($_GET['type']) ? $_GET['type'] : '';

switch($type)
{
	case 'customerbalance': /********************************************/

		if (!check_conf('privileges.finances_management'))
			access_denied();

		$from = $_POST['from'];
		$to = $_POST['to'];

		// date format 'yyyy/mm/dd'	
		if($from && preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $from))
		{
			list($year, $month, $day) = explode('/',$from);
			$date['from'] = mktime(0, 0, 0, (int)$month, (int)$day, (int)$year);
		}
		else
			$date['from'] = 0;

		if($to && preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $to))
		{
			list($year, $month, $day) = explode('/',$to);
			$date['to'] = mktime(23,59,59,$month,$day,$year);
		} else { 
			$to = date('Y/m/d',time());
			$date['to'] = mktime(23,59,59); //koniec dnia dzisiejszego
		}

		$id = intval($_POST['customer']);

		$layout['pagetitle'] = trans('Customer $a Balance Sheet ($b to $c)',$LMS->GetCustomerName($id), ($from ? $from : ''), $to);

		$list['balance'] = 0;
		$list['income'] = 0;
		$list['expense'] = 0;
		$list['liability'] = 0;
		$list['summary'] = 0;
		$list['customerid'] = $id;

		if($tslist = $DB->GetAll('SELECT c.id AS id, time, type, c.value AS value, 
				    taxes.label AS taxlabel, customerid, comment, name AS username 
				    FROM cash c
				    LEFT JOIN taxes ON (c.taxid = taxes.id)
				    LEFT JOIN users ON (users.id = userid)
				    WHERE c.customerid = ? 
					    AND NOT EXISTS (
				                    SELECT 1 FROM customerassignments a
					            JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
					            WHERE e.userid = lms_current_user() AND a.customerid = ?)
				    ORDER BY time', array($id, $id))
		)
		{
			foreach($tslist as $row)
				foreach($row as $column => $value)
					$saldolist[$column][] = $value;

			$saldolist['balance'] = 0;

			foreach($saldolist['id'] as $i => $v)
			{
				$saldolist['after'][$i] = $saldolist['balance'] + $saldolist['value'][$i];
				$saldolist['balance'] += $saldolist['value'][$i];
			        $saldolist['date'][$i] = date('Y/m/d H:i', $saldolist['time'][$i]);

				if($saldolist['time'][$i]>=$date['from'] && $saldolist['time'][$i]<=$date['to'])
				{
					$list['id'][] = $saldolist['id'][$i];
					$list['type'][] = $saldolist['type'][$i];
					$list['after'][] = $saldolist['after'][$i];
					$list['before'][] = $saldolist['balance'];
					$list['value'][] = $saldolist['value'][$i];
					$list['taxlabel'][] = $saldolist['taxlabel'][$i];
					$list['date'][] = date('Y/m/d H:i',$saldolist['time'][$i]);
					$list['username'][] = $saldolist['username'][$i];
					$list['comment'][] = $saldolist['comment'][$i];
					$list['summary'] += $saldolist['value'][$i];
					
					if($saldolist['type'][$i])
					{
						if($saldolist['value'][$i] > 0)
					    		//income
						        $list['income'] += $saldolist['value'][$i];
						else
						        //expense
						        $list['expense'] -= $saldolist['value'][$i];
					}
					else
					        $list['liability'] -= $saldolist['value'][$i];
				}
			}
			
			$list['total'] = sizeof($list['id']);
		}
		
		$SMARTY->assign('balancelist', $list);
		if (strtolower($CONFIG['phpui']['report_type']) == 'pdf') {
			$output = $SMARTY->fetch('printcustomerbalance.html');
			html2pdf($output, trans('Reports'), $layout['pagetitle']);
		} else {
			$SMARTY->display('printcustomerbalance.html');
		}
	break;
	
	case 'balancelist': /********************************************/

		if (!check_conf('privileges.finances_management'))
			access_denied();

		$from = $_POST['balancefrom'];
		$to = $_POST['balanceto'];
		$net = intval($_POST['network']);
		$group = intval($_POST['customergroup']);
		$division = intval($_POST['division']);
		$source = intval($_POST['source']);
		$types = isset($_POST['types']) ? $_POST['types'] : NULL;
		$docs = $_POST['docs'];

		// date format 'yyyy/mm/dd'
		if($from)
		{
			list($year, $month, $day) = explode('/',$from);
			$date['from'] = mktime(0,0,0,(int)$month,(int)$day,(int)$year);
		}

		if($to) {
			list($year, $month, $day) = explode('/',$to);
			$date['to'] = mktime(23,59,59,$month,$day,$year);
		} else {
			$to = date('Y/m/d',time());
			$date['to'] = mktime(23,59,59); //koniec dnia dzisiejszego
		}

		if($net)
		        $net = $LMS->GetNetworkParams($net);

		if($user = $_POST['user'])
			$layout['pagetitle'] = trans('Balance Sheet of User: $a ($b to $c)', $LMS->GetUserName($user), ($from ? $from : ''), $to);
		else
			$layout['pagetitle'] = trans('Balance Sheet ($a to $b)', ($from ? $from : ''), $to);
	
		if($types)
		{
			foreach($types as $tt)
				switch($tt)
				{
					case 1:
						$typewhere[] = 'c.type = 0';
						$typetxt[] = trans('Liability');
					break;
					case 2:
						$typewhere[] = '(c.type = 1 AND c.value > 0)';
						$typetxt[] = trans('Income');
					break;
					case 3: // expense
						$typewhere[] = '(c.type = 1 AND c.value < 0)';
						$typetxt[] = trans('Expense');
					break;
				}
			
			$typewhere = ' AND ('.implode(' OR ', $typewhere).')';
		}
		
		$customerslist = $DB->GetAllByKey('SELECT id, '.$DB->Concat('UPPER(lastname)',"' '",'name').' AS customername FROM customers','id');
		
		if(isset($date['from']))
			$lastafter = $DB->GetOne('SELECT SUM(CASE WHEN c.customerid!=0 AND type=0 THEN 0 ELSE value END) 
					FROM cash c '
					.($group ? 'LEFT JOIN customerassignments a ON (c.customerid = a.customerid) ' : '')
					.'WHERE time<?'
					.($docs ? ($docs == 'documented' ? ' AND c.docid > 0' : ' AND c.docid = 0') : '')
					.($source ? ' AND c.sourceid = '.intval($source) : '')
					.($group ? ' AND a.customergroupid = '.$group : '')
					.($net ? ' AND EXISTS (SELECT 1 FROM nodes WHERE c.customerid = ownerid AND ((ipaddr > '.$net['address'].' AND ipaddr < '.$net['broadcast'].') OR (ipaddr_pub > '.$net['address'].' AND ipaddr_pub < '.$net['broadcast'].')))' : '')
					.($division ? ' AND EXISTS (SELECT 1 FROM customers WHERE id = c.customerid AND divisionid = '.$division.')' : '')
					.($types ? $typewhere : '')
					.' AND NOT EXISTS (
			        		SELECT 1 FROM customerassignments a
						JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
						WHERE e.userid = lms_current_user() AND a.customerid = c.customerid)'
					, array($date['from']));
		else
			$lastafter = 0;
			
		if($balancelist = $DB->GetAll('SELECT c.id AS id, time, userid, c.value AS value, 
					taxes.label AS taxlabel, c.customerid, comment, c.type AS type
					FROM cash c 
					LEFT JOIN taxes ON (taxid = taxes.id) '
					.($group ? 'LEFT JOIN customerassignments a ON (c.customerid = a.customerid)  ' : '')
					.'WHERE time <= ? '
					.($docs ? ($docs == 'documented' ? ' AND c.docid > 0' : ' AND c.docid = 0') : '')
					.($source ? ' AND c.sourceid = '.intval($source) : '')
					.(isset($date['from']) ? ' AND time >= '.$date['from'] : '')
					.($group ? ' AND a.customergroupid = '.$group : '')
					.($net ? ' AND EXISTS (SELECT 1 FROM nodes WHERE c.customerid = ownerid AND ((ipaddr > '.$net['address'].' AND ipaddr < '.$net['broadcast'].') OR (ipaddr_pub > '.$net['address'].' AND ipaddr_pub < '.$net['broadcast'].')))' : '')
					.($division ? ' AND EXISTS (SELECT 1 FROM customers WHERE id = c.customerid AND divisionid = '.$division.')' : '')
					.($types ? $typewhere : '')
					.' AND NOT EXISTS (
			        		SELECT 1 FROM customerassignments a
						JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
						WHERE e.userid = lms_current_user() AND a.customerid = c.customerid)'
					.' ORDER BY time ASC', array($date['to'])))
		{
			$listdata['income'] = 0;
			$listdata['expense'] = 0;
			$listdata['liability'] = 0;
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

				if($row['customerid'] && $row['type']==0)
	        		{
		                	// customer covenant
				        $list[$x]['after'] = $lastafter;
					$list[$x]['covenant'] = true;
					$listdata['liability'] -= $row['value'];
				}
				else
				{
					//customer payment
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
		
			$listdata['total'] = $listdata['income'] - $listdata['expense'];
		
			$SMARTY->assign('listdata', $listdata);
			$SMARTY->assign('balancelist', $list);
		}

		if($net)
			$SMARTY->assign('net', $net['name']);
		if($types)
			$SMARTY->assign('types', implode(', ', $typetxt));
		if($group)
			$SMARTY->assign('group', $DB->GetOne('SELECT name FROM customergroups WHERE id = ?', array($group)));
		if($division)
			$SMARTY->assign('division', $DB->GetOne('SELECT name FROM divisions WHERE id = ?', array($division)));
		if($source)
			$SMARTY->assign('source', $DB->GetOne('SELECT name FROM cashsources WHERE id = ?', array($source)));

		if (strtolower($CONFIG['phpui']['report_type']) == 'pdf') {
			$output = $SMARTY->fetch('printbalancelist.html');
			html2pdf($output, trans('Reports'), $layout['pagetitle']);
		} else {
			$SMARTY->display('printbalancelist.html');
		}
	break;

	case 'incomereport': /********************************************/

		if (!check_conf('privileges.finances_management'))
			access_denied();

		$from = $_POST['from'];
		$to = $_POST['to'];

		// date format 'yyyy/mm/dd'	
		list($year, $month, $day) = explode('/',$from);
		$date['from'] = mktime(0,0,0, (int)$month, (int)$day, (int)$year);
		
		if($to) {
			list($year, $month, $day) = explode('/',$to);
			$date['to'] = mktime(23,59,59,$month,$day,$year);
		} else {
			$to = date("Y/m/d",time());
			$date['to'] = mktime(23,59,59); // end of today
		}
		
		$layout['pagetitle'] = trans('Total Invoiceless Income ($a to $b)',($from ? $from : ''), $to);
		
		$incomelist = $DB->GetAll('SELECT floor(time/86400)*86400 AS date, SUM(value) AS value
			FROM cash c
			WHERE value>0 AND time>=? AND time<=? AND docid=0
				AND NOT EXISTS (
			        	SELECT 1 FROM customerassignments a
					JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
					WHERE e.userid = lms_current_user() AND a.customerid = c.customerid)
			GROUP BY date ORDER BY date ASC',
			array($date['from'], $date['to']));

		$SMARTY->assign('incomelist', $incomelist);
		if (strtolower($CONFIG['phpui']['report_type']) == 'pdf') {
			$output = $SMARTY->fetch('printincomereport.html');
			html2pdf($output, trans('Reports'), $layout['pagetitle']);
		} else {
			$SMARTY->display('printincomereport.html');
		}
	break;

	case 'importlist': /********************************************/

		if (!check_conf('privileges.finances_management'))
			access_denied();

		$from = $_POST['importfrom'];
		$to = $_POST['importto'];
		$source = $_POST['source'];

		// date format 'yyyy/mm/dd'
		if ($from) {
			list($year, $month, $day) = explode('/',$from);
			$date['from'] = mktime(0,0,0, (int)$month, (int)$day, (int)$year);
		} else {
			$date['from'] = 0;
		}

		if($to) {
			list($year, $month, $day) = explode('/',$to);
			$date['to'] = mktime(23,59,59,$month,$day,$year);
		} else {
			$to = date("Y/m/d",time());
			$date['to'] = mktime(23,59,59); // end of today
		}

		$layout['pagetitle'] = trans('Cash Import History ($a to $b)', $from, $to);

		$importlist = $DB->GetAll('SELECT c.time, c.value, c.customerid, '
			.$DB->Concat('upper(v.lastname)',"' '",'v.name').' AS customername 
			FROM cash c
			JOIN customersview v ON (v.id = c.customerid)
			WHERE c.time >= ? AND c.time <= ?'
			.($source ? ' AND c.sourceid = '.intval($source) : '')
			.' AND c.importid IS NOT NULL
			ORDER BY time', array($date['from'], $date['to']));

		if ($source)
			$SMARTY->assign('source', $DB->GetOne('SELECT name FROM cashsources WHERE id = ?', array($source)));
		$SMARTY->assign('importlist', $importlist);
		if (strtolower($CONFIG['phpui']['report_type']) == 'pdf') {
			$output = $SMARTY->fetch('printimportlist.html');
			html2pdf($output, trans('Reports'), $layout['pagetitle']);
		} else {
			$SMARTY->display('printimportlist.html');
		}
	break;

	case 'invoices': /********************************************/

		if (!check_conf('privileges.finances_management'))
			access_denied();

		$from = $_POST['invoicefrom'];
		$to = $_POST['invoiceto'];

		// date format 'yyyy/mm/dd'
		if($to) {
			list($year, $month, $day) = explode('/',$to);
			$date['to'] = mktime(23,59,59,$month,$day,$year);
		} else { 
			$to = date('Y/m/d',time());
			$date['to'] = mktime(23,59,59); //koniec dnia dzisiejszego
		}

		if($from) {
			list($year, $month, $day) = explode('/',$from);
			$date['from'] = mktime(0,0,0,$month,$day,$year);
		} else { 
			$from = date('Y/m/d',time());
			$date['from'] = mktime(0,0,0); //pocz�tek dnia dzisiejszego
		}

		$type = '';
		$type .= isset($_POST['invoiceorg']) ? '&original=1' : '';
		$type .= isset($_POST['invoicecopy']) ? '&copy=1' : '';
		$type .= isset($_POST['invoicedup']) ? '&duplicate=1' : '';
		if(!$type) $type = '&oryginal=1';
		
		$layout['pagetitle'] = trans('Invoices');
	
		header('Location: ?m=invoice&fetchallinvoices=1'
			.$type
			.'&from='.$date['from']
			.'&to='.$date['to']
			.(!empty($_POST['customer']) ? '&customerid='.intval($_POST['customer']) : '')
			.(!empty($_POST['group']) ? '&groupid='.intval($_POST['group']) : '')
			.(!empty($_POST['numberplan']) ? '&numberplanid='.intval($_POST['numberplan']) : '')
			.(!empty($_POST['groupexclude']) ? '&groupexclude=1' : '')
			.(!empty($_POST['autoissued']) ? '&autoissued=1' : '')
		);
	break;
	
	case 'invoicesproforma': /********************************************/

		if (!check_conf('privileges.finances_management'))
			access_denied();

		$from = $_POST['invoicefrom'];
		$to = $_POST['invoiceto'];

		// date format 'yyyy/mm/dd'
		if($to) {
			list($year, $month, $day) = explode('/',$to);
			$date['to'] = mktime(23,59,59,$month,$day,$year);
		} else { 
			$to = date('Y/m/d',time());
			$date['to'] = mktime(23,59,59); //koniec dnia dzisiejszego
		}

		if($from) {
			list($year, $month, $day) = explode('/',$from);
			$date['from'] = mktime(0,0,0,$month,$day,$year);
		} else { 
			$from = date('Y/m/d',time());
			$date['from'] = mktime(0,0,0); //pocz�tek dnia dzisiejszego
		}

		$type = '';
		$type .= isset($_POST['invoiceorg']) ? '&original=1' : '';
		$type .= isset($_POST['invoicecopy']) ? '&copy=1' : '';
		$type .= isset($_POST['invoicedup']) ? '&duplicate=1' : '';
		if(!$type) $type = '&oryginal=1';
		
		$layout['pagetitle'] = trans('Invoices');
	
		header('Location: ?m=invoice&fetchallinvoicesproforma=1'
			.$type
			.'&from='.$date['from']
			.'&to='.$date['to']
			.(!empty($_POST['customer']) ? '&customerid='.intval($_POST['customer']) : '')
			.(!empty($_POST['group']) ? '&groupid='.intval($_POST['group']) : '')
			.(!empty($_POST['numberplan']) ? '&numberplanid='.intval($_POST['numberplan']) : '')
			.(!empty($_POST['groupexclude']) ? '&groupexclude=1' : '')
			.(!empty($_POST['autoissued']) ? '&autoissued=1' : '')
		);
	break;

	case 'transferforms': /********************************************/

		if (!check_conf('privileges.finances_management'))
			access_denied();

		$from = $_POST['invoicefrom'];
		$to = $_POST['invoiceto'];

		if($to) {
			list($year, $month, $day) = explode('/',$to);
			$date['to'] = mktime(23,59,59,$month,$day,$year);
		} else { 
			$to = date('Y/m/d',time());
			$date['to'] = mktime(23,59,59); //koniec dnia dzisiejszego
		}

		if($from) {
			list($year, $month, $day) = explode('/',$from);
			$date['from'] = mktime(0,0,0,$month,$day,$year);
		} else { 
			$from = date('Y/m/d',time());
			$date['from'] = mktime(0,0,0); //pocz�tek dnia dzisiejszego
		}
		
		$_GET['from'] = $date['from'];
		$_GET['to'] = $date['to'];
		$_GET['customerid'] = $_POST['customer'];
		$_GET['groupid'] = $_POST['group'];
		$_GET['numberplan'] = $_POST['numberplan'];
		$_GET['groupexclude'] = !empty($_POST['groupexclude']) ? 1 : 0;
		$which = '';
		
		require_once(MODULES_DIR.'/transferforms.php');
		
	break;	
	
	case 'transferformsproforma': /********************************************/

		if (!check_conf('privileges.finances_management'))
			access_denied();

		$from = $_POST['invoicefrom'];
		$to = $_POST['invoiceto'];

		if($to) {
			list($year, $month, $day) = explode('/',$to);
			$date['to'] = mktime(23,59,59,$month,$day,$year);
		} else { 
			$to = date('Y/m/d',time());
			$date['to'] = mktime(23,59,59); //koniec dnia dzisiejszego
		}

		if($from) {
			list($year, $month, $day) = explode('/',$from);
			$date['from'] = mktime(0,0,0,$month,$day,$year);
		} else { 
			$from = date('Y/m/d',time());
			$date['from'] = mktime(0,0,0); //pocz�tek dnia dzisiejszego
		}
		
		$_GET['from'] = $date['from'];
		$_GET['to'] = $date['to'];
		$_GET['customerid'] = $_POST['customer'];
		$_GET['groupid'] = $_POST['group'];
		$_GET['numberplan'] = $_POST['numberplan'];
		$_GET['groupexclude'] = !empty($_POST['groupexclude']) ? 1 : 0;
		$which = '';
		require_once(MODULES_DIR.'/transferformsproforma.php');
		
		
	break;	

	case 'transferforms2': /********************************************/

		if (!check_conf('privileges.finances_management'))
			access_denied();

		require_once(MODULES_DIR.'/transferforms2.php');
	break;

	case 'liabilityreport': /********************************************/

		if (!check_conf('privileges.finances_management'))
			access_denied();

		if (isset($_POST['day']) && $_POST['day']) 
		{
			list($year, $month, $day) = explode('/', $_POST['day']);
			$reportday = mktime(0, 0, 0, $month, $day, $year);
			$today = $reportday;
		} else {
			$reportday = time();
			$today = mktime(0, 0, 0);
		}

		$layout['pagetitle'] = trans('Liability Report on $a',date('Y/m/d', $reportday));

		$order = $_POST['order'];
		$direction = $_POST['direction'];
		$customerid = (isset($_POST['customer']) ? intval($_POST['customer']) : 0);

		$year = date('Y', $reportday);
		$yearday = date('z', $reportday) + 1;
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

		if ($month > 6)
			$halfyear = $monthday + ($month - 7) * 100;
		else
			$halfyear = $monthday + ($month - 1) * 100;

		if (is_leap_year($year) && $yearday > 31 + 28)
			$yearday -= 1;

		$suspension_percentage = $CONFIG['finances']['suspension_percentage'];

		if ($taxes = $LMS->GetTaxes($reportday, $reportday))
		{
			foreach($taxes as $taxidx => $tax)
			{
				$list1 = $DB->GetAllByKey('SELECT a.customerid AS id, '.$DB->Concat('UPPER(lastname)',"' '",'c.name').' AS customername, '
					.$DB->Concat('city',"' '",'address').' AS address, ten, 
					SUM((((((100 - a.pdiscount) * t.value) / 100) - a.vdiscount) *
						((CASE a.suspended WHEN 0 THEN 100.0 ELSE '.$suspension_percentage.' END) / 100))
					* (CASE a.period
						WHEN '.YEARLY.' THEN 12
						WHEN '.HALFYEARLY.' THEN 6
						WHEN '.QUARTERLY.' THEN 3
						WHEN '.WEEKLY.' THEN 1.0/4
						WHEN '.DAILY.' THEN 1.0/30
						ELSE 1 END)
					* (CASE t.period
						WHEN '.YEARLY.' THEN 1.0/12
						WHEN '.HALFYEARLY.' THEN 1.0/6
						WHEN '.QUARTERLY.' THEN 1.0/3
						ELSE 1 END)
					) AS value
					FROM assignments a, tariffs t, customersview c
					WHERE a.customerid = c.id AND status = 3 
					AND a.tariffid = t.id AND t.taxid=?
					AND c.deleted=0 
					AND (a.datefrom<=? OR a.datefrom=0) AND (a.dateto>=? OR a.dateto=0) 
					AND ((a.period='.DISPOSABLE.' AND a.at=?)
						OR (a.period='.WEEKLY.'. AND a.at=?)
						OR (a.period='.MONTHLY.' AND a.at=?)
						OR (a.period='.QUARTERLY.' AND a.at=?)
						OR (a.period='.HALFYEARLY.' AND a.at=?)
						OR (a.period='.YEARLY.' AND a.at=?)) '
					.($customerid ? 'AND a.customerid='.$customerid : '').
					' GROUP BY a.customerid, lastname, c.name, city, address, ten ', 'id',
					array($tax['id'], $reportday, $reportday, $today, $weekday, $monthday, $quarterday, $halfyear, $yearday));

				$list2 = $DB->GetAllByKey('SELECT a.customerid AS id, '.$DB->Concat('UPPER(lastname)',"' '",'c.name').' AS customername, '
					.$DB->Concat('city',"' '",'address').' AS address, ten,
					SUM(((((100 - a.pdiscount) * l.value) / 100) - a.vdiscount) *
						((CASE a.suspended WHEN 0 THEN 100.0 ELSE '.$suspension_percentage.' END) / 100)) AS value
					FROM assignments a, liabilities l, customersview c
					WHERE a.customerid = c.id AND status = 3 
					AND a.liabilityid = l.id AND l.taxid=?
					AND c.deleted=0
					AND (a.datefrom<=? OR a.datefrom=0) AND (a.dateto>=? OR a.dateto=0) 
					AND ((a.period='.DISPOSABLE.' AND a.at=?)
						OR (a.period='.WEEKLY.'. AND a.at=?) 
						OR (a.period='.MONTHLY.' AND a.at=?) 
						OR (a.period='.QUARTERLY.' AND a.at=?) 
						OR (a.period='.HALFYEARLY.' AND a.at=?)
						OR (a.period='.YEARLY.' AND a.at=?)) '
					.($customerid ? 'AND a.customerid='.$customerid : ''). 
					' GROUP BY a.customerid, lastname, c.name, city, address, ten ', 'id',
					array($tax['id'], $reportday, $reportday, $today, $weekday, $monthday, $quarterday, $halfyear, $yearday));

				if (empty($list1) && empty($list2)) {
					unset($taxes[$taxidx]);
				}

				$list = array_merge((array) $list1, (array) $list2);

				if ($list)
				{
					foreach($list as $row)
					{
						$idx = $row['id'];
						if (!isset($reportlist[$idx]))
						{ 
							$reportlist[$idx]['id'] = $row['id'];
							$reportlist[$idx]['customername'] = $row['customername'];
							$reportlist[$idx]['address'] = $row['address'];
							$reportlist[$idx]['ten'] = $row['ten'];
						}
						$reportlist[$idx]['value'] += $row['value'];
						$reportlist[$idx][$tax['id']]['netto'] = round($row['value']/($tax['value']+100)*100, 2);
						$reportlist[$idx][$tax['id']]['tax'] = $row['value'] - $reportlist[$idx][$tax['id']]['netto'];
						$reportlist[$idx]['taxsum'] += $reportlist[$idx][$tax['id']]['tax'];
						$total['netto'][$tax['id']] += $reportlist[$idx][$tax['id']]['netto'];
						$total['tax'][$tax['id']] += $reportlist[$idx][$tax['id']]['tax'];
					}

					switch($order)
					{
						case 'customername':
							foreach($reportlist as $idx => $row)
							{
								$table['idx'][] = $idx;
								$table['customername'][] = $row['customername'];
							}
							if (is_array($table))
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
							if (is_array($table))
							{
								array_multisort($table['value'],($direction == 'desc' ? SORT_DESC : SORT_ASC), $table['idx']);
								foreach($table['idx'] as $idx)
									$tmplist[] = $reportlist[$idx];
							}
							$reportlist = $tmplist;
						break;
					}

				}
			}

			$SMARTY->assign('reportlist', $reportlist);
			$SMARTY->assign('total',$total);
			$SMARTY->assign('taxes', $taxes);
			$SMARTY->assign('taxescount', sizeof($taxes));
		}

		if (strtolower($CONFIG['phpui']['report_type']) == 'pdf') {
			$output = $SMARTY->fetch('printliabilityreport.html');
			html2pdf($output, trans('Reports'), $layout['pagetitle']);
		} else {
			$SMARTY->display('printliabilityreport.html');
		}
	break;
	
	case 'receiptlist':

		if (!check_conf('privileges.cash_operations'))
			access_denied();

		if($_POST['from'])
		{
			list($year, $month, $day) = explode('/', $_POST['from']);
			$from = mktime(0,0,0, $month, $day, $year);
		}
		else
			$from = mktime(0,0,0, date('m'), date('d'), date('Y'));
		
		if($_POST['to'])
		{
			list($year, $month, $day) = explode('/', $_POST['to']);
			$to = mktime(23,59,59, $month, $day, $year);
		}
		else
			$to = mktime(23,59,59, date('m'), date('d'), date('Y'));

		$registry = intval($_POST['registry']);
		$user = intval($_POST['user']);
		$group = intval($_POST['group']);
		$where = '';
		
		if($registry)
			$where .= ' AND regid = '.$registry;
		if($from)
			$where .= ' AND cdate >= '.$from;
		if($to)
			$where .= ' AND cdate <= '.$to;
		if($user)
			$where .= ' AND userid = '.$user;
		if($group)
		{
		        $groupwhere = ' AND '.(isset($_POST['groupexclude']) ? 'NOT' : '').'
			            EXISTS (SELECT 1 FROM customerassignments a
				            WHERE a.customergroupid = '.$group.'
					    AND a.customerid = d.customerid)';
			$where .= $groupwhere;
		}

		if($from > 0)
			$listdata['startbalance'] = $DB->GetOne('SELECT SUM(value) FROM receiptcontents
						LEFT JOIN documents d ON (docid = d.id AND type = ?) 
						WHERE cdate < ?'
						.($registry ? ' AND regid='.$registry : '')
						.($user ? ' AND userid='.$user : '')
						.($group ? $groupwhere : '')
						.' AND NOT EXISTS (
						        SELECT 1 FROM customerassignments a
							JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
							WHERE e.userid = lms_current_user() AND a.customerid = d.customerid)',
						array(DOC_RECEIPT, $from));

		$listdata['totalincome'] = 0;
		$listdata['totalexpense'] = 0;
		$listdata['advances'] = 0;
			
		if($list = $DB->GetAll(
	    		'SELECT d.id AS id, SUM(value) AS value, number, cdate, customerid, 
				d.name, address, zip, city, template, extnumber, closed, 
				MIN(description) AS title, COUNT(*) AS posnumber 
			FROM documents d
			LEFT JOIN numberplans ON (numberplanid = numberplans.id)
			LEFT JOIN receiptcontents ON (d.id = docid)
			WHERE d.type = ?'
			.$where.'
				AND NOT EXISTS (
					SELECT 1 FROM customerassignments a
					JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
					WHERE e.userid = lms_current_user() AND a.customerid = d.customerid)
			GROUP BY d.id, number, cdate, customerid, d.name, address, zip, city, template, extnumber, closed
			ORDER BY cdate, d.id', array(DOC_RECEIPT)))
		{
			foreach($list as $idx => $row)
			{
				$list[$idx]['number'] = docnumber($row['number'], $row['template'], $row['cdate'], $row['extnumber']);
				$list[$idx]['customer'] = $row['name'].' '.$row['address'].' '.$row['zip'].' '.$row['city'];

				if($row['posnumber'] > 1) 
					$list[$idx]['title'] = $DB->GetCol('SELECT description FROM receiptcontents WHERE docid=? ORDER BY itemid', array($list[$idx]['id']));

				// summary
				if($row['value'] > 0)
					$listdata['totalincome'] += $row['value'];
				else
					$listdata['totalexpense'] += -$row['value'];

				if($idx==0) 
					$list[$idx]['after'] = $listdata['startbalance'] + $row['value'];
				else
					$list[$idx]['after'] = $list[$idx-1]['after'] + $row['value'];

				if(!$row['closed'])
					$listdata['advances'] -= $row['value'];
			}
		}

		$listdata['endbalance'] = $listdata['startbalance'] + $listdata['totalincome'] - $listdata['totalexpense'];

		$from = date('Y/m/d', $from);
		$to = date('Y/m/d', $to);

		if($from == $to)
			$period = $from;
		else
			$period = $from.' - '.$to;

		$layout['pagetitle'] = trans('Cash Report').' '.$period;
		
		if($registry)
			$layout['registry'] = trans('Registry: $a', ($registry ? $DB->GetOne('SELECT name FROM cashregs WHERE id=?', array($registry)) : trans('all')));
		if($user)
			$layout['username'] = trans('Cashier: $a', $DB->GetOne('SELECT name FROM users WHERE id=?', array($user)));
		if($group)
		{
			$groupname = $DB->GetOne('SELECT name FROM customergroups WHERE id=?', array($group));
	
			if(isset($_POST['groupexclude']))
				$layout['group'] = trans('Group: all excluding $a', $groupname);
			else	
				$layout['group'] = trans('Group: $a', $groupname);
		}
		$SMARTY->assign('receiptlist', $list);
		$SMARTY->assign('listdata', $listdata);

		if(isset($_POST['extended']))
		{
		        $pages = array();
			$totals = array();

			// hidden option: max records count for one page of printout
			// I think 20 records is fine, but someone needs 19.
			$rows = isset($CONFIG['phpui']['printout_pagelimit']) ? $CONFIG['phpui']['printout_pagelimit'] : 20;

			// create a new array and do some calculations 
			// (summaries and page size calculations)
			$maxrows = $rows * 2;	// dwie linie na rekord
			$counter = $maxrows;
			$rows = 0;		// rzeczywista liczba rekord�w na stronie
			$i = 1;
			$x = 1;

			foreach($list as $row)
			{
				// tutaj musimy troch� pokombinowa�, bo liczba
				// rekord�w na stronie b�dzie zmienna
				$tmp = is_array($row['title']) ? sizeof($row['title']) : 2;
				$counter -= max($tmp,2);
				if($counter<0)
				{
					$x++;
					$rows = 0;
					$counter = $maxrows;
				}

				$rows++;
				$page = $x;

				if($row['value']>0)
					$totals[$page]['income'] += $row['value'];
				else
					$totals[$page]['expense'] += -$row['value'];

				$totals[$page]['rows'] = $rows; 
			}

			foreach($totals as $page => $t)
			{
				$pages[] = $page;

				$totals[$page]['totalincome'] = $totals[$page-1]['totalincome'] + $t['income'];
				$totals[$page]['totalexpense'] = $totals[$page-1]['totalexpense'] + $t['expense'];
				$totals[$page]['rowstart'] = $totals[$page-1]['rowstart'] + $totals[$page-1]['rows'];
			}

			$SMARTY->assign('pages', $pages);
			$SMARTY->assign('totals', $totals);
			$SMARTY->assign('pagescount', sizeof($pages));
			$SMARTY->assign('reccount', sizeof($list));
			if (strtolower($CONFIG['phpui']['report_type']) == 'pdf') {
				$output = $SMARTY->fetch('printreceiptlist-ext.html');
				html2pdf($output, trans('Reports'), $layout['pagetitle']);
			} else {
				$SMARTY->display('printreceiptlist-ext.html');
			}
		}
		else
		{
			if (strtolower($CONFIG['phpui']['report_type']) == 'pdf') {
				$output = $SMARTY->fetch('printreceiptlist.html');
				html2pdf($output, trans('Reports'), $layout['pagetitle']);
			} else {
				$SMARTY->display('printreceiptlist.html');
			}
		}
	break;

	default: /*******************************************************/

		$layout['pagetitle'] = trans('Reports');

		if(!isset($CONFIG['phpui']['big_networks']) || !chkconfig($CONFIG['phpui']['big_networks']))
		{
			$SMARTY->assign('customers', $LMS->GetCustomerNames());
		}
		$SMARTY->assign('users', $LMS->GetUserNames());
		$SMARTY->assign('networks', $LMS->GetNetworks());
		$SMARTY->assign('customergroups', $LMS->CustomergroupGetAll());
		$SMARTY->assign('numberplans', $LMS->GetNumberPlans(array(DOC_INVOICE, DOC_CNOTE)));
		$SMARTY->assign('numberplansproforma', $LMS->GetNumberPlans(array(DOC_INVOICE_PRO)));
		$SMARTY->assign('cashreglist', $DB->GetAllByKey('SELECT id, name FROM cashregs ORDER BY name', 'id'));
		$SMARTY->assign('divisions', $DB->GetAll('SELECT id, shortname FROM divisions ORDER BY shortname'));
		$SMARTY->assign('sourcelist', $DB->GetAll('SELECT id, name FROM cashsources ORDER BY name'));
		$SMARTY->assign('printmenu', 'finances');
		$SMARTY->display('printindex.html');
	break;
}

?>
