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

$customerid = $DB->GetOne('SELECT customerid FROM assignments WHERE id=?', array($_GET['id']));

if(!$customerid)
{
	$SESSION->redirect('?'.$SESSION->get('backto'));
}

if($a = $_POST['assignmentedit'])
{
	foreach($a as $key => $val)
		$a[$key] = trim($val);
	
	$a['id'] = $_GET['id'];
	$a['customerid'] = $customerid;
	$a['liabilityid'] = $_GET['lid'];

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
						$error['editat'] = trans('Incorrect date!');
				}
				else
					$error['editat'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
			}
			else
				$error['editat'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
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
				$error['editat'] = trans('Incorrect day of week (1-7)!');
		break;

		case MONTHLY:
			$at = sprintf('%d',$a['at']);
			
			if($CONFIG['phpui']['use_current_payday'] && $at==0)
				$at = date('j', time());

			$a['at'] = $at;
			
			if($at > 28 || $at < 1)
				$error['editat'] = trans('Incorrect day of month (1-28)!');
		break;

		case QUARTERLY:
			if(!eregi('^[0-9]{2}/[0-9]{2}$',$a['at']) && $a['at'])
			{
				$error['editat'] = trans('Incorrect date format! Enter date in DD/MM format!');
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
					$error['editat'] = trans('This month doesn\'t contain specified number of days');
				if($m>3 || $m<1)
					$error['editat'] = trans('Incorrect month number (max.3)!');

				$at = ($m-1) * 100 + $d;
			}
		break;

		case YEARLY:
			if(!eregi('^[0-9]{2}/[0-9]{2}$',$a['at']) && $a['at'])
			{
				$error['editat'] = trans('Incorrect date format! Enter date in DD/MM format!');
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
					$error['editat'] = trans('This month doesn\'t contain specified number of days');
				if($m>12 || $m<1)
					$error['editat'] = trans('Incorrect month number');
			
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
			$error['editdatefrom'] = trans('Incorrect charging start time!');
	}
	else
		$error['editdatefrom'] = trans('Incorrect charging start time!');

	if($a['dateto'] == '')
		$to = 0;
	elseif(eregi('^[0-9]{4}/[0-9]{2}/[0-9]{2}$',$a['dateto']))
	{
		list($y, $m, $d) = split('/', $a['dateto']);
		if(checkdate($m, $d, $y))
			$to = mktime(23, 59, 59, $m, $d, $y);
		else
			$error['editdateto'] = trans('Incorrect charging end time!');
	}
	else
		$error['editdateto'] = trans('Incorrect charging end time!');

	if($to < $from && $to != 0 && $from != 0)
		$error['editdateto'] = trans('Incorrect date range!');

	if($a['tariffid']=='0')
	{
		unset($error['editat']);
		$at = 0;
	}

	$a['discount'] = str_replace(',','.',$a['discount']);
	if($a['discount'] == '')
		$a['discount'] = 0;
	elseif($a['discount']<0 || $a['discount']>99.99 || !is_numeric($a['discount']))
		$error['editdiscount'] = trans('Wrong discount value!');

        if($a['tariffid'] != '')
    		$a['value'] = 0;

	if($_GET['lid'])
	{
		if($a['name'] == '')
			$error['editname'] = trans('Liability name/description is required!');
		if(!ereg('^[-]?[0-9.,]+$', $a['value']))
			$error['editvalue'] = trans('Incorrect value!');
	}

	if(!$error) 
	{
	        if($_GET['lid'])
		{
			$a['tariffid'] = 0;
			$DB->Execute('UPDATE liabilities SET value=?, name=?, taxid=?, prodid=? WHERE id=?',
			    array(str_replace(',','.',$a['value']),
				    $a['name'],
				    $a['taxid'],
				    $a['prodid'],
				    $_GET['lid']
				    ));
		}
										
		$DB->Execute('UPDATE assignments SET tariffid=?, customerid=?, period=?, at=?, invoice=?, datefrom=?, dateto=?, discount=? WHERE id=?',
			    array(  $a['tariffid'], 
				    $customerid, 
				    $period, 
				    $at, 
				    sprintf('%d',$a['invoice']), 
				    $from, 
				    $to,
				    $a['discount'],
				    $a['id'],
				    ));
		$LMS->SetTS('assignments');
		$SESSION->redirect('?'.$SESSION->get('backto'));
	}
}
else
{
	$a = $DB->GetRow('SELECT assignments.id AS id, customerid, tariffid, period, at, datefrom, dateto, invoice, discount, liabilityid, 
				(CASE liabilityid WHEN 0 THEN tariffs.name ELSE liabilities.name END) AS name, 
				liabilities.value AS value, liabilities.prodid AS prodid, liabilities.taxid AS taxid
				FROM assignments
				LEFT JOIN tariffs ON (tariffs.id = tariffid)
				LEFT JOIN liabilities ON (liabilities.id = liabilityid)
				WHERE assignments.id = ?',array($_GET['id']));

	if($a['dateto']) 
		$a['dateto'] = date('Y/m/d', $a['dateto']);
	if($a['datefrom'])
		$a['datefrom'] = date('Y/m/d', $a['datefrom']);
	
	switch($a['period'])
	{
		case QUARTERLY:
			$a['at'] = sprintf('%02d/%02d',$a['at']%100,$a['at']/100+1);
			break;
		case YEARLY:
			$a['at'] = date('d/m',($a['at']-1)*86400);
			break;
		case DISPOSABLE:
			$a['at'] = date('Y/m/d', $a['at']);
			break;
	}
}

$layout['pagetitle'] = trans('Customer Charging Edit: $0',$LMS->GetCustomerName($customerid));

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('tariffs', $LMS->GetTariffs());
$SMARTY->assign('taxeslist', $LMS->GetTaxes());
$SMARTY->assign('error', $error);
$SMARTY->assign('assignmentedit', $a);
$SMARTY->assign('assignments', $LMS->GetCustomerAssignments($customerid));
$balancelist['customerid'] = $customerid;
$SMARTY->assign('balancelist', $balancelist);
$SMARTY->display('customerassignmentsedit.html');

?>
