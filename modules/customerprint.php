<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

if (!ConfigHelper::checkConfig('privileges.superuser') && !ConfigHelper::checkConfig('privileges.reports'))
	access_denied();

$type = isset($_GET['type']) ? $_GET['type'] : '';

switch($type)
{
	case 'customerlist':

		$time = 0;
		$search['type'] = $_POST['type'];
		$search['linktype'] = $_POST['linktype'];

		if (isset($_POST['division']))
			$division = intval($_POST['division']);
		else
			$division = NULL;

		if($_POST['day'])
		{
			list($year, $month, $day) = explode('/', $_POST['day']);
			$time = mktime(0,0,0,$month,$day+1,$year);
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

                $order = $_POST['order'].','.$_POST['direction'];
                $state = $_POST['filter'];
                $network = $_POST['network'];
                $customergroup = $_POST['customergroup'];
                $sqlskey = 'AND';
                $nodegroup = $_POST['nodegroup'];

		switch($state)
		{
			case 0:
				$layout['pagetitle'] = trans('Customers List $a$b',($_POST['network'] ? trans(' (Net: $a)',$LMS->GetNetworkName($_POST['network'])) : ''),($_POST['customergroup'] ? trans('(Group: $a)',$LMS->CustomergroupGetName($_POST['customergroup'])) : ''));
				$SMARTY->assign('customerlist', $LMS->GetCustomerList(compact("order", "state", "network", "customergroup", "search", "time", "sqlskey", "nodegroup", "division")));
			break;
			case CSTATUS_INTERESTED:
				$layout['pagetitle'] = trans('Interested Customers List $a', ($_POST['customergroup'] ? trans('(Group: $a)', $LMS->CustomergroupGetName($_POST['customergroup'])) : ''));
				$SMARTY->assign('customerlist', $LMS->GetCustomerList(compact("order", "state", "network", "customergroup", "search", "time", "sqlskey", "nodegroup", "division")));
			break;
			case CSTATUS_WAITING:
				$layout['pagetitle'] = trans('List of awaiting customers $a', ($_POST['customergroup'] ? trans('(Group: $a)', $LMS->CustomergroupGetName($_POST['customergroup'])) : ''));
				$SMARTY->assign('customerlist', $LMS->GetCustomerList(compact("order", "state", "network", "customergroup", "search", "time", "sqlskey", "nodegroup", "division")));
			break;
			case CSTATUS_DISCONNECTED:
				$layout['pagetitle'] = trans('List of Disconnected customers $a$b', ($_POST['network'] ? trans(' (Net: $a)',$LMS->GetNetworkName($_POST['network'])) : ''), ($_POST['customergroup'] ? trans('(Group: $a)', $LMS->CustomergroupGetName($_POST['customergroup'])) : ''));
				$SMARTY->assign('customerlist', $LMS->GetCustomerList(compact("order", "state", "network", "customergroup", "search", "time", "sqlskey", "nodegroup", "division")));
			break;
			case CSTATUS_CONNECTED:
				$layout['pagetitle'] = trans('List of Connected Customers $a$b',($_POST['network'] ? trans(' (Net: $a)',$LMS->GetNetworkName($_POST['network'])) : ''),($_POST['customergroup'] ? trans('(Group: $a)',$LMS->CustomergroupGetName($_POST['customergroup'])) : '')); 
				$SMARTY->assign('customerlist', $LMS->GetCustomerList(compact("order", "state", "network", "customergroup", "search", "time", "sqlskey", "nodegroup", "division")));
			break;
			case 51:
				$layout['pagetitle'] = trans('List of Disconnected Customers $a$b',($_POST['network'] ? trans(' (Net: $a)',$LMS->GetNetworkName($_POST['network'])) : ''),($_POST['customergroup'] ? trans('(Group: $a)',$LMS->CustomergroupGetName($_POST['customergroup'])) : ''));
				$SMARTY->assign('customerlist', $LMS->GetCustomerList(compact("order", "state", "network", "customergroup", "search", "time", "sqlskey", "nodegroup", "division")));
			break;
			case 52:
			case 57:
			case 58:
				$layout['pagetitle'] = trans('Indebted Customers List $a$b',($_POST['network'] ? trans(' (Net: $a)',$LMS->GetNetworkName($_POST['network'])) : ''),($_POST['customergroup'] ? trans('(Group: $a)',$LMS->CustomergroupGetName($_POST['customergroup'])) : ''));
				$SMARTY->assign('customerlist', $LMS->GetCustomerList(compact("order", "state", "network", "customergroup", "search", "time", "sqlskey", "nodegroup", "division")));
			break;
			case 59:
				$layout['pagetitle'] = trans('Customers Without Contracts List $a$b',($_POST['network'] ? trans(' (Net: $a)',$LMS->GetNetworkName($_POST['network'])) : ''),($_POST['customergroup'] ? trans('(Group: $a)',$LMS->CustomergroupGetName($_POST['customergroup'])) : ''));
				$SMARTY->assign('customerlist', $LMS->GetCustomerList(compact("order", "state", "network", "customergroup", "search", "time", "sqlskey", "nodegroup", "division")));
			break;
			case 60:
				$layout['pagetitle'] = trans('Customers With Expired Contracts List $a$b',($_POST['network'] ? trans(' (Net: $a)',$LMS->GetNetworkName($_POST['network'])) : ''),($_POST['customergroup'] ? trans('(Group: $a)',$LMS->CustomergroupGetName($_POST['customergroup'])) : ''));
				$SMARTY->assign('customerlist', $LMS->GetCustomerList(compact("order", "state", "network", "customergroup", "search", "time", "sqlskey", "nodegroup", "division")));
			break;
			case 61:
				$layout['pagetitle'] = trans('Customers With Expiring Contracts List $a$b',($_POST['network'] ? trans(' (Net: $a)',$LMS->GetNetworkName($_POST['network'])) : ''),($_POST['customergroup'] ? trans('(Group: $a)',$LMS->CustomergroupGetName($_POST['customergroup'])) : ''));
				$SMARTY->assign('customerlist', $LMS->GetCustomerList(compact("order", "state", "network", "customergroup", "search", "time", "sqlskey", "nodegroup", "division")));
			break;
			case -1:
				$layout['pagetitle'] = trans('Customers Without Nodes List $a$b',($_POST['network'] ? trans(' (Net: $a)',$LMS->GetNetworkName($_POST['network'])) : ''),($_POST['customergroup'] ? trans('(Group: $a)',$LMS->CustomergroupGetName($_POST['customergroup'])) : ''));
				$SMARTY->assign('customerlist', $LMS->GetCustomerList(compact("order", "state", "network", "customergroup", "search", "time", "sqlskey", "nodegroup", "division")));
				if($customerlist = $LMS->GetCustomerList(compact("order", "customergroup", "search", "time", "sqlskey", "division")))
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

		$SMARTY->assign('contactlist', $DB->GetAllByKey('SELECT customerid, MIN(contact) AS phone
				FROM customercontacts WHERE contact <> \'\' AND type & 7 > 0 GROUP BY customerid',
				'customerid', array()));

		if (strtolower(ConfigHelper::getConfig('phpui.report_type')) == 'pdf') {
			$output = $SMARTY->fetch('print/printcustomerlist.html');
			html2pdf($output, trans('Reports'), $layout['pagetitle']);
		} else {
			$SMARTY->display('print/printcustomerlist.html');
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
		if (strtolower(ConfigHelper::getConfig('phpui.report_type')) == 'pdf') {
			$output = $SMARTY->fetch('print/printcustomerbalance.html');
			html2pdf($output, trans('Reports'), $layout['pagetitle']);
		} else {
			$SMARTY->display('print/printcustomerbalance.html');
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

		if (!ConfigHelper::checkConfig('phpui.big_networks'))
			$SMARTY->assign('customers', $LMS->GetCustomerNames());
		$SMARTY->assign('currmonth', date('n'));
		$SMARTY->assign('curryear', date('Y'));
		$SMARTY->assign('statyears', $statyears);
		$SMARTY->assign('months', $months);
		$SMARTY->assign('networks', $LMS->GetNetworks());
		$SMARTY->assign('customergroups', $LMS->CustomergroupGetAll());
		$SMARTY->assign('nodegroups', $LMS->GetNodeGroupNames());
		$SMARTY->assign('cstateslist', $LMS->GetCountryStates());
		$SMARTY->assign('divisions', $DB->GetAll('SELECT id, shortname FROM divisions ORDER BY shortname'));
		$SMARTY->assign('printmenu', 'customer');
		$SMARTY->display('print/printindex.html');
	break;
}

?>
