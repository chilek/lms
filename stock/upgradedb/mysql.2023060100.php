<?php
$this->DB->BeginTrans();

$this->DB->Execute('CREATE TRIGGER stk_products_update 
	AFTER UPDATE ON stck_products
	FOR EACH ROW
		IF NEW.gtu_id <> OLD.gtu_id THEN
                        UPDATE stck_stock SET stck_stock.gtu_id = NEW.gtu_id WHERE stck_stock.productid = NEW.id AND stck_stock.sold = 0;
                END IF');

$this->DB->Execute('UPDATE stck_dbinfo SET keyvalue = ? WHERE keytype = ?', array('2023060100', 'dbversion'));

$this->DB->CommitTrans();

?>
