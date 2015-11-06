<?php

define('EMAIL', 8);
define('EMAIL_INVOICE', 16);

$this->BeginTrans();
    
$this->Execute("UPDATE customercontacts SET type = ? WHERE customerid IN (SELECT id FROM customers WHERE einvoice = 1 AND invoicenotice = 1) AND type & ? = ?",
            array(EMAIL_INVOICE, EMAIL, EMAIL));

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2015110600', 'dbversion'));

$this->CommitTrans();

?>
