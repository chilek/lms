<?php
$this->DB->BeginTrans();

$this->DB->Execute('ALTER TABLE `stck_stock` CHANGE `deleted` `deleted` BOOLEAN NOT NULL DEFAULT FALSE;');

$this->DB->Execute('UPDATE stck_dbinfo SET keyvalue = ? WHERE keytype = ?', array('2016040301', 'dbversion'));

$this->DB->CommitTrans();
?>
