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

if(! $LMS->CustomerExists($_GET['id']))
{
	$SESSION->redirect('?m=customerlist');
}

if($_GET['action'] == 'delete')
{
	$LMS->DeleteAssignment($_GET['aid']);
	$SESSION->redirect('?'.$SESSION->get('backto'));
}

if($_GET['action'] == 'suspend')
{
	$LMS->SuspendAssignment($_GET['aid'], $_GET['suspend']);
	$SESSION->redirect('?'.$SESSION->get('backto'));
}

$a = $_POST['assignment'];

if($_GET['action'] == 'add' && isset($a))
{
	foreach($a as $key => $val)
		$a[$key] = trim($val);
	
	$period = sprintf('%d',$a['period']);

	if($period < DISPOSABLE || $period > YEARLY)
		$period = DISPOSABLE;

	switch($period)
	{
		case DISPOSABLE:
			$a['dateto'] = 0;
			$a['datefrom'] = 0;
			
			if(eregi('^[0-9]{4}/[0-9]{2}/[0-9]{2}$', $a['at']))
			{
				list($y, $m, $d) = split('/', $a['at']);
				if(checkdate($m, $d, $y))
				{
					$at = mktime(0, 0, 0, $m, $d, $y);
					
					if($at < mktime(0,0,0))
						$error['at'] = trans('Incorrect date!');
				}
				else
					$error['at'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
			}
			else
				$error['at'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
		break;
		
		case DAILY:
			$at = 0;
		break;
		
		case WEEKLY:
			$at = sprintf('%d',$a['at']);
			
			if($CONFIG['phpui']['use_current_payday'] && $at==0)
			{
				$at = strftime('%u', time());
			}
			
			if($at < 1 || $at > 7)
				$error['at'] = trans('Incorrect day of week (1-7)!');
		break;

		case MONTHLY:
			$at = sprintf('%d',$a['at']);
			
			if($CONFIG['phpui']['use_current_payday'] && $at==0)
				$at = date('j', time());

			$a['at'] = $at;
			
			if($at > 28 || $at < 1)
				$error['at'] = trans('Incorrect day of month (1-28)!');
		break;

		case QUARTERLY:
			if(!eregi('^[0-9]{2}/[0-9]{2}$',$a['at']) && $a['at'])
			{
				$error['at'] = trans('Incorrect date format! Enter date in DD/MM format!');
			}
			elseif($CONFIG['phpui']['use_current_payday'] && !$a['at'])
			{
				$d = date('j', time());
				$m = date('n', time());
				$a['at'] = $d.'/'.$m;
			}
			else
			{
				list($d,$m) = split('/',$a['at']);
			}
			
			if(!$error)
			{
				if($d>30 || $d<1 || ($d>28 && $m==2))
					$error['at'] = trans('This month doesn\'t contain specified number of days');
				if($m>3 || $m<1)
					$error['at'] = trans('Incorrect month number (max.3)!');

				$at = ($m-1) * 100 + $d;
			}
		break;

		case YEARLY:
			if(!eregi('^[0-9]{2}/[0-9]{2}$',$a['at']) && $a['at'])
			{
				$error['at'] = trans('Incorrect date format! Enter date in DD/MM format!');
			}
			elseif($CONFIG['phpui']['use_current_payday'] && !$a['at'])
			{
				$d = date('j', time());
				$m = date('n', time());
				$a['at'] = $d.'/'.$m;
			}
			else
			{
				list($d,$m) = split('/',$a['at']);
			}
			
			if(!$error)
			{
				if($d>30 || $d<1 || ($d>28 && $m==2))
					$error['at'] = trans('This month doesn\'t contain specified number of days');
				if($m>12 || $m<1)
					$error['at'] = trans('Incorrect month number');
			
				$ttime = mktime(12, 0, 0, $m, $d, 1990);
				$at = date('z',$ttime) + 1;
			}
		break;
	}

	if($a['datefrom'] == '')
		$from = 0;
	elseif(eregi('^[0-9]{4}/[0-9]{2}/[0-9]{2}$',$a['datefrom']))
	{
		list($y, $m, $d) = split('/', $a['datefrom']);
		if(checkdate($m, $d, $y))
			$from = mktime(0, 0, 0, $m, $d, $y);
		else
			$error['datefrom'] = trans('Incorrect charging time!');
	}
	else
		$error['datefrom'] = trans('Incorrect charging time!');

	if($a['dateto'] == '')
		$to = 0;
	elseif(eregi('^[0-9]{4}/[0-9]{2}/[0-9]{2}$',$a['dateto']))
	{
		list($y, $m, $d) = split('/', $a['dateto']);
		if(checkdate($m, $d, $y))
			$to = mktime(23, 59, 59, $m, $d, $y);
		else
			$error['dateto'] = trans('Incorrect charging time!');
	}
	else
		$error['dateto'] = trans('Incorrect charging time!');

	if($to < $from && $to != 0 && $from != 0)
		$error['dateto'] = trans('Incorrect date range!');

	if($a['tariffid']=='' && $a['value']=='')
	{
		$error['tariffid'] = trans('Subscription not selected!');
		$error['value'] = trans('Liability value not specified!');
	}

	$a['discount'] = str_replace(',','.',$a['discount']);
	if($a['discount']=='')
		$a['discount'] = 0;
	elseif($a['discount']<0 || $a['discount']>99.99 || !is_numeric($a['discount']))
		$error['discount'] = trans('Wrong discount value!');

	if($a['tariffid'] == '0')
	{
		unset($error['at']);
		$at = 0;
	}

	if($a['tariffid'] != '') 
		$a['value'] = 0;
	

	if(!$error) 
	{
		if($a['tariffid'] == '')
		{
			$a['tariffid'] = 0;
		}

		$LMS->AddAssignment(array('tariffid' => $a['tariffid'], 
					    'customerid' => $_GET['id'], 
					    'period' => $period, 
					    'at' => $at, 
					    'invoice' => sprintf('%d',$a['invoice']), 
					    'datefrom' => $from, 
					    'dateto' => $to, 
					    'discount' => $a['discount'],
					    'value' => str_replace(',','.',$a['value']),
					    'name' => $a['name'],
					    'taxid' => $a['taxid'],
					    'prodid' => $a['prodid']
					    ));
		$SESSION->redirect('?'.$SESSION->get('backto'));
	}
}

$customerinfo = $LMS->GetCustomer($_GET['id']);

$layout['pagetitle'] = trans('Customer Information: $0',$customerinfo['customername']);

$SMARTY->assign('customernodes',$LMS->GetCustomerNodes($customerinfo['id']));
$SMARTY->assign('balancelist',$LMS->GetCustomerBalanceList($customerinfo['id']));
$SMARTY->assign('tariffs',$LMS->GetTariffs());
$SMARTY->assign('taxeslist',$LMS->GetTaxes());
$SMARTY->assign('assignments',$LMS->GetCustomerAssignments($_GET['id']));
$SMARTY->assign('customergroups',$LMS->CustomergroupGetForCustomer($_GET['id']));
$SMARTY->assign('othercustomergroups',$LMS->GetGroupNamesWithoutCustomer($_GET['id']));
$SMARTY->assign('customerinfo',$customerinfo);
$SMARTY->assign('recover',($_GET['action'] == 'recover' ? 1 : 0));
$SMARTY->assign('error', $error);
$SMARTY->assign('assignment', $a);
$SMARTY->display('customerinfo.html');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

?>
