<?php
$this->DB->BeginTrans();

$this->DB->Execute('UPDATE cash
		SET stockid = NULL
		WHERE stockid NOT IN (
			SELECT id FROM stck_stock
		)');

$this->DB->Execute('CREATE TABLE stck_cashassignments(
		id int not null auto_increment,
		cashid int not null,
		stockid int not null,
		rnitem bool not null default 0,
		PRIMARY KEY(id),
		INDEX (cashid),
		INDEX (stockid),
		FOREIGN KEY (cashid)
			REFERENCES cash (id)
			ON UPDATE CASCADE ON DELETE CASCADE,
		FOREIGN KEY (stockid)
			REFERENCES stck_stock(id)
			ON UPDATE CASCADE ON DELETE RESTRICT
	) ENGINE=InnoDB');

$this->DB->Execute('INSERT INTO stck_cashassignments(cashid, stockid)
	SELECT id, stockid FROM cash where stockid is not null');

$this->DB->Execute('UPDATE invoicecontents
	SET stockid = NULL
	WHERE stockid NOT IN (
    		SELECT id FROM stck_stock
    	)');

$this->DB->Execute('CREATE TABLE stck_invoicecontentsassignments(
	id int not null auto_increment,
	icdocid int not null,
	icitemid smallint(6) not null,
	stockid int not null,
	PRIMARY KEY(id),
	INDEX (icdocid,icitemid),
	INDEX (stockid),
	FOREIGN KEY (icdocid,icitemid)
		REFERENCES invoicecontents(docid, itemid)
		ON UPDATE CASCADE ON DELETE CASCADE,
	FOREIGN KEY (stockid)
		REFERENCES stck_stock(id)
		ON UPDATE CASCADE ON DELETE RESTRICT
	) ENGINE=InnoDB');

$this->DB->Execute('INSERT INTO stck_invoicecontentsassignments(icdocid, icitemid, stockid)
        SELECT docid, itemid, stockid FROM invoicecontents where stockid > 0');

$this->DB->Execute('UPDATE stck_cashassignments SET rnitem = 1
	WHERE cashid IN (SELECT c.id
	FROM cash c
	LEFT JOIN stck_stock ss ON ss.id = c.stockid
	WHERE value > 0 AND stockid > 0 AND docid = 0 AND itemid = 0 AND ss.pricebuygross = c.value
	ORDER BY id DESC)');

$this->DB->Execute('DROP TABLE stck_stockassigments');

$this->DB->Execute('UPDATE stck_dbinfo SET keyvalue = ? WHERE keytype = ?', array('2016040101', 'dbversion'));

$this->DB->CommitTrans();
?>
