<?php
$this->DB->BeginTrans();

$this->DB->Execute('ALTER TABLE `stck_products`  ADD `gprice` DECIMAL(9) NULL DEFAULT NULL  AFTER `name`,  ADD `srp` DECIMAL(9) NULL DEFAULT NULL  AFTER `gprice`;');

$this->DB->Execute('UPDATE stck_dbinfo SET keyvalue = ? WHERE keytype = ?', array('2018030300', 'dbversion'));

$this->DB->CommitTrans();

?>
