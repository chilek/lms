<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2010 LMS Developers
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

// get customer name and check privileges using customersview
$customer = $DB->GetRow('SELECT id, divisionid, '
    .$DB->Concat('lastname',"' '",'name').' AS name
    FROM customersview WHERE id = ?', array($_GET['id']));

if(!$customer)
{
    $SESSION->redirect('?'.$SESSION->get('backto'));
}

if(isset($_POST['assignment']))
{
	$a = $_POST['assignment'];

	foreach($a as $key => $val)
	    if(!is_array($val))
		    $a[$key] = trim($val);

	$period = sprintf('%d',$a['period']);

	switch($period)
	{
		case DAILY:
			$at = 0;
		break;

		case WEEKLY:
			$at = sprintf('%d',$a['at']);

			if(chkconfig($CONFIG['phpui']['use_current_payday']) && $at==0)
			{
				$at = strftime('%u', time());
			}

			if($at < 1 || $at > 7)
				$error['at'] = trans('Incorrect day of week (1-7)!');
		break;

		case MONTHLY:
			$at = sprintf('%d',$a['at']);

			if(chkconfig($CONFIG['phpui']['use_current_payday']) && $at==0)
				$at = date('j', time());

			if(!chkconfig($CONFIG['phpui']['use_current_payday']) 
				&& $CONFIG['phpui']['default_monthly_payday']>0 && $at==0)
			{
				$at = $CONFIG['phpui']['default_monthly_payday'];
			}

			$a['at'] = $at;

			if($at > 28 || $at < 1)
				$error['at'] = trans('Incorrect day of month (1-28)!');
		break;

		case QUARTERLY:
			if(chkconfig($CONFIG['phpui']['use_current_payday']) && !$a['at'])
			{
				$d = date('j', time());
				$m = date('n', time());
				$a['at'] = $d.'/'.$m;
			}
			elseif(!preg_match('/^[0-9]{2}\/[0-9]{2}$/', $a['at']))
			{
				$error['at'] = trans('Incorrect date format! Enter date in DD/MM format!');
			}
			else
			{
				list($d,$m) = explode('/',$a['at']);
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

		case HALFYEARLY:
			if(!preg_match('/^[0-9]{2}\/[0-9]{2}$/', $a['at']) && $a['at'])
			{
				$error['at'] = trans('Incorrect date format! Enter date in DD/MM format!');
			}
			elseif(chkconfig($CONFIG['phpui']['use_current_payday']) && !$a['at'])
			{
				$d = date('j', time());
				$m = date('n', time());
				$a['at'] = $d.'/'.$m;
			}
			else
			{
				list($d,$m) = explode('/',$a['at']);
			}

			if(!$error)
			{
				if($d>30 || $d<1 || ($d>28 && $m==2))
					$error['at'] = trans('This month doesn\'t contain specified number of days');
				if($m>6 || $m<1)
					$error['at'] = trans('Incorrect month number (max.6)!');

				$at = ($m-1) * 100 + $d;
			}
		break;

		case YEARLY:
			if(chkconfig($CONFIG['phpui']['use_current_payday']) && !$a['at'])
			{
				$d = date('j', time());
				$m = date('n', time());
				$a['at'] = $d.'/'.$m;
			}
			elseif(!preg_match('/^[0-9]{2}\/[0-9]{2}$/', $a['at']))
			{
				$error['at'] = trans('Incorrect date format! Enter date in DD/MM format!');
			}
			else
			{
				list($d,$m) = explode('/',$a['at']);
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

		default: // DISPOSABLE
			$period = DISPOSABLE;

			if($a['tariffid'] != -1)
			{
				$a['dateto'] = '';
				$a['datefrom'] = '';
			}

			if(preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $a['at']))
			{
				list($y, $m, $d) = explode('/', $a['at']);
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
	}

	if($a['datefrom'] == '')
		$from = 0;
	elseif(preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/',$a['datefrom']))
	{
		list($y, $m, $d) = explode('/', $a['datefrom']);
		if(checkdate($m, $d, $y))
			$from = mktime(0, 0, 0, $m, $d, $y);
		else
			$error['datefrom'] = trans('Incorrect charging time!');
	}
	else
		$error['datefrom'] = trans('Incorrect charging time!');

	if($a['dateto'] == '')
		$to = 0;
	elseif(preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $a['dateto']))
	{
		list($y, $m, $d) = explode('/', $a['dateto']);
		if(checkdate($m, $d, $y))
			$to = mktime(23, 59, 59, $m, $d, $y);
		else
			$error['dateto'] = trans('Incorrect charging time!');
	}
	else
		$error['dateto'] = trans('Incorrect charging time!');

	if($to < $from && $to != 0 && $from != 0)
		$error['dateto'] = trans('Incorrect date range!');

	$a['discount'] = str_replace(',','.',$a['discount']);
	if($a['discount'] == '')
		$a['discount'] = 0;
	elseif($a['discount']<0 || $a['discount']>99.99 || !is_numeric($a['discount']))
		$error['discount'] = trans('Wrong discount value!');

	if($a['tariffid'] == -1)  // suspending
	{
		unset($error['at']);
		$at = 0;
	}
	else if (!$a['tariffid']) { // tariffless
	    if (!$a['name'])
		    $error['name'] = trans('Liability name is required!');
	    if (!$a['value'])
		    $error['value'] = trans('Liability value is required!');
		else if(!preg_match('/^[-]?[0-9.,]+$/', $a['value']))
		    $error['value'] = trans('Incorrect value!');
    }

	if(!$error)
	{
	    if ($a['tariffid'] == -1) {
	        $a['tariffid'] = 0;
	        $a['discount'] = 0;
	        unset($a['invoice']);
	        unset($a['settlement']);
        }
        if ($a['tariffid'])
		    $a['value'] = 0;

        $a['customerid'] = $customer['id'];
        $a['period']     = $period;
        $a['at']         = $at;
		$a['datefrom']   = $from;
		$a['dateto']     = $to;

		$LMS->AddAssignment($a);

		$SESSION->redirect('?'.$SESSION->get('backto'));
	}

    $SMARTY->assign('error', $error);
}
else
{
    if (!empty($CONFIG['phpui']['default_assignment_invoice']))
        $a['invoice'] = true;
    if (!empty($CONFIG['phpui']['default_assignment_settlement']))
        $a['settlement'] = true;
    if (!empty($CONFIG['phpui']['default_assignment_period']))
        $a['period'] = $CONFIG['phpui']['default_assignment_period'];
}

$expired = isset($_GET['expired']) ? $_GET['expired'] : false;

$layout['pagetitle'] = trans('New Liability: $0', '<A href="?m=customerinfo&id='.$customer['id'].'">'.$customer['name'].'</A>');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$customernodes = $LMS->GetCustomerNodes($customer['id']);
unset($customernodes['total']);

$SMARTY->assign('assignment', $a);
$SMARTY->assign('customernodes', $customernodes);
$SMARTY->assign('tariffs', $LMS->GetTariffs());
$SMARTY->assign('taxeslist', $LMS->GetTaxes());
$SMARTY->assign('expired', $expired);
$SMARTY->assign('assignments', $LMS->GetCustomerAssignments($customer['id'], $expired));
$SMARTY->assign('numberplanlist', $LMS->GetNumberPlans(DOC_INVOICE, NULL, $customer['divisionid'], false));
$SMARTY->assign('customerinfo', $customer);

$SMARTY->display('customerassignmentsedit.html');

?>
