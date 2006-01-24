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

function GetCustomerCovenants($id)
{
	global $CONFIG, $DB;

	if(!$id) return NULL;
	
	if($invoicelist = $DB->GetAll('SELECT docid AS id, cdate, SUM(value)*-1 AS value, number, template
			FROM cash
			LEFT JOIN documents ON (docid = documents.id)
			LEFT JOIN numberplans ON (numberplanid = numberplans.id)
			WHERE cash.customerid = ? AND documents.type = ? AND documents.closed = 0
			GROUP BY docid, cdate, number, template
			ORDER BY cdate DESC LIMIT 10', array($id, DOC_INVOICE)))
	{
		foreach($invoicelist as $idx => $row)
		{
			$invoicelist[$idx]['number'] = docnumber($row['number'], $row['template'], $row['cdate']);
		}
		
		return $invoicelist;
	}
}

function GetCustomerNotes($id)
{
	global $CONFIG, $DB;

	if(!$id) return NULL;
	
	if($invoicelist = $DB->GetAll('SELECT docid AS id, cdate, SUM(value) AS value, number, template
			FROM cash
			LEFT JOIN documents ON (docid = documents.id)
			LEFT JOIN numberplans ON (numberplanid = numberplans.id)
			WHERE cash.customerid = ? AND documents.type = ? AND documents.closed = 0
			GROUP BY docid, cdate, number, template
			HAVING SUM(value) > 0
			ORDER BY cdate DESC LIMIT 10', array($id, DOC_CNOTE)))
	{
		foreach($invoicelist as $idx => $row)
		{
			$invoicelist[$idx]['number'] = docnumber($row['number'], $row['template'], $row['cdate']);
		}
		
		return $invoicelist;
	}
}

$SESSION->restore('receiptcontents', $contents);
$SESSION->restore('receiptcustomer', $customer);
$SESSION->restore('receipt', $receipt);
$SESSION->restore('receiptquery', $receiptquery);
$SESSION->restore('receiptregid', $receipt['regid']);
$SESSION->restore('receipttype', $receipt['type']);
$SESSION->restore('receiptadderror', $error);

switch($_GET['action'])
{
	case 'init':

		$oldreg = $receipt['regid'];
    		unset($receipt);
    		unset($contents);
    		unset($customer);
    		unset($error);

		// get default receipt's numberplanid and next number
		$receipt['regid'] = $_GET['regid'] ? $_GET['regid'] : $oldreg;
		$receipt['type'] = $_GET['type'] ? $_GET['type'] : $_POST['type'];
		$receipt['customerid'] = $_GET['customerid'];

		if(!$receipt['regid'] || !$receipt['type'])
		{
			break;
		}
		
		$receipt['cdate'] = time();
		
		if($receipt['type'] == 'in')
		{
			$receipt['numberplanid'] = $DB->GetOne('SELECT in_numberplanid FROM cashregs WHERE id=?', array($receipt['regid']));
			$receipt['number'] = $LMS->GetNewDocumentNumber(DOC_RECEIPT, $receipt['numberplanid']);
		}	
	
		if($receipt['type'] == 'out')
		{
			$receipt['numberplanid'] = $DB->GetOne('SELECT out_numberplanid FROM cashregs WHERE id=?', array($receipt['regid']));
			$receipt['number'] = $LMS->GetNewDocumentNumber(DOC_RECEIPT, $receipt['numberplanid']);
			if($receipt['regid'])
				if( $DB->GetOne('SELECT SUM(value) FROM receiptcontents WHERE regid = ?', array($receipt['regid']))<=0)
					$error['regid'] = trans('There is no cash in selected registry!');
		}
		
		if($receipt['numberplanid'])
			if(strpos($DB->GetOne('SELECT template FROM numberplans WHERE id=?', array($receipt['numberplanid'])), '%I')!==FALSE)
				$receipt['extended'] = TRUE;

		if(!$error && $receipt['customerid'] && $LMS->CustomerExists($receipt['customerid']))
			$customer = $LMS->GetCustomer($receipt['customerid']);
	break;

	case 'setreg':

    		unset($receipt);
    		unset($contents);
    		unset($customer);
    		unset($error);

		// get default receipt's numberplanid and next number
		$receipt = $_POST['receipt'];
		$receipt['customerid'] = $_POST['customer'];
		
		if(!$receipt['regid']) break;	
		
		$receipt['cdate'] = time();
		$receipt['type'] = $receipt['type'] ? $receipt['type'] : $_POST['type'];
		
		if($receipt['type'] == 'in')
			$receipt['numberplanid'] = $DB->GetOne('SELECT in_numberplanid FROM cashregs WHERE id=?', array($receipt['regid']));
		else
		{
			$receipt['numberplanid'] = $DB->GetOne('SELECT out_numberplanid FROM cashregs WHERE id=?', array($receipt['regid']));
			if($receipt['regid'])
				if( $DB->GetOne('SELECT SUM(value) FROM receiptcontents WHERE regid = ?', array($receipt['regid']))<=0)
					$error['regid'] = trans('There is no cash in selected registry!');
		}
		
		if($receipt['numberplanid'])
			if(strpos($DB->GetOne('SELECT template FROM numberplans WHERE id=?', array($receipt['numberplanid'])), '%I')!==FALSE)
				$receipt['extended'] = TRUE;

		$receipt['number'] = $LMS->GetNewDocumentNumber(DOC_RECEIPT, $receipt['numberplanid']);
	break;

	case 'additem':
		
		unset($error['nocash']);
	
		$itemdata = r_trim($_POST);
		$itemdata['value'] = round((float) str_replace(',','.',$itemdata['value']),2);
		$itemdata['posuid'] = (string) getmicrotime();
		$itemdata['value'] = str_replace(',','.',$itemdata['value']);

		if($receipt['type'] != 'in')
		{
			// sprawdzamy czy mamy tyle kasy w kasie ;)
			$cash = $DB->GetOne('SELECT SUM(value) FROM receiptcontents WHERE regid = ?', array($receipt['regid']));
			
			foreach($contents as $item)
				$sum += $item['value'];
			$sum += $itemdata['value'];
			
			if( $cash < $sum )
				$error['nocash'] = trans('There is no cash in selected registry! You can expense only $0.', moneyf($cash));
		}
	
		if(!$error && $itemdata['value'] && $itemdata['description'])
			$contents[] = $itemdata;
	break;
	
	case 'additemlist':
	
		if($marks = $_POST['marks'])
		{
			unset($error['nocash']);
		
			$cash = $DB->GetOne('SELECT SUM(value) FROM receiptcontents WHERE regid = ?', array($receipt['regid']));
			
			foreach($marks as $id)
			{
				$row = $DB->GetRow('SELECT SUM(value) AS value, number, cdate, template, documents.type AS type
						    FROM cash 
						    LEFT JOIN documents ON (docid = documents.id)
						    LEFT JOIN numberplans ON (numberplanid = numberplans.id)
						    WHERE docid = ?
						    GROUP BY docid, number, cdate, template, documents.type', array($id));
				$itemdata['value'] = $receipt['type']=='in' ? -$row['value'] : $row['value'];
				$itemdata['docid'] = $id;
				$itemdata['posuid'] = (string) (getmicrotime()+$id);
				if($row['type']==DOC_INVOICE)
					$itemdata['description'] = trans('Invoice No. $0', docnumber($row['number'], $row['template'], $row['cdate']));
				else
					$itemdata['description'] = trans('Credit Note No. $0', docnumber($row['number'], $row['template'], $row['cdate']));
				
				if($receipt['type'] != 'in')
				{
					// sprawdzamy czy mamy tyle kasy w kasie ;)
					foreach($contents as $item)
						$sum += $item['value'];
					$sum += $itemdata['value'];
									
					if( $cash < $sum )
					{
						$error['nocash'] = trans('There is no cash in selected registry! You can expense only $0.', moneyf($cash));
						break;
					}
				}

				$contents[] = $itemdata;
			}
		}
	break;
	case 'deletepos':

		if(sizeof($contents))
			foreach($contents as $idx => $row)
				if($row['posuid'] == $_GET['posuid']) 
					unset($contents[$idx]);
	break;
	case 'setcustomer':

		$oldreg = $receipt['regid'];
		$oldtype = $receipt['type'];
		unset($receipt); 
		unset($customer);
		unset($error);

		if($receipt = $_POST['receipt'])
			foreach($receipt as $key => $val)
				$receipt[$key] = $val;
		
		//$receipt['customerid'] = $_POST['customerid'];
		$receipt['type'] = $_POST['type'] ? $_POST['type'] : $oldtype;

		if($receipt['regid'] != $oldreg || !$receipt['numberplanid'])
		{
			if($receipt['type'] == 'in')
				$receipt['numberplanid'] = $DB->GetOne('SELECT in_numberplanid FROM cashregs WHERE id=?', array($receipt['regid']));
			else
				$receipt['numberplanid'] = $DB->GetOne('SELECT out_numberplanid FROM cashregs WHERE id=?', array($receipt['regid']));
				
			$receipt['number'] = 0;
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
		else
			$receipt['cdate'] = time();

		if($receipt['cdate'] && !$receipt['cdatewarning'])
		{
			$maxdate = $DB->GetOne('SELECT MAX(cdate) FROM documents 
						WHERE type = ? AND numberplanid = ?', array(DOC_RECEIPT, $receipt['numberplanid']));

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

		if($receipt['numberplanid'] && !$receipt['extnumber'])
			if(strpos($DB->GetOne('SELECT template FROM numberplans WHERE id=?', array($receipt['numberplanid'])), '%I')!==FALSE)
				$receipt['extended'] = TRUE;

		if($DB->GetOne('SELECT rights FROM cashrights WHERE regid=? AND userid=?', array($receipt['regid'], $AUTH->id))<2)
			$error['regid'] = trans('You don\'t have permission to add receipt in selected cash registry!');
		
		if($receipt['o_type']=='other' || $receipt['o_type']=='move')
		{
			$receipt['customerid'] = 0;
			if(!$error)
				$receipt['selected'] = TRUE;
			break;
		}
		
		$cid = $_GET['customerid'] != '' ? $_GET['customerid'] : $_POST['customer'];
		
		if($receipt['search']!='' && !$cid)
		{
			$search = $receipt['search'];
		        switch($receipt['cat'])
			{
				case 'id':
					$query = 'SELECT id, '.$DB->Concat('UPPER(lastname)',"' '",'name').' AS customername 
						    FROM customers WHERE deleted = 0 AND id = '.intval($search).'
						    ORDER BY customername';
				break;
				case 'ten':
					$query = 'SELECT id, '.$DB->Concat('UPPER(lastname)',"' '",'name').' AS customername 
						    FROM customers WHERE deleted = 0 AND ten = \''.$search.'\'
						    ORDER BY customername';
				break;
				case 'name':
					$query = 'SELECT id, '.$DB->Concat('UPPER(lastname)',"' '",'name').' AS customername 
						    FROM customers WHERE deleted = 0 AND UPPER('.$DB->Concat('lastname',"' '",'name').') ?LIKE? UPPER(\'%'.$search.'%\')
						    ORDER BY customername';
				break;
				case 'address':
					$query = 'SELECT id, '.$DB->Concat('UPPER(lastname)',"' '",'name').' AS customername 
						    FROM customers WHERE deleted = 0 AND UPPER(address) ?LIKE? UPPER(\'%'.$search.'%\')
						    ORDER BY customername';
				break;
				case 'node':
					$query = 'SELECT customers.id AS id, '.$DB->Concat('UPPER(lastname)',"' '",'customers.name').' AS customername 
						    FROM customers, nodes WHERE customers.id = ownerid AND UPPER(nodes.name) ?LIKE? UPPER(\'%'.$search.'%\')
						    GROUP BY customers.id, lastname, customers.name ORDER BY customername';
				break;
				default:
					$query = '';
				break;
			}

			$customerlist = $DB->GetAll($query);
			
			if(sizeof($customerlist)==1)
			{
				$cid = $customerlist[0]['id'];
				unset($customerlist);
			}
			else
				$SESSION->save('receiptquery', $query);
		}

		if(!$error && $cid)
			if($LMS->CustomerExists($cid))
			{
				$receipt['customerid'] = $cid;
				if($receipt['type'] == 'out')
				{
					$balance = $LMS->GetCustomerBalance($cid);
					if( $balance<0 )
						$error['customerid'] = trans('Selected customer is in debt for $0!', moneyf($balance*-1));
					else
						$customer = $LMS->GetCustomer($cid);
				}
				else
					$customer = $LMS->GetCustomer($cid);
			}
		
		if(!$error && $customer)
			$receipt['selected'] = TRUE;
	break;

	case 'save':

		if($contents && $customer)
		{
			$DB->BeginTrans();
		
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
				
				$DB->Execute('INSERT INTO cash (time, type, docid, itemid, value, comment, userid, customerid)
						VALUES(?, 1, ?, ?, ?, ?, ?, ?)', 
						array($receipt['cdate'],
							$rid, 
							$iid, 
							$value, 
							$item['description'],
							$AUTH->id,
							$customer['id']
						));
				
				if($item['docid'])
					$DB->Execute('UPDATE documents SET closed=1 WHERE id=?', array($item['docid']));
			}
		
			$DB->CommitTrans();
			
			$print = TRUE;
		}
		elseif($contents && $receipt['o_type'] == "other")
		{
			$DB->BeginTrans();
		
			$DB->Execute('INSERT INTO documents (type, number, extnumber, numberplanid, cdate, userid, name)
					VALUES(?, ?, ?, ?, ?, ?, ?)',
					array(	DOC_RECEIPT,
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
			
			$print = TRUE;
		}
		
		if($print)
		{
			$SESSION->remove('receiptcontents');
			$SESSION->remove('receiptcustomer');
			$SESSION->remove('receipt');
			$SESSION->remove('receiptadderror');
			$SESSION->redirect('?m=receiptlist&receipt='.$rid.'&which='.$_GET['which'].'&regid='.$receipt['regid'].'#'.$rid);
		}
	break;

	case 'movecash':

		$value = str_replace(',','.',$_POST['value']);
		$dest = $_POST['registry'];
		
		if($value && $dest)
		{
			$cash = $DB->GetOne('SELECT SUM(value) FROM receiptcontents WHERE regid = ?', array($receipt['regid']));
			
			if( $cash < $value )
			{
				$error['nocash'] = trans('There is no cash in selected registry! You can expense only $0.', moneyf($cash));
				break;
			}
		
			$DB->BeginTrans();
			
			// cash-out
			$description = trans('Moving assets to registry $0',$DB->GetOne('SELECT name FROM cashregs WHERE id=?', array($dest)));
			
			$DB->Execute('INSERT INTO documents (type, number, extnumber, numberplanid, cdate, userid, name)
					VALUES(?, ?, ?, ?, ?, ?, ?)',
					array(	DOC_RECEIPT,
						$receipt['number'],
						$receipt['extnumber'] ? $receipt['extnumber'] : '',
						$receipt['numberplanid'],
						$receipt['cdate'],
						$AUTH->id,
						$receipt['o_name']
						));

			$rid = $DB->GetOne('SELECT id FROM documents WHERE type=? AND number=? AND cdate=? AND numberplanid=?', array(DOC_RECEIPT, $receipt['number'], $receipt['cdate'], $receipt['numberplanid'])); 
			
			$DB->Execute('INSERT INTO receiptcontents (docid, itemid, value, description, regid)
				        VALUES(?,?,?,?,?)', 
					array($rid, 
						1, 
						str_replace(',','.', $value*-1),
						$description,
						$receipt['regid']
					));

			// number of cash-out receipt
			$template = $DB->GetOne('SELECT template FROM numberplans WHERE id=?', array($receipt['numberplanid']));
			$r_number = docnumber($receipt['number'], $template, $receipt['cdate']);

			// cash-in
			$description = trans('Moving assets from registry $0 ($1)',$DB->GetOne('SELECT name FROM cashregs WHERE id=?', array($receipt['regid'])), $r_number);
			$numberplan = $DB->GetOne('SELECT in_numberplanid FROM cashregs WHERE id=?', array($dest));
			$number = $LMS->GetNewDocumentNumber(DOC_RECEIPT, $numberplan, $receipt['cdate']);

			$DB->Execute('INSERT INTO documents (type, number, numberplanid, cdate, userid)
					VALUES(?, ?, ?, ?, ?)',
					array(	DOC_RECEIPT,
						$number,
						$numberplan ? $numberplan : 0,
						$receipt['cdate'],
						$AUTH->id
						));
						
			$did = $DB->GetOne('SELECT id FROM documents WHERE type=? AND number=? AND cdate=? AND numberplanid=?', array(DOC_RECEIPT, $number, $receipt['cdate'], $numberplan)); 
			
			$DB->Execute('INSERT INTO receiptcontents (docid, itemid, value, description, regid)
				        VALUES(?,?,?,?,?)', 
					array($did, 
						1, 
						str_replace(',','.', $value),
						$description,
						$dest
					));
		
			$DB->CommitTrans();
			
			$SESSION->remove('receipt');
			$SESSION->remove('receiptadderror');
			$SESSION->redirect('?m=receiptlist&receipt='.$rid.'&which='.$_GET['which'].'&regid='.$receipt['regid'].'#'.$rid);
		}
	break;

}

$SESSION->save('receipt', $receipt);
$SESSION->save('receiptregid', $receipt['regid']);
$SESSION->save('receipttype', $receipt['type']);
$SESSION->save('receiptcontents', $contents);
$SESSION->save('receiptcustomer', $customer);
$SESSION->save('receiptadderror', $error);

if($_GET['action'] != '')
{
	$SESSION->redirect('?m=receiptadd');
}

switch($receipt['type'])
{
	case 'in':
		$layout['pagetitle'] = trans('New Cash-in Receipt');
		$list = GetCustomerCovenants($customer['id']);
	break;
	case 'out':
		$layout['pagetitle'] = trans('New Cash-out Receipt');
		$list = GetCustomerNotes($customer['id']);
	break;
	default:
		$layout['pagetitle'] = trans('New Cash Receipt');
	break;
}

if($list)
	if($contents)
		foreach($list as $row)
		{
			$i = 0;
			foreach($contents as $item)
				if($row['id'] == $item['docid'])
				{
					$i = 1;
					break;
				}
			if(!$i)
				$invoicelist[] = $row;
		}
	else
		$invoicelist = $list;

if($receiptquery)
{
	$customerlist = $DB->GetAll($receiptquery);
	$SESSION->remove('receiptquery');
}

$cashreglist = $DB->GetAllByKey('SELECT id, name FROM cashregs ORDER BY name', 'id');

$SMARTY->assign('invoicelist', $invoicelist);
$SMARTY->assign('customerlist', $customerlist ? $customerlist : $LMS->GetCustomerNames());
$SMARTY->assign('numberplanlist', $LMS->GetNumberPlans(DOC_RECEIPT));
$SMARTY->assign('rights', $DB->GetOne('SELECT rights FROM cashrights WHERE userid=? AND regid=?', array($AUTH->id, $receipt['regid'])));
$SMARTY->assign('cashreglist', $cashreglist);
$SMARTY->assign('cashregcount', sizeof($cashreglist));
$SMARTY->assign('contents', $contents);
$SMARTY->assign('customer', $customer);
$SMARTY->assign('receipt', $receipt);
$SMARTY->assign('error', $error);
$SMARTY->display('receiptadd.html');

?>
