<?php
$this->DB->BeginTrans();

$this->DB->Execute('ALTER TABLE stck_manufacturers ADD INDEX (name);');

$this->DB->Execute("ALTER TABLE stck_products CHANGE quantity quantity INT(11) NOT NULL DEFAULT '1'");

$this->DB->Execute('UPDATE stck_dbinfo SET keyvalue = ? WHERE keytype = ?', array('2016052001', 'dbversion'));

$this->DB->CommitTrans();
?>
