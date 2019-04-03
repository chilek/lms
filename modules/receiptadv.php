<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

	if($DB->GetOne('SELECT rights FROM cashrights WHERE userid=? AND regid=?', array(Auth::GetCurrentUser(), $regid))<256)
	{
	        $SMARTY->display('noaccess.html');
	        $SESSION->close();
	        die;
	}			

	$record = $DB->GetRow('SELECT documents.*, numberplans.template
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

$titlenumber = docnumber(array(
	'number' => $record['number'],
	'template' => $record['template'],
	'cdate' => $record['cdate'],
	'ext_num' => $record['extnumber'],
));
$layout['pagetitle'] = trans('Advance settlement: $a', $titlenumber);

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

	if(isset($receipt['cdate']))
	{
		$cdate = datetime_to_timestamp($receipt['cdate']);
		if(empty($cdate)) {
			$error['cdate'] = trans('Incorrect date format!');
			$receipt['cdate'] = time();
		} else
			$receipt['cdate'] = $cdate;
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
                            		$error['value'] = trans('There is only $a in registry!', money_format($sum));
			}
		}
		
		if($receipt['in_number'])
		{
	    		if(!preg_match('/^[0-9]+$/', $receipt['in_number']))
	            		$error['in_number'] = trans('Receipt number must be integer!');
			elseif($LMS->DocumentExists(array(
					'number' => $receipt['in_number'],
					'doctype' => DOC_RECEIPT,
					'planid' => $in_plan,
					'cdate' => $receipt['cdate'],
				)))
		 		$error['in_number'] = trans('Receipt number $a already exists!', $receipt['in_number']);
		}

		if($receipt['out_number'])
		{
			if(!preg_match('/^[0-9]+$/', $receipt['out_number']))
				$error['out_number'] = trans('Receipt number must be integer!');
			elseif($LMS->DocumentExists(array(
					'number' => $receipt['out_number'],
					'doctype' => DOC_RECEIPT,
					'planid' => $record['numberplanid'],
					'cdate' => $receipt['cdate'],
				)))
				$error['out_number'] = trans('Receipt number $a already exists!', $receipt['out_number']);
		}
	}
	else
	{	
		if($receipt['number'])
		{
			if(!preg_match('/^[0-9]+$/', $receipt['number']))
				$error['number'] = trans('Receipt number must be integer!');
			elseif($LMS->DocumentExists(array(
					'number' => $receipt['number'],
					'doctype' => DOC_RECEIPT,
					'planid' => $in_plan,
					'cdate' => $receipt['cdate'],
				)))
				$error['number'] = trans('Receipt number $a already exists!', $receipt['number']);
		}
	}
	
	if(!$error)
	{
		$DB->BeginTrans();
		$DB->LockTables(array('documents', 'numberplans'));
		
		if($receipt['type'] == 'return')
		{
			if(!$receipt['number'])
				$in_number = $LMS->GetNewDocumentNumber(array(
					'doctype' => DOC_RECEIPT,
					'planid' => $in_plan,
					'cdate' => $receipt['cdate'],
				));
			else
				$in_number = $receipt['number'];
			$in_extnumber = isset($receipt['extnumber']) ? $receipt['extnumber'] : '';
		}
		else
		{
			if(!$receipt['in_number'])
				$in_number = $LMS->GetNewDocumentNumber(array(
					'doctype' => DOC_RECEIPT,
					'planid' => $in_plan,
					'cdate' => $receipt['cdate'],
				));
			else
				$in_number = $receipt['in_number'];
			$in_extnumber = isset($receipt['in_extnumber']) ? $receipt['in_extnumber'] : '';
		}

		$fullnumber = docnumber(array(
			'number' => $in_number,
			'template' => $DB->GetOne('SELECT template FROM numberplans WHERE id = ?', array($in_plan)),
			'cdate' => $receipt['cdate'],
		));

		// add cash-in receipt 
		$DB->Execute('INSERT INTO documents (type, number, extnumber, numberplanid, cdate, userid, name, closed, fullnumber)
					VALUES(?, ?, ?, ?, ?, ?, ?, 1, ?)',
					array(	DOC_RECEIPT,
						$in_number,
						$in_extnumber,
						$in_plan,
						$receipt['cdate'],
						Auth::GetCurrentUser(),
						$record['name'],
						$fullnumber,
						));
						
		$rid = $DB->GetLastInsertId('documents');

		if($receipt['type'] == 'settle')
		{
			// add cash-out receipt
			if(!$receipt['out_number'])
				$receipt['out_number'] = $LMS->GetNewDocumentNumber(array(
					'doctype' => DOC_RECEIPT,
					'planid' => $record['numberplanid'],
					'cdate' => $receipt['cdate'],
				));

			$fullnumber = docnumber(array(
				'number' => $receipt['out_number'],
				'template' => $DB->GetOne('SELECT template FROM numberplans WHERE id = ?', array($record['numberplanid'])),
				'cdate' => $receipt['cdate'],
			));

			$DB->Execute('INSERT INTO documents (type, number, extnumber, numberplanid, cdate, userid, name, closed, fullnumber)
					VALUES(?, ?, ?, ?, ?, ?, ?, 1, ?)',
					array(	DOC_RECEIPT,
						$receipt['out_number'],
						isset($receipt['out_extnumber']) ? $receipt['out_extnumber'] : '',
						$record['numberplanid'],
						$receipt['cdate'],
						Auth::GetCurrentUser(),
						$receipt['name'],
						$fullnumber,
						));
						
			$rid2 = $DB->GetLastInsertId('documents');
		}
		
		$DB->UnLockTables();
			
		$DB->Execute('INSERT INTO receiptcontents (docid, itemid, value, description, regid)
					VALUES(?, 1, ?, ?, ?)',
					array($rid,
						str_replace(',', '.', $record['value'] * -1),
						trans('Advance return').' - '.$titlenumber,
						$regid
					));

		$DB->Execute('INSERT INTO cash (time, type, docid, itemid, value, comment, userid)
					VALUES(?, 1, ?, 1, ?, ?, ?)',
					array($receipt['cdate'],
						$rid,
						str_replace(',', '.', $record['value'] * -1),
						trans('Advance return').' - '.$titlenumber,
						Auth::GetCurrentUser()
					));

		if($receipt['type'] == 'settle')
		{
			$DB->Execute('INSERT INTO receiptcontents (docid, itemid, value, description, regid)
					VALUES(?, 1, ?, ?, ?)',
					array($rid2, 
						str_replace(',', '.', $value * -1),
						$receipt['description'],
						$regid
					));

			$DB->Execute('INSERT INTO cash (time, type, docid, itemid, value, comment, userid)
					VALUES(?, 1, ?, 1, ?, ?, ?)',
					array($receipt['cdate'],
						$rid,
						str_replace(',', '.', $value * -1),
						$receipt['description'],
						Auth::GetCurrentUser()
					));
		}

		// advance status update
		$DB->Execute('UPDATE documents SET closed = 1 WHERE id = ?', array($record['id']));

		$DB->CommitTrans();

		if(isset($_GET['print']))
			$SESSION->save('receiptprint', array('receipt' => $rid, 'receipt2' => $rid2,
                                'which' => (isset($_GET['which']) ? $_GET['which'] : '')));

		$SESSION->redirect('?m=receiptlist&regid='.$regid.'#'.$rid);
	}

	$SMARTY->assign('error', $error);
}

$SMARTY->assign('receipt', $receipt);
$SMARTY->display('receipt/receiptadv.html');

?>
