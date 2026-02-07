<?php
$this->DB->BeginTrans();

$this->DB->Execute('ALTER TABLE stck_stock ADD sold tinyint(1) NOT NULL DEFAULT 0 AFTER pricesell');

$this->DB->Execute('UPDATE stck_stock
	SET sold = 1
	WHERE pricesell IS NOT NULL AND leavedate > 0');

$this->DB->Execute('UPDATE stck_dbinfo SET keyvalue = ? WHERE keytype = ?', array('2016041401', 'dbversion'));

$this->DB->CommitTrans();
?>
