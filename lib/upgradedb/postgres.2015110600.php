<?php

$this->BeginTrans();

$customers = $this->GetAll("SELECT * FROM customers WHERE einvoice = 1 AND invoicenotice = 1");

foreach($customers as $customer){
    $row = $this->GetRow("SELECT * FROM customercontacts WHERE customerid = ? AND type & ? = ?", array(intval($customer['id']), 8, 8));
    $this->Execute("UPDATE customercontacts SET type = ? WHERE id = ?", array(16 ,intval($row['id'])));
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2015110600', 'dbversion'));

$this->CommitTrans();

?>
