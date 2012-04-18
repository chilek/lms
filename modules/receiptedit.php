<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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
	if($DB->GetOne('SELECT rights FROM cashrights WHERE userid=? AND regid=?', array($AUTH->id, $regid))<256)
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
	$sum = 0;
	
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
	{
		$receipt['o_type'] = 'customer';
	}
	elseif($receipt['closed'])
	{
		$receipt['o_type'] = 'other';
		$receipt['other_name'] = $receipt['name'];
	}
	elseif(!$receipt['closed'])
	{
		$receipt['o_type'] = 'advance';
		$receipt['adv_name'] = $receipt['name'];
	}
	
	if($receipt['customerid'])
	{
		$customer = $LMS->GetCustomer($receipt['customerid'], true);
    		$customer['groups'] = $LMS->CustomergroupGetForCustomer($receipt['customerid']);
		if(empty($CONFIG['receipts']['show_notes']) || !chkconfig($CONFIG['receipts']['show_notes']))
                        unset($customer['notes']);

		// niezatwierdzone dokumenty klienta
		if(isset($CONFIG['receipts']['show_documents_warning']) && chkconfig($CONFIG['receipts']['show_documents_warning']))
			if($DB->GetOne('SELECT COUNT(*) FROM documents WHERE customerid = ? AND closed = 0 AND type < 0', array($receipt['customerid'])))
			{
				if(!empty($CONFIG['receipts']['documents_warning']))
					$customer['docwarning'] = $CONFIG['receipts']['documents_warning'];
				else
					$customer['docwarning'] = trans('Customer has got unconfirmed documents!');
			}
		
		// jesli klient posiada zablokowane komputery poinformujmy
		// o tym kasjera, moze po wplacie trzeba bedzie zmienic ich status
		if(isset($CONFIG['receipts']['show_nodes_warning']) && chkconfig($CONFIG['receipts']['show_nodes_warning']))
		        if($DB->GetOne('SELECT COUNT(*) FROM nodes WHERE ownerid = ? AND access = 0', array($receipt['customerid'])))
			{
			        if(!empty($CONFIG['receipts']['nodes_warning']))
			        	$customer['nodeswarning'] = $CONFIG['receipts']['nodes_warning'];
				else
					$customer['nodeswarning'] = trans('Customer has got disconnected nodes!');
			}
		// jesli klient posiada komputery przypisane do wybranych grup..., u mnie
    		// komputery zadluzonych dodawane sa do grupy "zadluzenie"
		if(!empty($CONFIG['receipts']['show_nodegroups_warning']))
		{
		        $list = preg_split("/\s+/", $CONFIG['receipts']['show_nodegroups_warning']);
		        if($DB->GetOne('SELECT COUNT(*) FROM nodes n
			                JOIN nodegroupassignments a ON (n.id = a.nodeid)
					JOIN nodegroups g ON (g.id = a.nodegroupid)
					WHERE n.ownerid = ? AND UPPER(g.name) IN (UPPER(\''
					.implode("'),UPPER('", $list).'\'))',
					array($receipt['customerid'])))
			{
			        if(!empty($CONFIG['receipts']['nodegroups_warning']))
			                $customer['nodegroupswarning'] = $CONFIG['receipts']['nodegroups_warning'];
			        else
			        	$customer['nodegroupswarning'] = trans('Customer has got nodes in groups: <b>$a</b>!',
			                            $CONFIG['receipts']['show_nodegroups_warning']);
			}
		}
	}
	    
	if($receipt['numberplanid'] && !$receipt['extnumber'])
    		if(strpos($receipt['template'], '%I')!==FALSE)
	                $receipt['extended'] = TRUE;
	
	$receipt['selected'] = TRUE;
	
	$SESSION->save('receipt', $receipt);
	$SESSION->save('receiptcontents', $contents);
	$SESSION->save('receiptcustomer', isset($customer) ? $customer : NULL);
	$SESSION->save('receiptediterror', $error);
}

// receipt positions adding with double click protection
function additem(&$content, $item)
{
        for($i=0, $x=sizeof($content); $i<$x; $i++)
	        if($content[$i]['value'] == $item['value']
			&& $content[$i]['description'] == $item['description']
			&& $content[$i]['posuid'] > $item['posuid'] - 1)
		{
			break;
		}
	
	if($i == $x)
	        $content[] = $item;
}

$SESSION->restore('receiptcontents', $contents);
$SESSION->restore('receiptcustomer', $customer);
$SESSION->restore('receipt', $receipt);
$SESSION->restore('receiptediterror', $error);

$receipt['titlenumber'] = docnumber($receipt['number'], $receipt['template'], $receipt['cdate'], 
			    isset($receipt['extnumber']) ? $receipt['extnumber'] : '');

if($receipt['type']=='in')
	$layout['pagetitle'] = trans('Cash-in Receipt Edit: $a', $receipt['titlenumber']);
else
	$layout['pagetitle'] = trans('Cash-out Receipt Edit: $a', $receipt['titlenumber']);

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch($action)
{
	case 'additem':

		$itemdata = r_trim($_POST);
		$itemdata['value'] = round((float) str_replace(',','.',$itemdata['value']),2);
		// workaround for PHP 4.3.10 bug
		$itemdata['value'] = str_replace(',','.',$itemdata['value']);
		$itemdata['posuid'] = (string) getmicrotime();
	
		if($itemdata['value'] && $itemdata['description'])
			additem($contents, $itemdata);
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
		$oldclosed = $receipt['closed'];
		
		unset($receipt);
		unset($customer);
		$error = NULL;
		
		if($receipt = $_POST['receipt'])
			foreach($receipt as $key => $val)
				$receipt[$key] = $val;
		
		$receipt['customerid'] = $_POST['customerid'];
		$receipt['template'] = $oldtemplate;
		$receipt['id'] = $id;
		$receipt['closed'] = $oldclosed;
		
		if($receipt['regid'] != $oldreg)
		{
		        if($receipt['type'] == 'in')
				$receipt['numberplanid'] = $DB->GetOne('SELECT in_numberplanid FROM cashregs WHERE id=?', array($receipt['regid']));
			else
				$receipt['numberplanid'] = $DB->GetOne('SELECT out_numberplanid FROM cashregs WHERE id=?', array($receipt['regid']));
			
			$receipt['number'] = 0;
		}
		
		if($receipt['cdate'])
		{
			list($year, $month, $day) = explode('/',$receipt['cdate']);
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
		{
			if($receipt['cdate'] && !$receipt['cdatewarning'])
			{
				$maxdate = $DB->GetOne('SELECT MAX(cdate) FROM documents WHERE type = ? AND numberplanid = ?', array(DOC_RECEIPT, $receipt['numberplanid']));
				if($receipt['cdate'] < $maxdate)
				{
					$error['cdate'] = trans('Last date of receipt settlement is $a. If sure, you want to write receipt with date of $b, then click "Submit" again.',date('Y/m/d H:i', $maxdate), date('Y/m/d H:i', $receipt['cdate']));
					$receipt['cdatewarning'] = 1;
				}
			}
		}
		else // przywracamy pierwotna godzine utworzenia dokumentu
			$receipt['cdate'] = $oldcdate;
			
		if(!$receipt['number'])
			$receipt['number'] = $LMS->GetNewDocumentNumber(DOC_RECEIPT, $receipt['numberplanid'], $receipt['cdate']);
		else
		{
			if(!preg_match('/^[0-9]+$/', $receipt['number']))
				$error['number'] = trans('Receipt number must be integer!');
			elseif($receipt['number']!=$oldnumber)
				if($LMS->DocumentExists($receipt['number'], DOC_RECEIPT, $receipt['numberplanid'], $receipt['cdate']))
					$error['number'] = trans('Receipt number $a already exists!', $receipt['number']);
		}

		if($receipt['numberplanid'] && !$receipt['extnumber'])
    			if(strpos($DB->GetOne('SELECT template FROM numberplans WHERE id=?', array($receipt['numberplanid'])), '%I')!==FALSE)
	            		$receipt['extended'] = TRUE;

		if($receipt['o_type']=='other')
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
			
			if(trim($receipt['o_name']) == '')
                                $error['o_name'] = trans('Target is required!');

			if(!$error)
			        $receipt['selected'] = TRUE;
			break;
		}
		
		$cid = !empty($_GET['customerid']) ? $_GET['customerid'] : $_POST['customerid'];
		
		if(!$cid)
			$cid = $oldcid;
		
		if(!isset($error))
			if($LMS->CustomerExists(($cid)))
			{
				$customer = $LMS->GetCustomer($cid, true);
	                        $customer['groups'] = $LMS->CustomergroupGetForCustomer($customer['id']);
		        	
				if(empty($CONFIG['receipts']['show_notes']) || !chkconfig($CONFIG['receipts']['show_notes']))
				        unset($customer['notes']);
				
				// niezatwierdzone dokumenty klienta
				if(isset($CONFIG['receipts']['show_nodes_warning']) && chkconfig($CONFIG['receipts']['show_documents_warning']))
					if($DB->GetOne('SELECT COUNT(*) FROM documents WHERE customerid = ? AND closed = 0 AND type < 0', array($customer['id'])))
					{
						if(!empty($CONFIG['receipts']['documents_warning']))
							$customer['docwarning'] = $CONFIG['receipts']['documents_warning'];
						else
							$customer['docwarning'] = trans('Customer has got unconfirmed documents!');
					}
				
				// jesli klient posiada zablokowane komputery poinformujmy
				// o tym kasjera, moze po wplacie trzeba bedzie zmienic ich status
				if(isset($CONFIG['receipts']['show_nodes_warning']) && chkconfig($CONFIG['receipts']['show_nodes_warning']))
				        if($DB->GetOne('SELECT COUNT(*) FROM nodes WHERE ownerid = ? AND access = 0', array($customer['id'])))
					{
					        if(!empty($CONFIG['receipts']['nodes_warning']))
					        	$customer['nodeswarning'] = $CONFIG['receipts']['nodes_warning'];
						else
							$customer['nodeswarning'] = trans('Customer has got disconnected nodes!');
					}

				// jesli klient posiada komputery przypisane do wybranych grup..., u mnie
                    		// komputery zadluzonych dodawane sa do grupy "zadluzenie"
			        if(!empty($CONFIG['receipts']['show_nodegroups_warning']))
				{
				        $list = preg_split("/\s+/", $CONFIG['receipts']['show_nodegroups_warning']);
				        if($DB->GetOne('SELECT COUNT(*) FROM nodes n
				                JOIN nodegroupassignments a ON (n.id = a.nodeid)
						JOIN nodegroups g ON (g.id = a.nodegroupid)
						WHERE n.ownerid = ? AND UPPER(g.name) IN (UPPER(\''
						.implode("'),UPPER('", $list).'\'))',
						array($customer['id'])))
					{
					        if(!empty($CONFIG['receipts']['nodegroups_warning']))
					                $customer['nodegroupswarning'] = $CONFIG['receipts']['nodegroups_warning'];
					        else
					        	$customer['nodegroupswarning'] = trans('Customer has got nodes in groups: <b>$a</b>!',
					                            $CONFIG['receipts']['show_nodegroups_warning']);
					}
				}
				
				$receipt['selected'] = TRUE;
			}

	break;
	case 'save':

		if($contents && $customer)
		{
			$DB->BeginTrans();
			$DB->LockTables('documents');

			// delete old receipt 
			$DB->Execute('DELETE FROM documents WHERE id = ?', array($receipt['id']));
		
			// re-add receipt 
			$DB->Execute('INSERT INTO documents (type, number, extnumber, numberplanid, cdate, customerid, userid, name, address, zip, city, closed)
					VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
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
						$customer['city'],
						$receipt['closed']
						));
			$DB->UnLockTables();
						
			$rid = $DB->GetLastInsertId('documents');

			// delete old receipt content and assignments
			$DB->Execute('DELETE FROM receiptcontents WHERE docid = ?', array($receipt['id']));
			$DB->Execute('DELETE FROM cash WHERE docid = ?', array($receipt['id']));
			
			$iid = 0;
			foreach($contents as $item)
			{
				$iid++;

				if($receipt['type'] == 'in')
				        $value = str_replace(',','.',$item['value']);
				else
				        $value = str_replace(',','.',$item['value']*-1);

				$DB->Execute('INSERT INTO receiptcontents (docid, itemid, value, description, regid)
						VALUES(?, ?, ?, ?, ?)', 
						array($rid, 
							$iid, 
							$value, 
							$item['description'],
							$receipt['regid']
						));

				$DB->Execute('INSERT INTO cash (type, time, docid, itemid, value, comment, userid, customerid)
					        VALUES(1, ?, ?, ?, ?, ?, ?, ?)', 
						array($receipt['cdate'],
							$rid, 
							$iid, 
							$value,
							$item['description'],
							$AUTH->id,
							$customer['id']
						));
			}

			$DB->CommitTrans();
		}
		elseif($contents && ($receipt['o_type'] == 'other' || $receipt['o_type'] == 'advance'))
		{
		        $DB->BeginTrans();
			$DB->LockTables('documents');
			
			// delete old receipt 
			$DB->Execute('DELETE FROM documents WHERE id = ?', array($receipt['id']));
			
			$DB->Execute('INSERT INTO documents (type, number, extnumber, numberplanid, cdate, userid, name, closed)
			    		VALUES(?, ?, ?, ?, ?, ?, ?, ?)',
			                array(  DOC_RECEIPT,
					        $receipt['number'],
						$receipt['extnumber'] ? $receipt['extnumber'] : '',
						$receipt['numberplanid'],
						$receipt['cdate'],
						$AUTH->id,
						$receipt['o_type'] == 'advance' ? $receipt['adv_name'] : $receipt['other_name'],
						$receipt['closed']
					));
			$DB->UnLockTables();
						
			$rid = $DB->GetLastInsertId('documents');

			// delete old receipt content and assignments
			$DB->Execute('DELETE FROM receiptcontents WHERE docid = ?', array($receipt['id']));
			$DB->Execute('DELETE FROM cash WHERE docid = ?', array($receipt['id']));
			
			$iid = 0;
			foreach($contents as $item)
			{
				$iid++;
				
				if($receipt['type'] == 'in')
				        $value = str_replace(',','.',$item['value']);
				else
				        $value = str_replace(',','.',$item['value']*-1);
				
				$DB->Execute('INSERT INTO receiptcontents (docid, itemid, value, description, regid)
						VALUES(?, ?, ?, ?, ?)', 
						array($rid, 
							$iid, 
							$value, 
							$item['description'],
							$receipt['regid']
					));
					    
				$DB->Execute('INSERT INTO cash (type, time, docid, itemid, value, comment, userid)
					        VALUES(1, ?, ?, ?, ?, ?, ?)', 
						array($receipt['cdate'],
							$rid, 
							$iid, 
							$value,
							$item['description'],
							$AUTH->id,
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
		
		if(isset($_GET['print']))
			$SESSION->save('receiptprint', array('receipt' => $rid,
		                'which' => (isset($_GET['which']) ? $_GET['which'] : '')));

		$SESSION->redirect('?m=receiptlist&regid='.$receipt['regid'].'#'.$rid);
	break;
}

$SESSION->save('receipt', $receipt);
$SESSION->save('receiptcontents', $contents);
$SESSION->save('receiptcustomer', $customer);
$SESSION->save('receiptediterror', $error);

if($action != '')
{
	$SESSION->redirect('?m=receiptedit');
}

$cashreglist = $DB->GetAllByKey('SELECT id, name FROM cashregs ORDER BY name', 'id');

if(!isset($CONFIG['phpui']['big_networks']) || !chkconfig($CONFIG['phpui']['big_networks']))
{
        $SMARTY->assign('customerlist', $LMS->GetCustomerNames());
}

$SMARTY->assign('cashreglist', $cashreglist);
$SMARTY->assign('cashregcount', sizeof($cashreglist));
$SMARTY->assign('contents', $contents);
$SMARTY->assign('customer', $customer);
$SMARTY->assign('receipt', $receipt);
$SMARTY->assign('error', $error);
$SMARTY->display('receiptedit.html');

?>
