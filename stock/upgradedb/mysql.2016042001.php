<?php
$this->DB->BeginTrans();

$this->DB->Execute('ALTER TABLE invoicecontents CHANGE stockid stockid_tbd int(11)');

$this->DB->Execute('ALTER TABLE cash CHANGE stockid stockid_tbd int(11)');

$this->DB->Execute('UPDATE stck_dbinfo SET keyvalue = ? WHERE keytype = ?', array('2016042001', 'dbversion'));

$this->DB->CommitTrans();
?>
