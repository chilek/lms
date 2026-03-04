<?php
$this->DB->BeginTrans();

$this->DB->Execute("ALTER TABLE stck_receivenotes ADD ksef_number VARCHAR(40) NULL DEFAULT NULL AFTER number");

$this->DB->Execute('UPDATE stck_dbinfo SET keyvalue = ? WHERE keytype = ?', array('2026030400', 'dbversion'));

$this->DB->CommitTrans();

?>
