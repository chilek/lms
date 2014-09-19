<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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
	global $DB;

	if ($receipt = $DB->GetRow('SELECT d.*, u.name AS user, n.template,
					ds.name AS d_name, ds.address AS d_address,
					ds.zip AS d_zip, ds.city AS d_city
				FROM documents d
				LEFT JOIN users u ON (d.userid = u.id)
				LEFT JOIN numberplans n ON (d.numberplanid = n.id)
				LEFT JOIN customers c ON (d.customerid = c.id)
				LEFT JOIN divisions ds ON (ds.id = c.divisionid)
				WHERE d.type = 2 AND d.id = ?', array($id)))
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

if(strtolower(ConfigHelper::getConfig('receipts.type')) == 'pdf')
{
    include('receipt_pdf.php');
    $SESSION->close();
    die;
}

header('Content-Type: '.ConfigHelper::getConfig('receipts.content_type'));
$attachment_name = ConfigHelper::getConfig('receipts.attachment_name');
if(!empty($attachment_name))
	header('Content-Disposition: attachment; filename='.$attachment_name);

if(isset($_GET['print']) && $_GET['print'] == 'cached' && sizeof($_POST['marks']))
{
        $SESSION->restore('rlm', $rlm);
	$SESSION->remove('rlm');

	if(sizeof($_POST['marks']))
	        foreach($_POST['marks'] as $id => $mark)
	                $rlm[$id] = $mark;
	if(sizeof($rlm))
		foreach($rlm as $mark)
			$ids[] = intval($mark);

	if(empty($ids))
	{
		$SESSION->close();
		die;
	}

	if(!empty($_GET['cash']))
	{
		$ids = $DB->GetCol('SELECT DISTINCT docid FROM cash, documents
			WHERE docid = documents.id AND documents.type = 2
			    AND cash.id IN ('.implode(',', $ids).')');
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
			$SMARTY->display(ConfigHelper::getConfig('receipts.template_file'));
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

	$layout['pagetitle'] = trans('Cash Receipt No. $a', $receipt['number']);

	$receipt['last'] = TRUE;
	$receipt['first'] = TRUE;
	$SMARTY->assign('type', isset($_GET['which']) ? $_GET['which'] : NULL);
	$SMARTY->assign('receipt',$receipt);
	$SMARTY->display('receiptheader.html');
	$SMARTY->display(ConfigHelper::getConfig('receipts.template_file'));
	$SMARTY->display('clearfooter.html');
}
else
{
	$SESSION->redirect('?m=receiptlist');
}

?>
