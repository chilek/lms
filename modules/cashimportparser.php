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

include($LMS->CONFIG['phpui']['import_config'] ? $LMS->CONFIG['phpui']['import_config'] : 'cashimportcfg.php');

if(is_uploaded_file($_FILES['file']['tmp_name']) && $_FILES['file']['size'])
{
	$file = file($_FILES['file']['tmp_name']);
	foreach($file as $line)
	{
		$id = 0;
		preg_match($pattern, $line, $matches);
//print_r($matches);
		$name = trim($matches[$pname]);
		$lastname = trim($matches[$plastname]);
		$comment = trim($matches[$pcomment]);
		$time = trim($matches[$pdate]);
		$value = str_replace(',','.',trim($matches[$pvalue]));

		if($encoding != 'UTF-8')
		{
			echo $customer;
			$customer = iconv($encoding, 'UTF-8', $customer);
			$comment = iconv($encoding, 'UTF-8', $comment);
			echo "*".$comment.$customer;
		}
		
		if(!$pid)
		{
			if(preg_match("/.*ID[:\-\/]([0-9]{0,4}).*/i", $line, $matches))
				$id = $matches[1];
		}
		else
			$id = trim($matches[$pid]);
		
/*		// seek invoice number
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
*/		
		if(!$id && $name && $lastname)
		{
			$uids = $DB->GetCol('SELECT id FROM customers WHERE UPPER(lastname)=UPPER(?) and UPPER(name)=UPPER(?)', array($lastname, $name));
			if(sizeof($uids)==1)
				$id = $uids[0];
		}
		
		if($time)
		{
			if(preg_match($date_regexp, $time, $date))
				$time = mktime(0,0,0, $date[$pmonth], $date[$pday], $date[$pyear]);
		}
		else
			$time = time();
			
		$customer = trim($lastname.' '.$name);
		$hash = md5($time.$value.$customer.$comment);

		if(is_numeric($value))
			if(!$LMS->DB->GetOne('SELECT id FROM cashimport WHERE hash=?', array($hash)))
				$LMS->DB->Execute('INSERT INTO cashimport(date, value, customer, customerid, description, hash) VALUES(?,?,?,?,?,?)',
					array($time, $value, $customer, $id, $comment, $hash));
		
	}
	
	$SESSION->redirect('?m=cashimport');
} 
else // upload errors
	switch($_FILES['file']['error'])
	{
		case 1: 			
		case 2: $error['file'] = trans('File is too large.'); break;
		case 3: $error['file'] = trans('File upload has finished prematurely.'); break;
		case 4: $error['file'] = trans('Path to file was not specified.'); break;
		default: $error['file'] = trans('Problem during file upload.'); break;
	}	

$layout['pagetitle'] = trans('Cash Operations Import');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('error', $error);
$SMARTY->display('cashimport.html');

?>
