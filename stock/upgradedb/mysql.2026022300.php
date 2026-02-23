<?php
$this->DB->BeginTrans();

$this->DB->Execute("DELETE FROM uiconfig WHERE section = 'phpui' AND var IN ('quicksearch_stck_warranty','quicksearch_stck_price')");

$this->DB->Execute('UPDATE stck_dbinfo SET keyvalue = ? WHERE keytype = ?', array('2026022300', 'dbversion'));

$this->DB->CommitTrans();

?>
