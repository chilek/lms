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

if(isset($_GET['id']))
{
	$receipt = $DB->GetRow('SELECT documents.*, template 
			    FROM documents 
			    LEFT JOIN numberplans ON (numberplanid = numberplans.id)
			    WHERE documents.id = ? AND type = ?', array($_GET['id'], DOC_RECEIPT));

	if(!$receipt)
		$SESSION->redirect('?'.$SESSION->get('backto'));

	$i = 1;
	
	if($items = $DB->GetAll('SELECT itemid, value, description FROM receiptcontents WHERE docid = ?', array($receipt['id'])))
		foreach($items as $item)
		{
			$item['posuid'] = $i++;
			$contents[] = $item;
		}

	$customer = $LMS->GetCustomer($receipt['customerid']);
	
	$SESSION->save('receipt', $receipt);
	$SESSION->save('receiptcontents', $contents);
	$SESSION->save('receiptcustomer', $customer);
	$SESSION->save('receiptediterror', $error);
}

$SESSION->restore('receiptcontents', $contents);
$SESSION->restore('receiptcustomer', $customer);
$SESSION->restore('receipt', $receipt);
$SESSION->restore('receiptediterror', $error);

$receipt['titlenumber'] = docnumber($receipt['number'], $receipt['template'], $receipt['cdate']);
$layout['pagetitle'] = trans('Cash Receipt Edit: $0', $receipt['titlenumber']);

switch($_GET['action'])
{
	case 'additem':
		
		$itemdata = r_trim($_POST);
		$itemdata['value'] = round((float) str_replace(',','.',$itemdata['value']),2);
		// workaround for PHP 4.3.10 bug
		$itemdata['value'] = str_replace(',','.',$itemdata['value']);
		$itemdata['posuid'] = (string) getmicrotime();
	
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

		$oldnumber = $receipt['number'];
		$oldcdate = $receipt['cdate'];
		$id = $receipt['id'];
		
		unset($receipt);
		unset($customer);
		unset($error);
		
		if($receipt = $_POST['receipt'])
			foreach($receipt as $key => $val)
				$receipt[$key] = $val;
		
		$receipt['customerid'] = $_POST['customerid'];
		$receipt['id'] = $id;
		
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
		
		$newday = date('Ymd',$receipt['cdate']);
		$oldday = date('Ymd',$oldcdate);
		if($newday != $oldday)
			if($receipt['cdate'] && !$receipt['cdatewarning'])
			{
				$maxdate = $DB->GetOne('SELECT MAX(cdate) FROM documents WHERE type = ?', array(DOC_RECEIPT));
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
			elseif($receipt['number']!=$oldnumber)
				if($LMS->DocumentExists($receipt['number'], DOC_RECEIPT, $receipt['numberplanid'], $receipt['cdate']))
					$error['number'] = trans('Receipt number $0 already exists!', $receipt['number']);
		}
		
		if(!$error)
			if($LMS->CustomerExists(($_GET['customerid'] != '' ? $_GET['customerid'] : $_POST['customer'])))
				$customer = $LMS->GetCustomer(($_GET['customerid'] != '' ? $_GET['customerid'] : $_POST['customer']));

	break;
	case 'save':

		if($contents && $customer)
		{
			$DB->BeginTrans();

			// delete old receipt 
			$DB->Execute('DELETE FROM documents WHERE id = ?', array($receipt['id']));
			$DB->Execute('DELETE FROM receiptcontents WHERE docid = ?', array($receipt['id']));
			$DB->Execute('DELETE FROM cash WHERE docid = ?', array($receipt['id']));
		
			// re-add receipt 
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
				$DB->Execute('INSERT INTO cash (type, time, docid, itemid, value, comment, userid, customerid)
					    VALUES(1, ?, ?, ?, ?, ?, ?, ?)', 
					    array($receipt['cdate'],
						$rid, 
						$iid, 
						$item['value'], 
						$item['description'],
						$AUTH->id,
						$customer['id']));
			}
			$DB->CommitTrans();
			
			$SESSION->remove('receiptcontents');
			$SESSION->remove('receiptcustomer');
			$SESSION->remove('receipt');
			$SESSION->remove('receiptediterror');
			$SESSION->redirect('?m=receipt&id='.$rid.'&which='.$_GET['which']);
		}
	break;
}

$SESSION->save('receipt', $receipt);
$SESSION->save('receiptcontents', $contents);
$SESSION->save('receiptcustomer', $customer);
$SESSION->save('receiptediterror', $error);

if($_GET['action'] != '')
{
	$SESSION->redirect('?m=receiptedit');
}

$SMARTY->assign('customerlist', $LMS->GetCustomerNames());
$SMARTY->assign('numberplanlist', $LMS->GetNumberPlans(DOC_RECEIPT));
$SMARTY->assign('contents', $contents);
$SMARTY->assign('customer', $customer);
$SMARTY->assign('receipt', $receipt);
$SMARTY->assign('error', $error);
$SMARTY->display('receiptedit.html');

?>
