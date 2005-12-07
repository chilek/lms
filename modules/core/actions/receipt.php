<?php

/*
 * LMS version 1.8-cvs
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

function GetReceipt($id)
{
	global $CONFIG, $DB;
	
	if($receipt = $DB->GetRow('SELECT documents.*, users.name AS user, template
				FROM documents 
				LEFT JOIN users ON (userid = users.id)
				LEFT JOIN numberplans ON (numberplanid = numberplans.id)
				WHERE type = 2 AND documents.id = ?', array($id)))
	{
		$receipt['contents'] = $DB->GetAll('SELECT * FROM receiptcontents WHERE docid = ? ORDER BY itemid', array($id));
		
		foreach($receipt['contents'] as $row)
			$receipt['total'] += $row['value'];
		
		$receipt['totalg'] = ($receipt['total']*100 - ((int) $receipt['total'])*100);
		$receipt['number'] = docnumber($receipt['number'], $receipt['template'], $receipt['cdate']);
		
		return $receipt;
	}
}

if (strtolower($CONFIG['receipts']['type']) == 'pdf')
{
    include('receipt_pdf.php');
    $SESSION->close();
    die;
}

header('Content-Type: '.$CONFIG['receipts']['content_type']);
if($LMS->CONFIG['receipts']['attachment_name'] != '')
	header('Content-Disposition: attachment; filename='.$CONFIG['receipts']['attachment_name']);


if($_GET['print'] == 'cached' && sizeof($_POST['marks']))
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
								
	if($_GET['cash'])
	{
		foreach($ids as $cashid)
			if($rid = $DB->GetOne('SELECT docid FROM cash, documents WHERE docid = documents.id AND documents.type = 2 AND cash.id = ?', array($cashid)))
				$idsx[] = $rid;
		$ids = array_unique((array)$idsx);
	}

	sort($ids);

	$layout['pagetitle'] = trans('Cash Receipts');
	$SMARTY->display('clearheader.html');
	$SMARTY->assign('type', $_GET['which']);
	
	$i = 0;
	$count = sizeof($ids);
	foreach($ids as $idx => $receiptid)
	{
		if($receipt = GetReceipt($receiptid))
		{
			$i++;
		        if($i == $count) $receipt['last'] = TRUE;

			$SMARTY->assign('receipt',$receipt);
			$SMARTY->display($CONFIG['receipts']['template_file']);
		}
	}
	$SMARTY->display('clearfooter.html');
}
elseif($receipt = GetReceipt($_GET['id']))
{
	$layout['pagetitle'] = trans('Cash Receipt No. $0', $receipt['number']);
	
	$receipt['last'] = TRUE;
	$SMARTY->assign('type', $_GET['which']);
	$SMARTY->assign('receipt',$receipt);
	$SMARTY->display('clearheader.html');
	$SMARTY->display($CONFIG['receipts']['template_file']);
	$SMARTY->display('clearfooter.html');
}
else
{
	$SESSION->redirect('?m=receiptlist');
}

?>
