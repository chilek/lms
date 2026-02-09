<?php
$this->DB->BeginTrans();

$this->DB->Execute('UPDATE documents
	SET comment = invcomment
	WHERE type IN (1,6);');
$this->DB->Execute("UPDATE documents set comment = NULL where comment = ''");
$this->DB->Execute('ALTER TABLE documents CHANGE invcomment invcomment_tbd varchar(50)');

$this->DB->Execute('UPDATE stck_dbinfo SET keyvalue = ? WHERE keytype = ?', array('2026020901', 'dbversion'));

$this->DB->CommitTrans();

?>
