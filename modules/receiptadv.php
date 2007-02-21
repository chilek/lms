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

if(isset($_GET['id']))
{
	$id = intval($_GET['id']);
	$regid = $DB->GetOne('SELECT DISTINCT regid FROM receiptcontents WHERE docid=?', array($id));

	if($DB->GetOne('SELECT rights FROM cashrights WHERE userid=? AND regid=?', array($AUTH->id, $regid))<256)
	{
	        $SMARTY->display('noaccess.html');
	        $SESSION->close();
	        die;
	}			

	$record = $DB->GetRow('SELECT documents.*, template 
			    FROM documents 
			    LEFT JOIN numberplans ON (numberplanid = numberplans.id)
			    WHERE documents.id = ? AND type = ? AND closed = 0', 
			    array($id, DOC_RECEIPT));

	if(!$record)
		$SESSION->redirect('?'.$SESSION->get('backto'));
	
	$record['value'] = $DB->GetOne('SELECT SUM(value) FROM receiptcontents 
			    WHERE docid = ?', array($record['id']));

        if(strpos($record['template'], '%I') !== false)
	        $receipt['out_extended'] = true;
	
        if(strpos($DB->GetOne('SELECT template FROM numberplans 
			    WHERE id IN (SELECT in_numberplanid FROM cashregs WHERE id = ?)', array($regid)), '%I') !== false)
		$receipt['in_extended'] = true;
	
	$receipt['id'] = $id;
	$receipt['regid'] = $regid;
}

$titlenumber = docnumber($record['number'], $record['template'], $record['cdate'], $record['extnumber']);
$layout['pagetitle'] = trans('Advance settlement: $0', $titlenumber);

if(isset($_POST['receipt']))
{
	$out_extended = isset($receipt['out_extended']) ? $receipt['out_extended'] : NULL;
	$in_extended = isset($receipt['in_extended']) ? $receipt['in_extended'] : NULL;

	$receipt = $_POST['receipt'];
	
	$receipt['out_extended'] = $out_extended;
	$receipt['in_extended'] = $in_extended;
	$receipt['regid'] = $regid;
	
	$value = f_round($receipt['value']);
	
	if($receipt['type'] == 'return')
		$receipt['cdate'] = $_POST['receiptr']['cdate'];

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
                }
        }
        else
        	$receipt['cdate'] = time();

	$in_plan = $DB->GetOne('SELECT in_numberplanid FROM cashregs WHERE id = ?', array($regid));

	if($receipt['type'] == 'settle')
	{
		if($receipt['description'] == '')
			$error['description'] = trans('Description is required!');

		if($receipt['name'] == '')
			$error['name'] = trans('Recipient name is required!');

		if(!$value)
			$error['value'] = trans('Value is required!');
		else
		{	
			$diff = $value + $record['value'];
			
			if($diff > 0)
			{
				$sum = $DB->GetOne('SELECT SUM(value) FROM receiptcontents WHERE regid = ?', array($regid));
				if($sum < $diff)
                            		$error['value'] = trans('There is only $0 in registry!', money_format($sum));
			}
		}
		
		if($receipt['in_number'])
		{
	    		if(!eregi('^[0-9]+$', $receipt['in_number']))
	            		$error['in_number'] = trans('Receipt number must be integer!');
			elseif($LMS->DocumentExists($receipt['in_number'], DOC_RECEIPT, $in_plan, $receipt['cdate']))
		    		$error['in_number'] = trans('Receipt number $0 already exists!', $receipt['in_number']);
		}

		if($receipt['out_number'])
		{
	    		if(!eregi('^[0-9]+$', $receipt['out_number']))
	            		$error['out_number'] = trans('Receipt number must be integer!');
			elseif($LMS->DocumentExists($receipt['out_number'], DOC_RECEIPT, $record['numberplanid'], $receipt['cdate']))
		    		$error['out_number'] = trans('Receipt number $0 already exists!', $receipt['out_number']);
		}
	}
	else
	{	
		if($receipt['number'])
		{
	    		if(!eregi('^[0-9]+$', $receipt['number']))
	            		$error['number'] = trans('Receipt number must be integer!');
			elseif($LMS->DocumentExists($receipt['number'], DOC_RECEIPT, $in_plan, $receipt['cdate']))
		    		$error['number'] = trans('Receipt number $0 already exists!', $receipt['number']);
		}
	}
	
	if(!$error)
	{
		if($receipt['type'] == 'return')
		{
			if(!$receipt['number'])
				$in_number = $LMS->GetNewDocumentNumber(DOC_RECEIPT, $in_plan, $receipt['cdate']);
			else
				$in_number = $receipt['number'];
			$in_extnumber = isset($receipt['extnumber']) ? $receipt['extnumber'] : '';
		}
		else
		{
			if(!$receipt['in_number'])
				$in_number = $LMS->GetNewDocumentNumber(DOC_RECEIPT, $in_plan, $receipt['cdate']);
			else
				$in_number = $receipt['in_number'];
			$in_extnumber = isset($receipt['in_extnumber']) ? $receipt['in_extnumber'] : '';
		}

		$DB->BeginTrans();

		// add cash-in receipt 
		$DB->Execute('INSERT INTO documents (type, number, extnumber, numberplanid, cdate, userid, name, closed)
					VALUES(?, ?, ?, ?, ?, ?, ?, 1)',
					array(	DOC_RECEIPT,
						$in_number,
						$in_extnumber,
						$in_plan,
						$receipt['cdate'],
						$AUTH->id,
						$record['name']
						));
						
		$rid = $DB->GetOne('SELECT id FROM documents WHERE type=? AND number=? AND cdate=?', 
					array(DOC_RECEIPT, $in_number, $receipt['cdate']));
			
		$DB->Execute('INSERT INTO receiptcontents (docid, itemid, value, description, regid)
					VALUES(?, 1, ?, ?, ?)', 
					array($rid, 
						str_replace(',', '.', $record['value'] * -1), 
						trans('Advance return').' - '.$titlenumber,
						$regid
					));

		if($receipt['type'] == 'settle')
		{
			// add cash-out receipt
			if(!$receipt['out_number'])
				$receipt['out_number'] = $LMS->GetNewDocumentNumber(DOC_RECEIPT, $record['numberplanid'], $receipt['cdate']);

			$DB->Execute('INSERT INTO documents (type, number, extnumber, numberplanid, cdate, userid, name, closed)
					VALUES(?, ?, ?, ?, ?, ?, ?, 1)',
					array(	DOC_RECEIPT,
						$receipt['out_number'],
						isset($receipt['out_extnumber']) ? $receipt['out_extnumber'] : '',
						$record['numberplanid'],
						$receipt['cdate'],
						$AUTH->id,
						$receipt['name']
						));
						
			$rid2 = $DB->GetOne('SELECT id FROM documents WHERE type=? AND number=? AND cdate=?', 
					array(DOC_RECEIPT, $receipt['out_number'], $receipt['cdate']));
			
			$DB->Execute('INSERT INTO receiptcontents (docid, itemid, value, description, regid)
					VALUES(?, 1, ?, ?, ?)', 
					array($rid2, 
						str_replace(',', '.', $value * -1), 
						$receipt['description'],
						$regid
					));
		}

		// advance status update
		$DB->Execute('UPDATE documents SET closed = 1 WHERE id = ?', array($record['id']));
				
		$DB->CommitTrans();

		if(isset($_GET['print']))		
			header('Location: ?m=receiptlist&receipt='.$rid.(isset($rid2) ? '&receipt2='.$rid2 : '').(isset($_GET['which']) ? '&which='.$_GET['which'] : '').'&regid='.$regid.'#'.$rid);
		else
			header('Location: ?m=receiptlist&regid='.$regid.'#'.$rid);
		die;
	}

	$SMARTY->assign('error', $error);
}

$SMARTY->assign('receipt', $receipt);
$SMARTY->display('receiptadv.html');

?>
