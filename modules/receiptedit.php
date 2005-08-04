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

function GetCustomerCovenants($id)
{
	global $CONFIG, $DB;

	if(!$id) return NULL;
	
	if($covenantlist = $DB->GetAll('SELECT a.docid AS docid, a.itemid AS itemid, MIN(cdate) AS cdate, 
			ROUND(SUM(CASE a.type WHEN 3 THEN a.value*-1 ELSE a.value END)/(CASE COUNT(b.id) WHEN 0 THEN 1 ELSE COUNT(b.id) END),2)
			+ COALESCE(SUM(CASE b.type WHEN 3 THEN b.value*-1 ELSE b.value END),0) AS value
			FROM cash a 
			LEFT JOIN documents d ON (a.docid = d.id)
			LEFT JOIN cash b ON (a.id = b.reference)
			WHERE d.customerid = ? AND d.type = 1 
			AND a.docid > 0 AND a.itemid > 0
			GROUP BY a.docid, a.itemid
			HAVING ROUND(SUM(CASE a.type WHEN 3 THEN a.value*-1 ELSE a.value END)/(CASE COUNT(b.id) WHEN 0 THEN 1 ELSE COUNT(b.id) END),2)
			+ COALESCE(SUM(CASE b.type WHEN 3 THEN b.value*-1 ELSE b.value END),0) > 0
			ORDER BY cdate LIMIT 10', array($id)))
	{
		foreach($covenantlist as $idx => $row)
		{
			$record = $DB->GetRow('SELECT cash.id AS id, number, taxes.label AS tax, comment, template
					    FROM cash 
					    LEFT JOIN documents ON (docid = documents.id)
					    LEFT JOIN taxes ON (taxid = taxes.id)
					    LEFT JOIN numberplans ON (numberplanid = numberplans.id)
					    WHERE docid = ? AND itemid = ? AND cash.type = 4',
					    array($row['docid'], $row['itemid']));
		
			$record['invoice'] = docnumber($record['number'], $record['template'], $row['cdate']);

			$covenantlist[$idx] = array_merge($record, $covenantlist[$idx]);
		}
		return $covenantlist;
	}
}

if(isset($_GET['id']))
{
	$receipt = $DB->GetRow('SELECT documents.*, template 
			    FROM documents 
			    LEFT JOIN numberplans ON (numberplanid = numberplans.id)
			    WHERE documents.id = ? AND type = ?', array($_GET['id'], DOC_RECEIPT));

	if(!$receipt)
		$SESSION->redirect('?'.$SESSION->get('backto'));

	$i = 1;
	
	if($items = $DB->GetAll('SELECT receiptcontents.itemid AS itemid, receiptcontents.value AS value, description, reference 
				FROM receiptcontents LEFT JOIN cash ON (receiptcontents.docid = cash.docid AND receiptcontents.itemid = cash.itemid)
				WHERE receiptcontents.docid = ?', array($receipt['id'])))
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
	case 'additemlist':
	
		if($marks = $_POST['marks'])
			foreach($marks as $id)
			{
				$row = $DB->GetRow('SELECT docid, itemid, comment FROM cash WHERE id = ?', array($id));
				$itemdata['value'] = $LMS->GetItemUnpaidValue($row['docid'], $row['itemid']);
				$itemdata['value'] = str_replace(',','.',$itemdata['value']);
				$itemdata['description'] = $row['comment'];
				$itemdata['reference'] = $id;
				$itemdata['posuid'] = (string) getmicrotime();
				$contents[] = $itemdata;
			}
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
				$item['reference'] = $item['reference'] ? $item['reference'] : 0;
				$DB->Execute('INSERT INTO receiptcontents (docid, itemid, value, description)
					    VALUES(?,?,?,?)', array($rid, $iid, $item['value'], $item['description']));
				$DB->Execute('INSERT INTO cash (time, type, docid, itemid, reference, value, comment, userid, customerid)
					    VALUES(?, 3, ?, ?, ?, ?, ?, ?, ?)', 
					    array($receipt['cdate'],
						$rid, 
						$iid, 
						$item['reference'] ? $item['reference'] : 0, 
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

if($list = GetCustomerCovenants($customer['id'] ? $customer['id'] : $_GET['cid']))
	if($contents)
		foreach($list as $row)
		{
			$i = 0;
			foreach($contents as $item)
				if($row['id'] == $item['reference'])
					$i = 1;
			if(!$i)
				$covenantlist[] = $row;
		}
	else
		$covenantlist = $list;


$SMARTY->assign('customerlist', $LMS->GetCustomerNames());
$SMARTY->assign('covenantlist', $covenantlist);
$SMARTY->assign('numberplanlist', $LMS->GetNumberPlans(DOC_RECEIPT));
$SMARTY->assign('contents', $contents);
$SMARTY->assign('customer', $customer);
$SMARTY->assign('receipt', $receipt);
$SMARTY->assign('error', $error);
$SMARTY->display('receiptedit.html');

?>
