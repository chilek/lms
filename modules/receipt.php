<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2011 LMS Developers
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

function GetReceipt($id)
{
	global $CONFIG, $DB;
	
	if($receipt = $DB->GetRow('SELECT documents.*, users.name AS user, template,
					div.name AS d_name, div.address AS d_address,
					div.zip AS d_zip, div.city AS d_city
				FROM documents 
				LEFT JOIN users ON (userid = users.id)
				LEFT JOIN numberplans ON (numberplanid = numberplans.id)
				LEFT JOIN customers c ON (documents.customerid = c.id)
				LEFT JOIN divisions div ON (div.id = c.divisionid)
				WHERE documents.type = 2 AND documents.id = ?', array($id)))
	{
		$receipt['contents'] = $DB->GetAll('SELECT * FROM receiptcontents WHERE docid = ? ORDER BY itemid', array($id));
		$receipt['total'] = 0;
		
		foreach($receipt['contents'] as $row)
			$receipt['total'] += $row['value'];
			
		$receipt['number'] = docnumber($receipt['number'], $receipt['template'], $receipt['cdate'], $receipt['extnumber']);
		
		if($receipt['total'] < 0)
		{
			$receipt['type'] = 'out';
			// change values sign
			foreach($receipt['contents'] as $idx => $row)
				$receipt['contents'][$idx]['value'] *= -1;
			$receipt['total'] *= -1;
		}
		else
			$receipt['type'] = 'in';

		$receipt['totalg'] = round($receipt['total']*100 - ((int) $receipt['total'])*100);
		
		return $receipt;
	}
}

if(strtolower($CONFIG['receipts']['type']) == 'pdf')
{
    include('receipt_pdf.php');
    $SESSION->close();
    die;
}

header('Content-Type: '.$CONFIG['receipts']['content_type']);
if(!empty($CONFIG['receipts']['attachment_name']))
	header('Content-Disposition: attachment; filename='.$CONFIG['receipts']['attachment_name']);

if(isset($_GET['print']) && $_GET['print'] == 'cached' && sizeof($_POST['marks']))
{
        $SESSION->restore('rlm', $rlm);
	$SESSION->remove('rlm');
		
	if(sizeof($_POST['marks']))
	        foreach($_POST['marks'] as $id => $mark)
	                $rlm[$id] = $mark;
	if(sizeof($rlm))
		foreach($rlm as $mark)
			$ids[] = $mark;

	if(!$ids)
	{
		$SESSION->close();
		die;
	}
								
	if(!empty($_GET['cash']))
	{
		foreach($ids as $cashid)
			if($rid = $DB->GetOne('SELECT docid FROM cash, documents WHERE docid = documents.id AND documents.type = 2 AND cash.id = ?', array($cashid)))
				$idsx[] = $rid;
		$ids = array_unique((array)$idsx);
	}

	sort($ids);

	$layout['pagetitle'] = trans('Cash Receipts');
	$SMARTY->display('receiptheader.html');
	$SMARTY->assign('type', !empty($_GET['which']) ? $_GET['which'] : '');
	
	$i = 0;
	$count = sizeof($ids);
	foreach($ids as $idx => $receiptid)
	{
		if($receipt = GetReceipt($receiptid))
		{
			$i++;
		        if($i == $count) $receipt['last'] = TRUE;
			$receipt['first'] = $i > 1 ? FALSE : TRUE;

			$SMARTY->assign('receipt',$receipt);
			$SMARTY->display($CONFIG['receipts']['template_file']);
		}
	}
	$SMARTY->display('clearfooter.html');
}
elseif($receipt = GetReceipt($_GET['id']))
{
	$regid = $DB->GetOne('SELECT DISTINCT regid FROM receiptcontents WHERE docid=?', array($_GET['id']));
	if( !$DB->GetOne('SELECT rights FROM cashrights WHERE userid=? AND regid=?', array($AUTH->id, $regid)))
	{
    		$SMARTY->display('noaccess.html');
	        $SESSION->close();
		die;
	}	

	$layout['pagetitle'] = trans('Cash Receipt No. $0', $receipt['number']);
	
	$receipt['last'] = TRUE;
	$receipt['first'] = TRUE;
	$SMARTY->assign('type', isset($_GET['which']) ? $_GET['which'] : NULL);
	$SMARTY->assign('receipt',$receipt);
	$SMARTY->display('receiptheader.html');
	$SMARTY->display($CONFIG['receipts']['template_file']);
	$SMARTY->display('clearfooter.html');
}
else
{
	$SESSION->redirect('?m=receiptlist');
}

?>
