<?php

/*
 * LMS version 1.9-cvs
 *
 *  (C) Copyright 2001-2007 LMS Developers
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

include($CONFIG['phpui']['import_config'] ? $CONFIG['phpui']['import_config'] : 'cashimportcfg.php');

if(is_uploaded_file($_FILES['file']['tmp_name']) && $_FILES['file']['size'])
{
	$file = file($_FILES['file']['tmp_name']);
	foreach($file as $line)
	{
		$id = 0;

		if(strtoupper($encoding) != 'UTF-8')
		{
			$line = iconv($encoding, 'UTF-8//TRANSLIT', $line);
		}
		
		if(!preg_match($pattern, $line, $matches))
			continue;

		$name = isset($matches[$pname]) ? trim($matches[$pname]) : '';
		$lastname = isset($matches[$plastname]) ? trim($matches[$plastname]) : '';
		$comment = isset($matches[$pcomment]) ? trim($matches[$pcomment]) : '';
		$time = isset($matches[$pdate]) ? trim($matches[$pdate]) : '';
		$value = str_replace(',','.', isset($matches[$pvalue]) ? trim($matches[$pvalue]) : '');
		
		if(!$pid)
		{
			if(preg_match("/.*ID[:\-\/]([0-9]{0,4}).*/i", $line, $matches))
				$id = $matches[1];
		}
		else
			$id = isset($matches[$pid]) ? intval($matches[$pid]) : 0;
		
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
		elseif($id && (!$name || !$lastname))
		{
			if($tmp = $DB->GetRow('SELECT lastname, name FROM customers WHERE id = ?', array($id)))
			{
				$lastname = $tmp['lastname'];
				$name = $tmp['name'];
			}
			else
				$id = 0;
		}
		
		if($time)
		{
			if(preg_match($date_regexp, $time, $date))
				$time = mktime(0,0,0, $date[$pmonth], $date[$pday], $date[$pyear]);
			elseif(!is_numeric($time))
				$time = time();
		}
		else
			$time = time();
			
		$customer = trim($lastname.' '.$name);
		$hash = md5($time.$value.$customer.$comment);
		
		if(is_numeric($value))
		{
			if(isset($modvalue) && $modvalue)
			{
				$value = str_replace(',','.', ($value * 100) / 10000);
			}
		
			if(!$DB->GetOne('SELECT id FROM cashimport WHERE hash = ?', array($hash)))
				$DB->Execute('INSERT INTO cashimport (date, value, customer, 
					customerid, description, hash) VALUES (?,?,?,?,?,?)',
					array($time, $value, $customer, $id, $comment, $hash));
		}
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
