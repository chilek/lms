<?php
/**
 * @author Maciej_Wawryk
 */

if($doc = $DB->GetRow('SELECT number, cdate, template, extnumber, paytime, paytype
	FROM documents 
	LEFT JOIN numberplans ON (numberplanid = numberplans.id)
	WHERE documents.id = ?', array($_GET['id']))){
		$doc['ntempl'] = docnumber($doc['number'], $doc['template'], $doc['cdate'], $doc['extnumber']);
		$doc['pdate'] = $doc['cdate'] + ($doc['paytime'] * 86400);
		$doc['paytypename'] = $PAYTYPES[$doc['paytype']];
		$SMARTY->assign('invoice', $doc);
		$SMARTY->display('invoice/invoiceinfoshort.html');
}

?>