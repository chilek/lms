<?php

/*
 * LMS version 1.10-cvs
 *
 *  (C) Copyright 2001-2008 LMS Developers
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
	
	if($invoicelist = $DB->GetAllByKey('SELECT docid AS id, cdate, SUM(value)*-1 AS value, number, template, reference AS ref,
				(SELECT dd.id FROM documents dd WHERE dd.reference = docid AND dd.closed = 0 LIMIT 1) AS reference
			FROM cash
			LEFT JOIN documents d ON (docid = d.id)
			LEFT JOIN numberplans ON (numberplanid = numberplans.id)
			WHERE cash.customerid = ? AND d.type IN (?,?) AND d.closed = 0
			GROUP BY docid, cdate, number, template, reference
			HAVING SUM(value) < 0
			ORDER BY cdate DESC LIMIT 10', 'id', array($id, DOC_INVOICE, DOC_CNOTE)))
	{
		foreach($invoicelist as $idx => $row)
		{
			if($row['ref'] && isset($invoicelist[$row['ref']]))
			{
				unset($invoicelist[$idx]);
				continue;
			}
			
			$invoicelist[$idx]['number'] = docnumber($row['number'], $row['template'], $row['cdate']);
			
			// invoice has cnote reference
			if($row['reference'])
			{
				// get cnotes values if those values decreases invoice value
				if($cnotes = $DB->GetAll('SELECT SUM(value) AS value, cdate, number, template
						FROM cash
						LEFT JOIN documents d ON (docid = d.id)
						LEFT JOIN numberplans ON (numberplanid = numberplans.id)
						WHERE reference = ? AND d.closed = 0
						GROUP BY docid, cdate, number, template',
						array($row['id'])))
				{
					$invoicelist[$idx]['number'] .= ' (';
					foreach($cnotes as $cidx => $cnote)
					{
						$invoicelist[$idx]['number'] .= docnumber($cnote['number'], $cnote['template'], $cnote['cdate']);
						$invoicelist[$idx]['value'] -= $cnote['value'];
						if($cidx < count($cnotes)-1)
							$invoicelist[$idx]['number'] .= ',';
					}
					$invoicelist[$idx]['number'] .= ')';
				}
			}
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
$SESSION->restore('receiptregid', $receipt['regid']);
$SESSION->restore('receipttype', $receipt['type']);
$SESSION->restore('receiptadderror', $error);

$cashreglist = $DB->GetAllByKey('SELECT id, name FROM cashregs ORDER BY name', 'id');

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch($action)
{
	case 'init':

		$oldreg = $receipt['regid'];
    		unset($receipt);
    		unset($contents);
    		unset($customer);
    		unset($error);

		// get default receipt's numberplanid and next number
		$receipt['regid'] = isset($_GET['regid']) ? $_GET['regid'] : $oldreg;
		$receipt['type'] = isset($_GET['type']) ? $_GET['type'] : (isset($_POST['type']) ? $_POST['type'] : 0);
		$receipt['customerid'] = isset($_GET['customerid']) ? $_GET['customerid'] : 0;

		// when registry is not selected but we've got only one registry in database
		if(!$receipt['regid'] && count($cashreglist) == 1)
			$receipt['regid'] = key($cashreglist);

		if(!$receipt['regid'] || !$receipt['type'])
		{
			break;
		}
		
		$receipt['cdate'] = time();
		
		if($receipt['type'] == 'in')
		{
			$receipt['numberplanid'] = $DB->GetOne('SELECT in_numberplanid FROM cashregs WHERE id=?', array($receipt['regid']));
		}	
		elseif($receipt['type'] == 'out')
		{
			$receipt['numberplanid'] = $DB->GetOne('SELECT out_numberplanid FROM cashregs WHERE id=?', array($receipt['regid']));
			if($DB->GetOne('SELECT SUM(value) FROM receiptcontents WHERE regid = ?', array($receipt['regid']))<=0)
				$error['regid'] = trans('There is no cash in selected registry!');
		}
		
		if($receipt['numberplanid'])
			if(strpos($DB->GetOne('SELECT template FROM numberplans WHERE id=?', array($receipt['numberplanid'])), '%I')!==FALSE)
				$receipt['extended'] = TRUE;

		if(!isset($error) && $receipt['customerid'] && $LMS->CustomerExists($receipt['customerid']))
		{
			$customer = $LMS->GetCustomer($receipt['customerid']);
			$customer['groups'] = $LMS->CustomergroupGetForCustomer($receipt['customerid']);
			if(!isset($CONFIG['receipts']['show_notes']) || !chkconfig($CONFIG['receipts']['show_notes']))
				unset($customer['notes']);
			
			// niezatwierdzone dokumenty klienta
			if(isset($CONFIG['receipts']['show_documents_warning']) && chkconfig($CONFIG['receipts']['show_documents_warning']))
				if($DB->GetOne('SELECT COUNT(*) FROM documents WHERE customerid = ? AND closed = 0 AND type < 0', array($receipt['customerid'])))
				{
					if($CONFIG['receipts']['documents_warning'])
						$customer['docwarning'] = $CONFIG['receipts']['documents_warning'];
					else
						$customer['docwarning'] = trans('Customer has got unconfirmed documents!');
				}
		}
	break;

	case 'setreg':

    		unset($receipt);
    		unset($contents);
    		unset($customer);
    		unset($error);

		// get default receipt's numberplanid and next number
		$receipt = ($_POST['receipt']) ? $_POST['receipt'] : NULL;
		$receipt['customerid'] = isset($_POST['customerid']) ? $_POST['customerid'] : 0;
		$receipt['type'] = isset($receipt['type']) ? $receipt['type'] : $_POST['type'];
		
		if(!$receipt['regid'])
			$error['regid'] = trans('Registry not selected!');

		if($DB->GetOne('SELECT rights FROM cashrights WHERE userid=? AND regid=?', array($AUTH->id, $receipt['regid']))<=1)
			$error['regid'] = trans('You have no write rights to selected registry!');

		if(isset($error)) break;
		
		$receipt['cdate'] = time();
		
		if($receipt['type'] == 'in')
			$receipt['numberplanid'] = $DB->GetOne('SELECT in_numberplanid FROM cashregs WHERE id=?', array($receipt['regid']));
		else
		{
			$receipt['numberplanid'] = $DB->GetOne('SELECT out_numberplanid FROM cashregs WHERE id=?', array($receipt['regid']));
			if( $DB->GetOne('SELECT SUM(value) FROM receiptcontents WHERE regid = ?', array($receipt['regid']))<=0)
				$error['regid'] = trans('There is no cash in selected registry!');
		}
		
		if($receipt['numberplanid'])
			if(strpos($DB->GetOne('SELECT template FROM numberplans WHERE id=?', array($receipt['numberplanid'])), '%I')!==FALSE)
				$receipt['extended'] = TRUE;
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
			
			$sum = 0;
			if($contents)
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
	
		if(isset($_POST['marks']))
		{
			unset($error['nocash']);
		
			$cash = $DB->GetOne('SELECT SUM(value) FROM receiptcontents WHERE regid = ?', array($receipt['regid']));
			
			foreach($_POST['marks'] as $id)
			{
				$row = $DB->GetRow('SELECT SUM(value) AS value, number, cdate, template, documents.type AS type,
						    (SELECT dd.id FROM documents dd WHERE dd.reference = docid AND dd.closed = 0 LIMIT 1) AS reference
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

				if($row['reference'] && $receipt['type']=='in')
				{
					// get cnotes values if those values decreases invoice value
					if($cnotes = $DB->GetAll('SELECT SUM(value) AS value, docid, cdate, number, template
						FROM cash
						LEFT JOIN documents d ON (docid = d.id)
						LEFT JOIN numberplans ON (numberplanid = numberplans.id)
						WHERE reference = ? AND d.closed = 0
						GROUP BY docid, cdate, number, template',
						array($id)))
					{
						$itemdata['description'] .= ' (';
						foreach($cnotes as $cidx => $cnote)
						{
							$itemdata['description'] .= docnumber($cnote['number'], $cnote['template'], $cnote['cdate']);
							$itemdata['value'] -= $cnote['value'];
							$itemdata['references'][] = $cnote['docid'];
							if($cidx < count($cnotes)-1)
								$itemdata['description'] .= ',';
						}
						$itemdata['description'] .= ')';
					}
				}
				
				if($receipt['type'] != 'in')
				{
					// sprawdzamy czy mamy tyle kasy w kasie ;)
					$sum = 0;
					if($contents)
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
		$oldcid = $customer['id'];
		unset($receipt); 
		unset($customer);
		unset($error);

		if($receipt = $_POST['receipt'])
			foreach($receipt as $key => $val)
				$receipt[$key] = $val;
		
		//$receipt['customerid'] = $_POST['customerid'];
		$receipt['type'] = isset($_POST['type']) ? $_POST['type'] : $oldtype;

		if($receipt['regid'] != $oldreg || !$receipt['numberplanid'])
		{
			if($receipt['type'] == 'in')
				$receipt['numberplanid'] = $DB->GetOne('SELECT in_numberplanid FROM cashregs WHERE id=?', array($receipt['regid']));
			else
				$receipt['numberplanid'] = $DB->GetOne('SELECT out_numberplanid FROM cashregs WHERE id=?', array($receipt['regid']));
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

		if($receipt['cdate'] && !isset($receipt['cdatewarning']))
		{
			$maxdate = $DB->GetOne('SELECT MAX(cdate) FROM documents 
						WHERE type = ? AND numberplanid = ?', array(DOC_RECEIPT, $receipt['numberplanid']));

			if($receipt['cdate'] < $maxdate)
			{
				$error['cdate'] = trans('Last date of receipt settlement is $0. If sure, you want to write receipt with date of $1, then click "Submit" again.',date('Y/m/d H:i', $maxdate), date('Y/m/d H:i', $receipt['cdate']));
				$receipt['cdatewarning'] = 1;
			}
		}

		if($receipt['number'])
		{
			if(!eregi('^[0-9]+$', $receipt['number']))
				$error['number'] = trans('Receipt number must be integer!');
			elseif($LMS->DocumentExists($receipt['number'], DOC_RECEIPT, $receipt['numberplanid'], $receipt['cdate']))
				$error['number'] = trans('Receipt number $0 already exists!', $receipt['number']);
		}

		if($receipt['numberplanid'] && !isset($receipt['extnumber']))
			if(strpos($DB->GetOne('SELECT template FROM numberplans WHERE id=?', array($receipt['numberplanid'])), '%I')!==FALSE)
				$receipt['extended'] = TRUE;

		$rights = $DB->GetOne('SELECT rights FROM cashrights WHERE regid=? AND userid=?', array($receipt['regid'], $AUTH->id));
		
		switch($receipt['o_type'])
		{
			case 'customer': if(($rights & 2)!=2) $rightserror = true; break; 
			case 'move': if(($rights & 4)!=4) $rightserror = true; break; 
			case 'advance': if(($rights & 8)!=8) $rightserror = true; break; 
			case 'other': if(($rights & 16)!=16) $rightserror = true; break;
		}	

		if(!$receipt['regid'])
			$error['regid'] = trans('Registry not selected!');
		elseif(isset($rightserror))	
			$error['regid'] = trans('You don\'t have permission to add receipt in selected cash registry!');

		if($receipt['o_type'] != 'customer')
		{
			$receipt['customerid'] = 0;
			
			switch($receipt['o_type'])
			{
				 case 'advance':
					if(trim($receipt['adv_name']) == '')
						$error['adv_name'] = trans('Target is required!');
				break;
				case 'other':
					if(trim($receipt['other_name']) == '')
						$error['other_name'] = trans('Target is required!');
				break;
			}
			
			if(!isset($error))
				$receipt['selected'] = TRUE;
			break;
		}
		
		if(isset($_GET['customerid']) && $_GET['customerid'] != '')
			$cid = intval($_GET['customerid']);
		else
			$cid = isset($_POST['customerid']) ? intval($_POST['customerid']) : 0;

		$receipt['customerid'] = $cid;
		
		if(!isset($error) && $cid)
			if($LMS->CustomerExists($cid))
			{
				if($receipt['type'] == 'out')
				{
					$balance = $LMS->GetCustomerBalance($cid);
					if( $balance<0 )
						$error['customerid'] = trans('Selected customer is in debt for $0!', moneyf($balance*-1));
				}

				if(!isset($error))
				{
					$customer = $LMS->GetCustomer($cid);
					$customer['groups'] = $LMS->CustomergroupGetForCustomer($cid);
					if(!isset($CONFIG['receipts']['show_notes']) || !chkconfig($CONFIG['receipts']['show_notes']))
						unset($customer['notes']);
					
					// niezatwierdzone dokumenty klienta
					if(isset($CONFIG['receipts']['show_documents_warning']) && chkconfig($CONFIG['receipts']['show_documents_warning']))
						if($DB->GetOne('SELECT COUNT(*) FROM documents WHERE customerid = ? AND closed = 0 AND type < 0', array($cid)))
						{
							if($CONFIG['receipts']['documents_warning'])
								$customer['docwarning'] = $CONFIG['receipts']['documents_warning'];
							else
								$customer['docwarning'] = trans('Customer has got unconfirmed documents!');
						}
					
					// remove positions if customer was changed
					if($oldcid != $customer['id'])
						unset($contents);
				}
			}
			
		if(!isset($error) && isset($customer))
			$receipt['selected'] = TRUE;
	break;

	case 'save':

		if($contents && $customer)
		{
			$DB->BeginTrans();
			$DB->LockTables('documents');
		
			if(!$receipt['number'])
				$receipt['number'] = $LMS->GetNewDocumentNumber(DOC_RECEIPT, $receipt['numberplanid'], $receipt['cdate']);
			else
			{
				if(!eregi('^[0-9]+$', $receipt['number']))
					$error['number'] = trans('Receipt number must be integer!');
				elseif($LMS->DocumentExists($receipt['number'], DOC_RECEIPT, $receipt['numberplanid'], $receipt['cdate']))
					$error['number'] = trans('Receipt number $0 already exists!', $receipt['number']);

				if($error)
					$receipt['number'] = $LMS->GetNewDocumentNumber(DOC_RECEIPT, $receipt['numberplanid'], $receipt['cdate']);
			}
		
			$DB->Execute('INSERT INTO documents (type, number, extnumber, numberplanid, cdate, customerid, userid, name, address, zip, city, closed)
					VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)',
					array(	DOC_RECEIPT,
						$receipt['number'],
						isset($receipt['extnumber']) ? $receipt['extnumber'] : '',
						$receipt['numberplanid'],
						$receipt['cdate'],
						$customer['id'],
						$AUTH->id,
						$customer['customername'],
						$customer['address'],
						$customer['zip'],
						$customer['city']
						));
						
			$rid = $DB->GetOne('SELECT id FROM documents 
						WHERE type=? AND number=? AND cdate=?', 
						array(DOC_RECEIPT, $receipt['number'], $receipt['cdate'])); 
			
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
				
				if(isset($item['docid']))
					$DB->Execute('UPDATE documents SET closed=1 WHERE id=?', array($item['docid']));
				if(isset($item['references']))
					foreach($item['references'] as $ref)
						$DB->Execute('UPDATE documents SET closed=1 WHERE id=?', array($ref));
			}

			$DB->UnLockTables();		
			$DB->CommitTrans();
			
			$print = TRUE;
		}
		elseif($contents && ($receipt['o_type'] == 'other' || $receipt['o_type'] == 'advance'))
		{
			$DB->BeginTrans();

			if(!$receipt['number'])
				$receipt['number'] = $LMS->GetNewDocumentNumber(DOC_RECEIPT, $receipt['numberplanid'], $receipt['cdate']);
			else
			{
				if(!eregi('^[0-9]+$', $receipt['number']))
					$error['number'] = trans('Receipt number must be integer!');
				elseif($LMS->DocumentExists($receipt['number'], DOC_RECEIPT, $receipt['numberplanid'], $receipt['cdate']))
					$error['number'] = trans('Receipt number $0 already exists!', $receipt['number']);
				
				if($error)
					$receipt['number'] = $LMS->GetNewDocumentNumber(DOC_RECEIPT, $receipt['numberplanid'], $receipt['cdate']);
			}
		
			$DB->Execute('INSERT INTO documents (type, number, extnumber, numberplanid, cdate, userid, name, closed)
					VALUES(?, ?, ?, ?, ?, ?, ?, ?)',
					array(	DOC_RECEIPT,
						$receipt['number'],
						isset($receipt['extnumber']) ? $receipt['extnumber'] : '',
						$receipt['numberplanid'],
						$receipt['cdate'],
						$AUTH->id,
						$receipt['o_type'] == 'advance' ? $receipt['adv_name'] : $receipt['other_name'],
						$receipt['o_type'] == 'advance' ? 0 : 1
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

					$DB->Execute('INSERT INTO cash (time, type, docid, itemid, value, comment, userid)
						VALUES(?, 1, ?, ?, ?, ?, ?)', 
						array($receipt['cdate'],
							$rid, 
							$iid, 
							$value, 
							$item['description'],
							$AUTH->id,
						));
			}
		
			$DB->CommitTrans();
			
			$print = TRUE;
		}
		
		if(isset($print))
		{
			$SESSION->remove('receiptcontents');
			$SESSION->remove('receiptcustomer');
			$SESSION->remove('receipt');
			$SESSION->remove('receiptadderror');

			if(isset($_GET['print']))
				$SESSION->redirect('?m=receiptlist&receipt='.$rid.(isset($_GET['which']) ? '&which='.$_GET['which'] : '').'&regid='.$receipt['regid'].'#'.$rid);
			else
				$SESSION->redirect('?m=receiptlist&regid='.$receipt['regid'].'#'.$rid);
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

			if(!$receipt['number'])
				$receipt['number'] = $LMS->GetNewDocumentNumber(DOC_RECEIPT, $receipt['numberplanid'], $receipt['cdate']);
			else
			{
				if(!eregi('^[0-9]+$', $receipt['number']))
					$error['number'] = trans('Receipt number must be integer!');
				elseif($LMS->DocumentExists($receipt['number'], DOC_RECEIPT, $receipt['numberplanid'], $receipt['cdate']))
					$error['number'] = trans('Receipt number $0 already exists!', $receipt['number']);
				
				if($error)
					$receipt['number'] = $LMS->GetNewDocumentNumber(DOC_RECEIPT, $receipt['numberplanid'], $receipt['cdate']);
			}
			
			// cash-out
			$description = trans('Moving assets to registry $0',$DB->GetOne('SELECT name FROM cashregs WHERE id=?', array($dest)));
			
			$DB->Execute('INSERT INTO documents (type, number, extnumber, numberplanid, cdate, userid, name, closed)
					VALUES(?, ?, ?, ?, ?, ?, \'\', 1)',
					array(	DOC_RECEIPT,
						$receipt['number'],
						isset($receipt['extnumber']) ? $receipt['extnumber'] : '',
						$receipt['numberplanid'],
						$receipt['cdate'],
						$AUTH->id
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

			$DB->Execute('INSERT INTO documents (type, number, numberplanid, cdate, userid, closed)
					VALUES(?, ?, ?, ?, ?, 1)',
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
			
			if(isset($_GET['print']))
				$SESSION->redirect('?m=receiptlist&receipt='.$rid.(isset($_GET['which']) ? '&which='.$_GET['which'] : '').'&regid='.$receipt['regid'].'#'.$rid);
			else
				$SESSION->redirect('?m=receiptlist&regid='.$receipt['regid'].'#'.$rid);
		}
	break;

}

$SESSION->save('receipt', $receipt);
$SESSION->save('receiptregid', $receipt['regid']);
$SESSION->save('receipttype', $receipt['type']);
$SESSION->save('receiptcontents', isset($contents) ? $contents : NULL);
$SESSION->save('receiptcustomer', isset($customer) ? $customer : NULL);
$SESSION->save('receiptadderror', isset($error) ? $error : NULL);

if($action != '')
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

$invoicelist = array();

if(isset($list))
	if($contents)
		foreach($list as $idx => $row)
		{
			$i = 0;
			foreach($contents as $item)
				if($row['id'] == $item['docid'])
				{
					$i = 1;
					break;
				}
			if(!$i)
				$invoicelist[$idx] = $row;
		}
	else
		$invoicelist = $list;

if(!isset($CONFIG['phpui']['big_networks']) || !chkconfig($CONFIG['phpui']['big_networks']))
{
        $SMARTY->assign('customerlist', $LMS->GetCustomerNames());
}

$SMARTY->assign('invoicelist', $invoicelist);
$SMARTY->assign('rights', $DB->GetOne('SELECT rights FROM cashrights WHERE userid=? AND regid=?', array($AUTH->id, $receipt['regid'])));
$SMARTY->assign('cashreglist', $cashreglist);
$SMARTY->assign('cashregcount', sizeof($cashreglist));
$SMARTY->assign('contents', $contents);
$SMARTY->assign('customer', $customer);
$SMARTY->assign('receipt', $receipt);
$SMARTY->assign('error', $error);
$SMARTY->display('receiptadd.html');

?>
