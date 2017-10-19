<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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
 *  $Id: 6e70ce491793915f20304fc291211ebd78873916 $
 */

$addbalance = isset($_POST['addbalance']) ? $_POST['addbalance'] : $_POST['instantpayment'];

foreach($addbalance as $key=>$value)
	if(!is_array($value))
		$addbalance[$key] = trim($value);

$addbalance['value'] = str_replace(',','.', $addbalance['value']);

$currenttime = false;
if(isset($addbalance['time']) && $addbalance['time'] != '' 
	&& !preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}\s+[0-9]{2}:[0-9]{2}$/', $addbalance['time'])
	&& !preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $addbalance['time']))
{
	// here we should throw error back to user about fucked up date format or something
	// otherwise mktime invokes error about expected parameters
}
elseif(isset($addbalance['time']) && $addbalance['time']!='')
{
	// date format 'yyyy/mm/dd hh:mm'	
	$date = preg_split('/\s+/', $addbalance['time']);
	if(isset($date[1]))
		$time = explode(':',$date[1]);
	else {
		$time[0] = $time[1] = 0;
	}	
	$date = explode('/',$date[0]);
	
	if(checkdate($date[1],$date[2],(int)$date[0])) //if date is wrong, set today's date
	{
		$addbalance['time'] = mktime($time[0],$time[1],0,$date[1],$date[2],$date[0]);
	}
	else
	{
		// here too. geez, what the matter with you guys? if($user==E_LAME){ we_know_better(); } ???
		// pls, fix it
		$addbalance['time'] = time();
		$currenttime = true;
	}
}
else
{
	$addbalance['time'] = time();
	$currenttime = true;
}

if (isset($_POST['addbalance']))
	$SESSION->save('addbc', $addbalance['comment']);

if ($currenttime)
	$SESSION->remove('addbt');
else
	$SESSION->save('addbt', $addbalance['time']);

$SESSION->save('addbtax', isset($addbalance['taxid']) ? $addbalance['taxid'] : 0);

if(!isset($addbalance['type']))
        $addbalance['type'] = 1;

if (!empty($addbalance['sourceid']))
{
	if (!$addbalance['type'])
		$addbalance['sourceid'] = NULL;
	$SESSION->save('addsource', $addbalance['sourceid']);
}

if($addbalance['type'] == 0)
	$addbalance['value'] *= -1;
else
	$addbalance['taxid'] = 0;

if(isset($addbalance['mcustomerid']))
{
	foreach($addbalance['mcustomerid'] as $value)
		if($LMS->CustomerExists($value))
		{
			$addbalance['customerid'] = $value;
			if($addbalance['value'] != 0)
				$LMS->AddBalance($addbalance);
		}
} elseif(isset($addbalance['customerid'])) {
	if ($LMS->CustomerExists($addbalance['customerid'])) {
		if ($addbalance['value'] != 0) {
			if ($addbalance['value'] > 0 && $addbalance['type'] == 1 && isset($_GET['receipt'])) {
				$cashregistries = $LMS->GetCashRegistries($addbalance['customerid']);
				if (!empty($cashregistries) && count($cashregistries) == 1
					&& ($liabilities = $LMS->GetOpenedLiabilities($addbalance['customerid']))) {
					// issues instant receipt
					$cashregistry = reset($cashregistries);
					$value = $addbalance['value'];
					$payments = array();
					foreach ($liabilities as $liability) {
						$value_to_pay = $liability['value'] * -1;
						$liability['value'] = min($value, $value_to_pay);
						$liability['description'] = $liability['comment'];
						$payments[] = $liability;
						$value -= $value_to_pay;
						if ($value <= 0)
							break;
					}
					if ($value > 0) {
						$payments[] = array(
							'description' => $addbalance['comment'],
							'value' => $value,
						);
					}
					$receipt = array(
						'number' => 0,
						'numberplanid' => intval($cashregistry['in_numberplanid']),
						'regid' => intval($cashregistry['id']),
						'cdate' => time(),
						'type' => 'in',
						'customer' => $LMS->GetCustomer($addbalance['customerid'], true),
						'contents' => $payments,
					);
					$rid = $LMS->AddReceipt($receipt);
					if (!empty($rid)) {
						$which = array();
						if (!empty($_POST['original'])) $which[] = 'original';
						if (!empty($_POST['copy'])) $which[] = 'copy';
						if (!empty($which) || isset($addbalance['print']))
							$SESSION->save('receiptprint', array(
								'receipt' => $rid,
								'which' => implode(',', $which),
							));
					}
				}
			} else
				$LMS->AddBalance($addbalance);
		}
	}
} else {
	$addbalance['customerid'] = null;
	$addbalance['taxid'] = '0';
	$addbalance['type'] = '1';
	
	if($addbalance['value'] != 0)
		$LMS->AddBalance($addbalance);
}

header('Location: ?'.$SESSION->get('backto'));

?>
