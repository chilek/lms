<?php

/*
 * LMS version 1.5-cvs
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

/********* konfiguracja (patrz plik README) *********/
$pattern = "/^([^ ]+)\t([^ ]+)[\s\t]+([^ ]+)\t([^ ]+)\t(.*)/";
$pid = 0;	// customer ID position in expression
		// if zero - we try to search ID by regexp,
		// invoice number or customer name and forename in entire row
$pname = 2;	// name position 
$plastname = 3; // forename position 
$pvalue = 4;	// value position
$pcomment = 5;  // operation comment position
$pdate = 1;  	// date position
$date_regexp = '/([0-9]{2})\.([0-9]{2})\.([0-9]{4})/'; // date format (dd.mm.yyyy)
$pday = 1;
$pmonth = 2;
$pyear = 3;
$invoice_regexp = '/.* (\d*)\/LMS\/([0-9]{4}).*/'; 	// format of invoice number
							// default %N/LMS/%Y
$pinvoice_number = 1; // position of invoice number in $invoice_regexp
$pinvoice_year = 2;   // year position in $invoice_regexp
$taxvalue = '0.0';	// Tax rate: 'zw.', '0.0', '7.0', '22.0'	
/*************** koniec konfiguracji *****************/

include('importlang.php');

if(isset($_GET['upload']))
{
	if(is_uploaded_file($_FILES['file']['tmp_name']) && $_FILES['file']['size'])
	{
		$file = file($_FILES['file']['tmp_name']);
		foreach($file as $line)
		{
			$id = 0;
			preg_match($pattern, $line, $matches);

			$name = trim($matches[$pname]);
			$lastname = trim($matches[$plastname]);
			$comment = trim($matches[$pcomment]);
			$time = trim($matches[$pdate]);
			$value = str_replace(',','.',trim($matches[$pvalue]));
			
			if(!$pid)
			{
				if(preg_match("/.*ID[:\-\/]([0-9]{0,4}).*/i", $line, $matches))
					$id = $matches[1];
			}
			else
				$id = trim($matches[$pid]);
			
			//szukamy faktury
			if(!$id)
			{
				if(preg_match($invoice_regexp, $line, $matches)) 
				{
					$invid = $matches[$pinvoice_number];
					$invyear = $matches[$pinvoice_year];
					if($invid && $invyear)
					{
						$from = mktime(0,0,0,1,1,$invyear);
						$to = mktime(0,0,0,13,1,$invyear);
						$id = $DB->GetOne('SELECT customerid FROM invoices WHERE number=? AND cdate>? AND cdate<?', array($invid, $from, $to));
					}
				}
			}
			
			if(!$id && $name && $lastname)
			{
				$uids = $DB->GetCol('SELECT id FROM users WHERE UPPER(lastname)=UPPER(?) and UPPER(name)=UPPER(?)', array($lastname, $name));
				if(sizeof($uids)==1)
					$id = $uids[0];
			}
			
			if($id && $LMS->UserExists($id))
				$import['error'][] = 0;
			else
				$import['error'][] = 1;

			if($time)
			{
				if(preg_match($date_regexp, $time, $date))
					$time = mktime(0,0,0, $date[$pmonth], $date[$pday], $date[$pyear]);
			}
			else
				$time = time();
				
			$import['id'][] = $id;
			$import['name'][] = $name;
			$import['lastname'][] = $lastname;
			$import['value'][] = $value;
			$import['comment'][] = $comment;
			$import['time'][] = $time;
		}
	} 
	else // upload errors
		switch($_FILES['file']['error'])
		{
			case 1: 			
			case 2: $error['file'] = trans('FIle is too large.'); break;
			case 3: $error['file'] = trans('File has been uploaded partly.'); break;
			case 4: $error['file'] = trans('Path to file was not specified.'); break;
			default: $error['file'] = trans'Problem during file upload.'); break;
		}	
}

if(isset($_POST['import']))
{
	$SMARTY->display('header.html');
	$SMARTY->display('balanceheader.html');
	echo '<H1><B>'.trans('Payments Import').'</B></H1>';
	
	$i=0;
	$total=0;
	
	foreach($_POST['import'] as $import)
	{
		if(!$import['id'] || !$import['value'] || !isset($import['box']))
			continue;
			
		if($LMS->UserExists($import['id']))
		{
			$balance['value'] = str_replace(',','.',trim($import['value']));
			$balance['taxvalue'] = $taxvalue;
			$balance['userid'] = $import['id'];
			$balance['type'] = 3;
			$balance['comment'] = $import['comment'];
			
			list($year,$month,$day) = explode('/',$import['time']);
			$balance['time'] = mktime(0,0,0,$month, $day, $year);
			
			$LMS->AddBalance($balance);
			$total += $balance['value'];
			$i++;
			trans('Customer ID:$0 - value: $1<BR>', printf('%04d',$balance['userid']), moneyf($balance['value']));
		}
	}

	trans('<BR>Saved $0 rec. on amount $1', $i, moneyf($total));
	$SMARTY->display('footer.html');
	die;
}

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

$layout['pagetitle'] = trans('Payments Import');

$SMARTY->assign('import', $import);
$SMARTY->assign('error', $error);
$SMARTY->display('import.html');

?>