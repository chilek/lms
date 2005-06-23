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

function GetReceipt($id)
{
	global $CONFIG, $DB;
	
	if($receipt = $DB->GetRow('SELECT * FROM documents 
				WHERE type = 2 AND id = ?', array($id)))
	{
		$receipt['contents'] = $DB->GetAll('SELECT * FROM receiptcontents WHERE docid = ? ORDER BY itemid', array($id));
		
		foreach($receipt['contents'] as $row)
			$receipt['total'] += $row['value'];
		$receipt['totalg'] = ($receipt['total']*100 - ((int) $receipt['total'])*100);
		$ntempl = $CONFIG['receipts']['number_template'];
		$ntempl = str_replace('%N',$receipt['number'],$ntempl);
		$ntempl = str_replace('%M',date('m',$receipt['cdate']),$ntempl);
		$ntempl = str_replace('%Y',date('Y',$receipt['cdate']),$ntempl);
		$receipt['number'] = $ntempl;
		
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
	$layout['pagetitle'] = trans('Cash Receipts');
	
	foreach($_POST['marks'] as $markid => $junk)
		if($junk)
			$ids[] = $markid;

	if($_GET['cash'])
	{
		foreach($ids as $cashid)
			if($rid = $DB->GetOne('SELECT docid FROM cash, documents WHERE docid = documents.id AND documents.type = 2 AND cash.id = ?', array($cashid)))
				$idsx[] = $rid;
		$ids = array_unique((array)$idsx);
	}

	sort($ids);

	$SMARTY->display('clearheader.html');
	$SMARTY->assign('type', $_GET['which']);
	
	foreach($ids as $idx => $receiptid)
	{
		if($receipt = GetReceipt($receiptid))
		{
			$SMARTY->assign('receipt',$receipt);
			$SMARTY->display($CONFIG['receipts']['template_file']);
		}
	}
	$SMARTY->display('clearfooter.html');
}
elseif($receipt = GetReceipt($_GET['id']))
{
	$layout['pagetitle'] = trans('Cash Receipt No. $0', $receipt['number']);
	
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
