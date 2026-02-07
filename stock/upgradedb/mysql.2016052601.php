<?php
$this->DB->BeginTrans();

$this->DB->Execute("ALTER TABLE stck_stock ADD INDEX (productid)");

$this->DB->Execute('ALTER TABLE stck_stock ADD FOREIGN KEY (productid) REFERENCES stck_products(id) ON DELETE RESTRICT ON UPDATE RESTRICT;');

$this->DB->Execute('UPDATE stck_dbinfo SET keyvalue = ? WHERE keytype = ?', array('2016052601', 'dbversion'));

$this->DB->CommitTrans();
?>
