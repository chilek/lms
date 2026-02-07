<?php
$this->DB->BeginTrans();

$this->DB->Execute('DROP VIEW stck_vstockcount');

$this->DB->Execute('CREATE VIEW stck_vstockcount AS
	SELECT s.productid AS productid, COUNT(s.id) AS scount, s.warehouseid AS warehouseid
	FROM stck_stock s
	WHERE s.sold = 0
	GROUP BY s.warehouseid, s.productid
	ORDER BY s.productid');

$this->DB->Execute('UPDATE stck_dbinfo SET keyvalue = ? WHERE keytype = ?', array('2016041501', 'dbversion'));

$this->DB->CommitTrans();
?>
