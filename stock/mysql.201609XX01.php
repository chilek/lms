<?php
$this->DB->BeginTrans();

$this->DB->Execute("CREATE TABLE stck_quantityleft (
	id INT NOT NULL AUTO_INCREMENT,
	stockid INT NOT NULL,
	quantityid INT NOT NULL,
	ql INT NOT NULL,
	PRIMARY KEY (id),
	INDEX (stockid),
	INDEX (quantityid)
	) ENGINE = InnoDB");

$this->DB->Execute("ALTER TABLE stck_quantityleft
	ADD FOREIGN KEY (stockid) REFERENCES stck_stock(id)
		ON DELETE RESTRICT ON UPDATE RESTRICT");

$this->DB->Execute("ALTER TABLE stck_quantityleft
	ADD FOREIGN KEY (quantityid) REFERENCES stck_quantities(id)
		ON DELETE RESTRICT ON UPDATE RESTRICT");

$this->DB->Execute('ALTER TABLE stck_warehouses ADD production BOOLEAN NOT NULL DEFAULT FALSE AFTER commerce');

$this->DB->Execute('UPDATE stck_dbinfo SET keyvalue = ? WHERE keytype = ?', array('201606XX01', 'dbversion'));

$this->DB->CommitTrans();
?>
