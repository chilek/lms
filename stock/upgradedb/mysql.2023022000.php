<?php
$this->DB->BeginTrans();

$this->DB->Execute('ALTER TABLE documents ADD COLUMN invcomment varchar(50) DEFAULT NULL');

$this->DB->Execute('ALTER TABLE cash DROP DOLUMN stockid_tbd');

$this->DB->Execute('ALTER TABLE invoicecontents DROP COLUMN stockid_tbd int(11) DEFAULT NULL;');

$this->DB->Execute('UPDATE stck_dbinfo SET keyvalue = ? WHERE keytype = ?', array('2023022000', 'dbversion'));

$this->DB->CommitTrans();

?>
