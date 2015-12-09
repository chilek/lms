<?php
/**
 * @author Maciej_Wawryk
 */

$id = intval($_GET['id']);

if($id && $_GET['is_sure'] == '1') {
    if(isset($_GET['recover'])) {
	$LMS->DB->Execute('UPDATE documents SET cancelled = FALSE WHERE id = ?', array($id));
	$document = $LMS->DB->GetRow('SELECT customerid, cdate FROM documents WHERE id = ?', array($id));
	$invoices = $LMS->DB->GetAll('SELECT * FROM invoicecontents WHERE docid = ?', array($id));
	$itemid = 1;
	foreach ($invoices as $invoice) {
	    $LMS->AddBalance(array(
		'time' => $document['cdate'],
		'value' => $invoice['value'] * $invoice['count'] * -1,
		'taxid' => $invoice['taxid'],
		'customerid' => $document['customerid'],
		'comment' => $invoice['description'],
		'docid' => $id,
		'itemid' => $itemid
	    ));
	    $itemid += 1;
	}
    } 
    else {
	$LMS->DB->Execute('UPDATE documents SET cancelled = TRUE WHERE id = ?', array($id));
	$LMS->DB->Execute('DELETE FROM cash WHERE docid = ?', array($id));
    }
}

$SESSION->redirect('?m=invoicelist');

?>
