<?php
$this->DB->BeginTrans();

$this->DB->Execute('INSERT INTO uiconfig(section, var, value, description, type) VALUES(?, ?, ?, ?, ?)', array('phpui', 'quicksearch_phone', '1', 'Wyświetlaj w górnym pasku szybkie wyszukiwanie telefon', '1'));

$this->DB->Execute('UPDATE stck_dbinfo SET keyvalue = ? WHERE keytype = ?', array('2017032500', 'dbversion'));

$this->DB->CommitTrans();
?>
