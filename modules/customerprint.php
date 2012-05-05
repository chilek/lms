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

if (!check_conf('privileges.reports'))
	access_denied();

$type = isset($_GET['type']) ? $_GET['type'] : '';

switch($type)
{
	case 'customerlist':

		$date = 0;
		$search['type'] = $_POST['type'];
		$search['linktype'] = $_POST['linktype'];

		if($_POST['day'])
		{
			list($year, $month, $day) = explode('/', $_POST['day']);
			$date = mktime(0,0,0,$month,$day+1,$year);
		}

		if($_POST['docfrom'])
		{
			list($year, $month, $day) = explode('/', $_POST['docfrom']);
			$docfrom = mktime(0,0,0,$month,$day,$year);
		}
		else
			$docfrom = 0;

		if($_POST['docto'])
		{
			list($year, $month, $day) = explode('/', $_POST['docto']);
			$docto = mktime(23,59,59,$month,$day,$year);
		}
		else
			$docto = 0;

		if(!empty($_POST['doctype']) || !empty($docfrom) || !empty($docto))
		{
			$search['doctype'] = intval($_POST['doctype']).':'.$docfrom.':'.$docto;
		}
		if(!empty($_POST['stateid']))
		{
			$search['stateid'] = intval($_POST['stateid']);
		}

		switch($_POST['filter'])
		{
			case 0:
				$layout['pagetitle'] = trans('Customers List $a$b',($_POST['network'] ? trans(' (Net: $a)',$LMS->GetNetworkName($_POST['network'])) : ''),($_POST['customergroup'] ? trans('(Group: $a)',$LMS->CustomergroupGetName($_POST['customergroup'])) : ''));
				$SMARTY->assign('customerlist', $LMS->GetCustomerList($_POST['order'].','.$_POST['direction'], $_POST['filter'], $_POST['network'], $_POST['customergroup'], $search, $date, 'AND', $_POST['nodegroup']));
			break;
			case 1:
				$layout['pagetitle'] = trans('Interested Customers List $a', ($_POST['customergroup'] ? trans('(Group: $a)', $LMS->CustomergroupGetName($_POST['customergroup'])) : ''));
				$SMARTY->assign('customerlist', $LMS->GetCustomerList($_POST['order'].','.$_POST['direction'], $_POST['filter'], NULL, $_POST['customergroup'], $search, $date));
			break;
			case 2:
				$layout['pagetitle'] = trans('List of awaiting customers $a', ($_POST['customergroup'] ? trans('(Group: $a)', $LMS->CustomergroupGetName($_POST['customergroup'])) : ''));
				$SMARTY->assign('customerlist', $LMS->GetCustomerList($_POST['order'].','.$_POST['direction'], $_POST['filter'], NULL, $_POST['customergroup'], $search, $date));
			break;
			case 3:
				$layout['pagetitle'] = trans('List of Connected Customers $a$b',($_POST['network'] ? trans(' (Net: $a)',$LMS->GetNetworkName($_POST['network'])) : ''),($_POST['customergroup'] ? trans('(Group: $a)',$LMS->CustomergroupGetName($_POST['customergroup'])) : '')); 
				$SMARTY->assign('customerlist', $LMS->GetCustomerList($_POST['order'].','.$_POST['direction'], $_POST['filter'], $_POST['network'], $_POST['customergroup'], $search, $date, 'AND', $_POST['nodegroup']));
			break;
			case 5:
				$layout['pagetitle'] = trans('List of Disconnected Customers $a$b',($_POST['network'] ? trans(' (Net: $a)',$LMS->GetNetworkName($_POST['network'])) : ''),($_POST['customergroup'] ? trans('(Group: $a)',$LMS->CustomergroupGetName($_POST['customergroup'])) : ''));
				$SMARTY->assign('customerlist', $LMS->GetCustomerList($_POST['order'].','.$_POST['direction'], $_POST['filter'], $_POST['network'], $_POST['customergroup'], $search, $date, 'AND', $_POST['nodegroup']));
			break;
			case 6:
			case 11:
			case 12:
				$layout['pagetitle'] = trans('Indebted Customers List $a$b',($_POST['network'] ? trans(' (Net: $a)',$LMS->GetNetworkName($_POST['network'])) : ''),($_POST['customergroup'] ? trans('(Group: $a)',$LMS->CustomergroupGetName($_POST['customergroup'])) : ''));
				$SMARTY->assign('customerlist', $LMS->GetCustomerList($_POST['order'].','.$_POST['direction'], $_POST['filter'], $_POST['network'], $_POST['customergroup'], $search, $date, 'AND', $_POST['nodegroup']));
			break;
			case -1:
				$layout['pagetitle'] = trans('Customers Without Nodes List $a$b',($_POST['network'] ? trans(' (Net: $a)',$LMS->GetNetworkName($_POST['network'])) : ''),($_POST['customergroup'] ? trans('(Group: $a)',$LMS->CustomergroupGetName($_POST['customergroup'])) : ''));
				if($customerlist = $LMS->GetCustomerList($_POST['order'].','.$_POST['direction'], NULL, NULL, $_POST['customergroup'], $search, $date))
				{
					unset($customerlist['total']);
					unset($customerlist['state']);
					unset($customerlist['order']);
					unset($customerlist['below']);
					unset($customerlist['over']);
					unset($customerlist['direction']);

					foreach($customerlist as $idx => $row)
						if(! $row['account'])
							$ncustomerlist[] = $customerlist[$idx];
				}
				$SMARTY->assign('customerlist', $ncustomerlist);
			break;
		}

		$SMARTY->assign('contactlist', $DB->GetAllByKey('SELECT customerid, MIN(phone) AS phone
						FROM customercontacts WHERE phone != \'\' GROUP BY customerid', 'customerid'));

		if (strtolower($CONFIG['phpui']['report_type']) == 'pdf') {
			$output = $SMARTY->fetch('printcustomerlist.html');
			html2pdf($output, trans('Reports'), $layout['pagetitle']);
		} else {
			$SMARTY->display('printcustomerlist.html');
		}
	break;

	case 'customerbalance': /********************************************/

		$from = $_POST['from'];
		$to = $_POST['to'];

		// date format 'yyyy/mm/dd'	
		list($year, $month, $day) = explode('/',$from);
		$date['from'] = mktime(0,0,0,$month,$day,$year);

		if($to) {
			list($year, $month, $day) = explode('/',$to);
			$date['to'] = mktime(23,59,59,$month,$day,$year);
		} else {
			$to = date('Y/m/d',time());
			$date['to'] = mktime(23,59,59); //koniec dnia dzisiejszego
		}

		$layout['pagetitle'] = trans('Customer $a Balance Sheet ($b to $c)',
			$LMS->GetCustomerName($_POST['customer']), ($from ? $from : ''), $to);

		$id = $_POST['customer'];

		if($tslist = $DB->GetAll('SELECT cash.id AS id, time, cash.value AS value,
			taxes.label AS taxlabel, customerid, comment, name AS username 
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
		if (strtolower($CONFIG['phpui']['report_type']) == 'pdf') {
			$output = $SMARTY->fetch('printcustomerbalance.html');
			html2pdf($output, trans('Reports'), $layout['pagetitle']);
		} else {
			$SMARTY->display('printcustomerbalance.html');
		}
	break;

	default: /*******************************************************/

		$layout['pagetitle'] = trans('Reports');

		$yearstart = date('Y', (int) $DB->GetOne('SELECT MIN(dt) FROM stats'));
		$yearend = date('Y', (int) $DB->GetOne('SELECT MAX(dt) FROM stats'));
		for($i=$yearstart; $i<$yearend+1; $i++)
			$statyears[] = $i;
		for($i=1; $i<13; $i++)
			$months[$i] = strftime('%B', mktime(0,0,0,$i,1));

		if(!isset($CONFIG['phpui']['big_networks']) || !chkconfig($CONFIG['phpui']['big_networks']))
		{
			$SMARTY->assign('customers', $LMS->GetCustomerNames());
		}
		$SMARTY->assign('currmonth', date('n'));
		$SMARTY->assign('curryear', date('Y'));
		$SMARTY->assign('statyears', $statyears);
		$SMARTY->assign('months', $months);
		$SMARTY->assign('networks', $LMS->GetNetworks());
		$SMARTY->assign('customergroups', $LMS->CustomergroupGetAll());
		$SMARTY->assign('nodegroups', $LMS->GetNodeGroupNames());
		$SMARTY->assign('cstateslist', $LMS->GetCountryStates());
		$SMARTY->assign('printmenu', 'customer');
		$SMARTY->display('printindex.html');
	break;
}

?>
