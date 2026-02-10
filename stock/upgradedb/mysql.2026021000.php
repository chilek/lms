<?php
$this->DB->BeginTrans();

$this->DB->Execute('UPDATE invoicecontents ic
	INNER JOIN stck_gtuassignments sga ON ic.docid = sga.icdocid AND ic.itemid = sga.icitemid
	SET taxcategory = sga.gtuid
	WHERE ic.taxcategory IS NULL');

$this->DB->Execute('UPDATE stck_dbinfo SET keyvalue = ? WHERE keytype = ?', array('2026021001', 'dbversion'));

$this->DB->CommitTrans();

?>
