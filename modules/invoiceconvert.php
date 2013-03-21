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
 *  $Id: invoiceconvert.php, 2012/12/01 23:32:16 Sylwester Kondracki Exp $
 */


$error = NULL;
$docid = (isset($_GET['docid']) ? intval($_GET['docid']) : $_POST['docid']);
$cid = (isset($_GET['cid']) ? intval($_GET['cid']) : $_POST['cid']);

$parents = (isset($_GET['parent']) ? $_GET['parent'] : $_POST['parents']);
$invoice = $LMS->GetInvoiceContent($docid);
$layout['pagetitle'] = trans('Pro Forma Invoice Convertion No. $a',docnumber($invoice['number'],$invoice['template'],$invoice['cdate']));
$currtime = time();
$invoice['cdate'] = $invoice['sdate'] = $currtime;
$invoice['type'] = DOC_INVOICE;
$customer = $LMS->GetCustomer($invoice['customerid'], true);

if (!$invoice['numberplanid'] = $DB->GetOne('SELECT n.id FROM numberplans n JOIN numberplanassignments a ON (n.id = a.planid)
				WHERE n.doctype = ? AND n.isdefault = 1 AND a.divisionid = ?', array($invoice['type'], $customer['divisionid'])))
    $invoice['numberplanid'] = $DB->GetOne('SELECT id FROM numberplans WHERE doctype = ? AND isdefault = 1', array($invoice['type']));

$numberplanlist = $LMS->GetNumberPlans($invoice['type'],date('Y/m', $invoice['cdate']));

if (isset($_POST['invoiceconvert']))
{
    $data = $_POST['invoiceconvert'];
    
    if($data['sdate']) {
	list($syear, $smonth, $sday) = explode('/', $data['sdate']);

	if(checkdate($smonth, $sday, $syear)) {
		$data['sdate'] = mktime(date('G', $currtime), date('i', $currtime), date('s', $currtime), $smonth, $sday, $syear);
		$scurrmonth = $smonth;
	} else {
		$error['sdate'] = trans('Incorrect date format!');
		$data['sdate'] = $currtime;
	}
    }
    else
	$data['sdate'] = $currtime;

    if($data['cdate']) {
	list($year, $month, $day) = explode('/', $data['cdate']);
	if(checkdate($month, $day, $year)) {
		$data['cdate'] = mktime(date('G', $currtime), date('i', $currtime), date('s', $currtime), $month, $day, $year);
		$currmonth = $month;
	} else {
		$error['cdate'] = trans('Incorrect date format!');
		$data['cdate'] = $currtime;
	}
    }
    
    if ($data['cdate'] && !isset($data['cdatawarning'])) {
	$maxdate = $DB->GetOne('SELECT MAX(cdate) FROM documents WHERE type = ? AND numberplanid = ?', 
					array($invoice['type'], $invoice['numberplanid']));

	if($invoice['cdate'] < $maxdate) {
		$error['cdate'] = trans('Last date of invoice settlement is $a. If sure, you want to write invoice with date of $b, then click "Submit" again.',
		date('Y/m/d H:i', $maxdate), date('Y/m/d H:i', $invoice['cdate']));
		$data['cdatewarning'] = 1;
	}
    } elseif (!$data['cdate']) $data['cdate'] = $currtime;
    
    if (empty($data['paytime'])) {
	if($customer['paytime'] != -1)
		$data['paytime'] = $customer['paytime'];
	elseif (($paytime = $DB->GetOne('SELECT inv_paytime FROM divisions 
		WHERE id = ?', array($customer['divisionid']))) !== NULL)
		$data['paytime'] = $paytime;
	else
		$data['paytime'] = $CONFIG['invoices']['paytime'];
    }
    
    
    $invoice['sdate'] = $data['sdate'];
    $invoice['cdate'] = $data['cdate'];
    $invoice['paytime'] = $data['paytime'];
    $invoice['paytype'] = $data['paytype'];
    
    if (!$error)
    {
	$contents = $invoice['content'];
	
	$DB->BeginTrans();
	$DB->LockTables(array('documents','cash','invoicecontents','numberplans','divisions'));
	
	$old = $DB->GetRow('SELECT * FROM documents WHERE id=? LIMIT 1;',array($docid));

	$invoice['number'] = $LMS->GetNewDocumentNumber(DOC_INVOICE,$invoice['numberplanid'],$invoice['cdate']);
	
	$DB->Execute('INSERT INTO documents (type, number, numberplanid, extnumber, cdate, sdate, customerid, userid, divisionid, name, 
			address, zip, city, countryid, ten, ssn, paytime, paytype, closed, reference, reason) 
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ;',
			array(DOC_INVOICE, $invoice['number'], $invoice['numberplanid'], $old['extnumber'], $invoice['cdate'], $invoice['sdate'], $old['customerid'],
			    $AUTH->id, $old['divisionid'], $old['name'], $old['address'], $old['zip'], $old['city'], $old['countryid'], $old['ten'], $old['ssn'], 
			    $invoice['paytime'], $invoice['paytype'], 0, $old['reference'], $old['reason'])
		);

	$newid = $DB->GetLastInsertId('documents');
	
	$old = $DB->GetAll('SELECT * FROM invoicecontents WHERE docid = ?',array($docid));
	
	for ( $i = 0; $i < sizeof($old); $i++ )
	    $DB->Execute('INSERT INTO invoicecontents (docid, itemid, value, pdiscount, vdiscount, taxid, prodid, content, count, description, tariffid) 
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ;',
			array(
			    $newid, $old[$i]['itemid'], $old[$i]['value'], $old[$i]['pdiscount'], $old[$i]['vdiscount'], 
			    $old[$i]['taxid'], $old[$i]['prodid'], $old[$i]['content'], $old[$i]['count'], $old[$i]['description'], $old[$i]['tariffid']
			)
		);

	$DB->Execute('UPDATE cash SET time = ? , docid = ? WHERE docid = ?',array($invoice['cdate'], $newid, $docid));
	
	$DB->Execute('UPDATE documents SET closed = ?, reference = ? WHERE id = ? ;',array(1, $newid, $docid));
	
	$DB->UnLockTables();
	$DB->CommitTrans();
	
	if ($parents == 'customerinfo')
	    $SESSION->redirect("?m=customerinfo&id=".$cid);
	else
	    $SESSION->redirect("?m=invoicelist");
    }
}
$SMARTY->assign('docid',$docid);
$SMARTY->assign('cid',$cid);
$SMARTY->assign('parents',$parents);
$SMARTY->assign('numberplanlist',$numberplanlist);
$SMARTY->assign('invoice',$invoice);
$SMARTY->display('invoiceconvert.html');

?>