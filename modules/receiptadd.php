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

$layout['pagetitle'] = trans('New Cash Receipt');

$SESSION->restore('receiptcontents', $contents);
$SESSION->restore('receiptcustomer', $customer);
$SESSION->restore('receipt', $receipt);
$SESSION->restore('receiptadderror', $error);

switch($_GET['action'])
{
	case 'init':

    		unset($receipt);
    		unset($contents);
    		unset($customer);
    		unset($error);

		// get default receipt's numberplanid and next number
		$receipt['numberplanid'] = $DB->GetOne('SELECT id FROM numberplans WHERE doctype = ? AND isdefault = 1', array(DOC_RECEIPT));
		$receipt['number'] = $LMS->GetNewDocumentNumber(DOC_RECEIPT, $receipt['numberplanid']);
		$receipt['cdate'] = time();
		if($_GET['customerid'] != '' && $LMS->CustomerExists($_GET['customerid']))
			$customer = $LMS->GetCustomer($_GET['customerid']);
	break;
	case 'additem':
		
		$itemdata = r_trim($_POST);
		$itemdata['value'] = round((float) str_replace(',','.',$itemdata['value']),2);
		$itemdata['posuid'] = (string) getmicrotime();
		$itemdata['value'] = str_replace(',','.',$itemdata['value']);
	
		if($itemdata['value'] && $itemdata['description'])
			$contents[] = $itemdata;
	break;
	case 'deletepos':

		if(sizeof($contents))
			foreach($contents as $idx => $row)
				if($row['posuid'] == $_GET['posuid']) 
					unset($contents[$idx]);
	break;
	case 'setcustomer':

		unset($receipt); 
		unset($customer);
		unset($error);
		
		if($receipt = $_POST['receipt'])
			foreach($receipt as $key => $val)
				$receipt[$key] = $val;
		
		$receipt['customerid'] = $_POST['customerid'];
		
		if($receipt['cdate'])
		{
			list($year, $month, $day) = split('/',$receipt['cdate']);
			if(checkdate($month, $day, $year)) 
			{
				$receipt['cdate'] = mktime(date('G',time()),date('i',time()),date('s',time()),$month,$day,$year);
			}				
			else
			{
				$error['cdate'] = trans('Incorrect date format!');
				$receipt['cdate'] = time();
				break;
			}
		}

		if($receipt['cdate'] && !$receipt['cdatewarning'])
		{
			$maxdate = $DB->GetOne('SELECT MAX(cdate) FROM documents WHERE type = 2');
			if($receipt['cdate'] < $maxdate)
			{
				$error['cdate'] = trans('Last date of receipt settlement is $0. If sure, you want to write receipt with date of $1, then click "Submit" again.',date('Y/m/d H:i', $maxdate), date('Y/m/d H:i', $receipt['cdate']));
				$receipt['cdatewarning'] = 1;
			}
		}

		if(!$receipt['number'])
			$receipt['number'] = $LMS->GetNewDocumentNumber(DOC_RECEIPT, $receipt['numberplanid'], $receipt['cdate']);
		else
		{
			if(!eregi('^[0-9]+$', $receipt['number']))
				$error['number'] = trans('Receipt number must be integer!');
			elseif($LMS->DocumentExists($receipt['number'], DOC_RECEIPT, $receipt['numberplanid'], $receipt['cdate']))
				$error['number'] = trans('Receipt number $0 already exists!', $receipt['number']);
		}
		
		if(!$error)
			if($LMS->CustomerExists(($_GET['customerid'] != '' ? $_GET['customerid'] : $_POST['customer'])))
				$customer = $LMS->GetCustomer(($_GET['customerid'] != '' ? $_GET['customerid'] : $_POST['customer']));

	break;
	case 'save':

		if($contents && $customer)
		{
			$DB->Execute('INSERT INTO documents (type, number, numberplanid, cdate, customerid, userid, name, address, zip, city)
					VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
					array(	DOC_RECEIPT,
						$receipt['number'],
						$receipt['numberplanid'],
						$receipt['cdate'],
						$customer['id'],
						$AUTH->id,
						$customer['customername'],
						$customer['address'],
						$customer['zip'],
						$customer['city']));
						
			$rid = $DB->GetOne('SELECT id FROM documents WHERE type=? AND number=? AND cdate=?', array(DOC_RECEIPT, $receipt['number'], $receipt['cdate'])); 
			
			$iid = 0;
			foreach($contents as $item)
			{
				$iid++;
				$DB->Execute('INSERT INTO receiptcontents (docid, itemid, value, description)
					    VALUES(?,?,?,?)', array($rid, $iid, $item['value'], $item['description']));
				$DB->Execute('INSERT INTO cash (time, type, docid, itemid, value, comment, userid, customerid)
					    VALUES(?, 3, ?, ?, ?, ?, ?, ?)', 
					    array($receipt['cdate'],
						$rid, 
						$iid, 
						$item['value'], 
						$item['description'],
						$AUTH->id,
						$customer['id']));
			}
		
			$SESSION->remove('receiptcontents');
			$SESSION->remove('receiptcustomer');
			$SESSION->remove('receipt');
			$SESSION->remove('receiptadderror');
			$SESSION->redirect('?m=receipt&id='.$rid.'&which='.$_GET['which']);
		}
	break;
}

$SESSION->save('receipt', $receipt);
$SESSION->save('receiptcontents', $contents);
$SESSION->save('receiptcustomer', $customer);
$SESSION->save('receiptadderror', $error);

if($_GET['action'] != '')
{
	$SESSION->redirect('?m=receiptadd');
}

$SMARTY->assign('customerlist', $LMS->GetCustomerNames());
$SMARTY->assign('numberplanlist', $LMS->GetNumberPlans(DOC_RECEIPT));
$SMARTY->assign('contents', $contents);
$SMARTY->assign('customer', $customer);
$SMARTY->assign('receipt', $receipt);
$SMARTY->assign('error', $error);
$SMARTY->display('receiptadd.html');

?>
