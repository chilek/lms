<?php

/*
 * LMS version 1.9-cvs
 *
 *  (C) Copyright 2001-2006 LMS Developers
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
	$regid = $DB->GetOne('SELECT DISTINCT regid FROM receiptcontents WHERE docid=?', array($_GET['id']));
	if($DB->GetOne('SELECT rights FROM cashrights WHERE userid=? AND regid=?', array($AUTH->id, $regid))<3)
	{
	        $SMARTY->display('noaccess.html');
	        $SESSION->close();
	        die;
	}			

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
			$sum += $item['value'];
			if($item['value'] < 0) $item['value'] *= -1;
			$contents[] = $item;
			
		}

	$receipt['regid'] = $regid;
	$receipt['type'] = $sum > 0 ? 'in' : 'out';
	if($receipt['customerid'])
		$receipt['o_type'] = 'customer';
	else
	{
		$receipt['o_type'] = 'other';
		$receipt['o_name'] = $receipt['name'];
	}
	
	if($receipt['customerid'])
		$customer = $LMS->GetCustomer($receipt['customerid']);

	if($receipt['numberplanid'] && !$receipt['extnumber'])
    		if(strpos($receipt['template'], '%I')!==FALSE)
	                $receipt['extended'] = TRUE;
	
	$SESSION->save('receipt', $receipt);
	$SESSION->save('receiptcontents', $contents);
	$SESSION->save('receiptcustomer', $customer);
	$SESSION->save('receiptediterror', $error);
}

$SESSION->restore('receiptcontents', $contents);
$SESSION->restore('receiptcustomer', $customer);
$SESSION->restore('receipt', $receipt);
$SESSION->restore('receiptediterror', $error);

$receipt['titlenumber'] = docnumber($receipt['number'], $receipt['template'], $receipt['cdate'], $receipt['extnumber']);
if($receipt['type']=='in')
	$layout['pagetitle'] = trans('Cash-in Receipt Edit: $0', $receipt['titlenumber']);
else
	$layout['pagetitle'] = trans('Cash-out Receipt Edit: $0', $receipt['titlenumber']);

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

		$oldcid = $receipt['customerid'];
		$oldnumber = $receipt['number'];
		$oldcdate = $receipt['cdate'];
		$oldreg = $receipt['regid'];
		$oldtemplate = $receipt['template'];
		$id = $receipt['id'];
		
		unset($receipt);
		unset($customer);
		unset($error);
		
		if($receipt = $_POST['receipt'])
			foreach($receipt as $key => $val)
				$receipt[$key] = $val;
		
		$receipt['customerid'] = $_POST['customerid'];
		$receipt['template'] = $oldtemplate;
		$receipt['id'] = $id;
		
		if($receipt['regid'] != $oldreg)
		{
		        if($receipt['type'] == 'in')
				$receipt['numberplanid'] = $DB->GetOne('SELECT in_numberplanid FROM cashregs WHERE id=?', array($receipt['regid']));
			else
				$receipt['numberplanid'] = $DB->GetOne('SELECT out_numberplanid FROM cashregs WHERE id=?', array($receipt['regid']));
			
			$receipt['number'] = 0;
			
			if($DB->GetOne('SELECT rights FROM cashrights WHERE regid=? AND userid=?', array($receipt['regid'], $AUTH->id))<2)
			        $error['regid'] = trans('You don\'t have permission to add receipt in selected cash registry!');
		}
		
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
				$maxdate = $DB->GetOne('SELECT MAX(cdate) FROM documents WHERE type = ? AND numberplanid = ?', array(DOC_RECEIPT, $receipt['numberplanid']));
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

		if($receipt['numberplanid'] && !$receipt['extnumber'])
    			if(strpos($DB->GetOne('SELECT template FROM numberplans WHERE id=?', array($receipt['numberplanid'])), '%I')!==FALSE)
	            		$receipt['extended'] = TRUE;

		if($receipt['o_type']=='other')
                {
		        $receipt['customerid'] = 0;
			if(!$error)
			        $receipt['selected'] = TRUE;
			break;
		}
		
		$cid = $_GET['customerid'] != '' ? $_GET['customerid'] : $_POST['customer'];
		
		if(!$cid)
			$cid = $oldcid;
		
		if(!$error)
			if($LMS->CustomerExists(($cid)))
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
			$DB->Execute('INSERT INTO documents (type, number, extnumber, numberplanid, cdate, customerid, userid, name, address, zip, city)
					VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
					array(	DOC_RECEIPT,
						$receipt['number'],
						$receipt['extnumber'] ? $receipt['extnumber'] : '',
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

				if($receipt['type'] == 'in')
				{
					$DB->Execute('INSERT INTO receiptcontents (docid, itemid, value, description, regid)
						VALUES(?,?,?,?,?)', 
						array($rid, 
							$iid, 
							str_replace(',','.',$item['value']), 
							$item['description'],
							$receipt['regid']
						));
					$DB->Execute('INSERT INTO cash (type, time, docid, itemid, value, comment, userid, customerid)
					        VALUES(1, ?, ?, ?, ?, ?, ?, ?)', 
						array($receipt['cdate'],
							$rid, 
							$iid, 
							str_replace(',','.',$item['value']),
							$item['description'],
							$AUTH->id,
							$customer['id']
						));
				}
				else
				{
					$DB->Execute('INSERT INTO receiptcontents (docid, itemid, value, description, regid)
						VALUES(?,?,?,?,?)', 
						array($rid, 
							$iid, 
							str_replace(',','.',$item['value']*-1), 
							$item['description'],
							$receipt['regid']
						));
				}
			}
			$DB->CommitTrans();
		}
		elseif($contents && $receipt['o_type'] =='other')
		{
		        $DB->BeginTrans();
			
			// delete old receipt 
			$DB->Execute('DELETE FROM documents WHERE id = ?', array($receipt['id']));
			$DB->Execute('DELETE FROM receiptcontents WHERE docid = ?', array($receipt['id']));
			
			$DB->Execute('INSERT INTO documents (type, number, extnumber, numberplanid, cdate, userid, name)
			    		VALUES(?, ?, ?, ?, ?, ?, ?)',
			                array(  DOC_RECEIPT,
					        $receipt['number'],
						$receipt['extnumber'] ? $receipt['extnumber'] : '',
						$receipt['numberplanid'],
						$receipt['cdate'],
						$AUTH->id,
						$receipt['o_name']
					));
						
			$rid = $DB->GetOne('SELECT id FROM documents WHERE type=? AND number=? AND cdate=?', array(DOC_RECEIPT, $receipt['number'], $receipt['cdate'])); 
			
			$iid = 0;
			foreach($contents as $item)
			{
				$iid++;
				
				if($receipt['type'] == 'in')
				        $value = str_replace(',','.',$item['value']);
				else
				        $value = str_replace(',','.',$item['value']*-1);
				
				$DB->Execute('INSERT INTO receiptcontents (docid, itemid, value, description, regid)
					    VALUES(?,?,?,?,?)', 
					    array($rid, 
						    $iid, 
						    $value, 
						    $item['description'],
						    $receipt['regid']
					    ));
			}
			
			$DB->CommitTrans();
		}
		else
			break;
		
		$SESSION->remove('receiptcontents');
		$SESSION->remove('receiptcustomer');
		$SESSION->remove('receipt');
		$SESSION->remove('receiptediterror');
		$SESSION->redirect('?m=receiptlist&receipt='.$rid.'&which='.$_GET['which'].'&regid='.$receipt['regid'].'#'.$rid);
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

$cashreglist = $DB->GetAllByKey('SELECT id, name FROM cashregs ORDER BY name', 'id');

$SMARTY->assign('customerlist', $LMS->GetCustomerNames());
$SMARTY->assign('numberplanlist', $LMS->GetNumberPlans(DOC_RECEIPT));
$SMARTY->assign('cashreglist', $cashreglist);
$SMARTY->assign('cashregcount', sizeof($cashreglist));
$SMARTY->assign('contents', $contents);
$SMARTY->assign('customer', $customer);
$SMARTY->assign('receipt', $receipt);
$SMARTY->assign('error', $error);
$SMARTY->display('receiptedit.html');

?>
