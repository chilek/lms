<?php
$this->DB->BeginTrans();

$this->DB->Execute('INSERT INTO uiconfig(section, var, value, description, type) VALUES(?, ?, ?, ?, ?)', array('phpui', 'quicksearch_customer', '1', 'Wyświetlaj w górnym pasku szybkie wyszukiwanie klienta', '1'));

$this->DB->Execute('INSERT INTO uiconfig(section, var, value, description, type) VALUES(?, ?, ?, ?, ?)', array('phpui', 'quicksearch_node', '1', 'Wyświetlaj w górnym pasku szybkie wyszukiwanie komputera', '1'));

$this->DB->Execute('INSERT INTO uiconfig(section, var, value, description, type) VALUES(?, ?, ?, ?, ?)', array('phpui', 'quicksearch_ticket', '1', 'Wyświetlaj w górnym pasku szybkie wyszukiwanie zgłoszenia', '1'));

$this->DB->Execute('INSERT INTO uiconfig(section, var, value, description, type) VALUES(?, ?, ?, ?, ?)', array('phpui', 'quicksearch_account', '1', 'Wyświetlaj w górnym pasku szybkie wyszukiwanie konta', '1'));

$this->DB->Execute('INSERT INTO uiconfig(section, var, value, description, type) VALUES(?, ?, ?, ?, ?)', array('phpui', 'quicksearch_document', '1', 'Wyświetlaj w górnym pasku szybkie wyszukiwanie dokumentu', '1'));

$this->DB->Execute('INSERT INTO uiconfig(section, var, value, description, type) VALUES(?, ?, ?, ?, ?)', array('phpui', 'quicksearch_stck_price', '1', 'Wyświetlaj w górnym pasku szybkie wyszukiwanie ceny produktu', '1'));

$this->DB->Execute('INSERT INTO uiconfig(section, var, value, description, type) VALUES(?, ?, ?, ?, ?)', array('phpui', 'quicksearch_stck_warranty', '1', 'Wyświetlaj w górnym pasku szybkie wyszukiwanie gwarancji', '1'));

$this->DB->Execute('UPDATE stck_dbinfo SET keyvalue = ? WHERE keytype = ?', array('2016041502', 'dbversion'));

$this->DB->CommitTrans();
?>
