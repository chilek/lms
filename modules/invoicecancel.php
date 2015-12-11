<?php
/**
 * @author Maciej_Wawryk
 */

$id = intval($_GET['id']);

if($id && $_GET['is_sure'] == '1') {
    if(isset($_GET['recover'])) {
	$DB->Execute('UPDATE documents SET cancelled = 0 WHERE id = ?', array($id));
	$document = $DB->GetRow('SELECT customerid, cdate FROM documents WHERE id = ?', array($id));
	$invoices = $DB->GetAll('SELECT * FROM invoicecontents WHERE docid = ?', array($id));
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
	if ($SYSLOG) {
		$args = array(
		    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $document['id'],
		    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $document['customerid'],
		    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => $AUTH->id
		);
		$SYSLOG->AddMessage(SYSLOG_RES_DOC, SYSLOG_OPER_UPDATE, $args, 
			array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] , $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER])
		);
	}
    } 
    else {
	$DB->Execute('UPDATE documents SET cancelled = 1 WHERE id = ?', array($id));
	$DB->Execute('DELETE FROM cash WHERE docid = ?', array($id));
	$document = $DB->GetRow('SELECT * FROM documents WHERE id = ?', array($id));
	if ($SYSLOG) {
		$args = array(
		    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $document['id'],
		    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $document['customerid'],
		    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => $AUTH->id
		);
		$SYSLOG->AddMessage(SYSLOG_RES_DOC, SYSLOG_OPER_UPDATE, $args, 
			array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] , $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER])
		);
	}
    }
}

$SESSION->redirect('?m=invoicelist');

?>
