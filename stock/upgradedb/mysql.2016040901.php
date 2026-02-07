<?php
$this->DB->BeginTrans();

$this->DB->Execute('UPDATE stck_stock s
	JOIN stck_products p
	ON s.productid = p.id
	SET s.groupid = p.groupid
	WHERE s.groupid <> p.groupid');

$this->DB->Execute('CREATE TABLE stck_receivennotesassignment (
	id INT NOT NULL,
	rnid INT NOT NULL,
	cashid INT NOT NULL,
	PRIMARY KEY (id),
	INDEX (rnid),
	INDEX (cashid),
	FOREIGN KEY (cashid)
		REFERENCES cash (id)
		ON UPDATE CASCADE ON DELETE CASCADE,
	FOREIGN KEY (rnid)
		REFERENCES stck_receivenotes (id)
		ON UPDATE CASCADE ON DELETE RESTRICT,
	) ENGINE = InnoDB');

$this->DB->Execute('DROP VIEW stck_vpstock');

$this->DB->Execute('UPDATE stck_dbinfo SET keyvalue = ? WHERE keytype = ?', array('2016040901', 'dbversion'));

$this->DB->CommitTrans();
?>
