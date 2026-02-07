<?php
$this->DB->BeginTrans();

$this->DB->Exec('CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `stck_vstockcount` AS (select `s`.`productid` AS `productid`,count(`s`.`id`) AS `scount` from `stck_stock` `s` where isnull(`s`.`pricesell`) group by `s`.`productid`);');

$this->DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016012501', 'dbversion'));

$this->DB->Execute('CREATE TABLE `stck_dbinfo` (
  `keytype` varchar(255) NOT NULL,
  `keyvalue` varchar(255) NOT NULL,
  PRIMARY KEY (`keytype`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

$this->DB->Execute('INSERT INTO stck_dbinfo VALUES(?, ?)', array('dbversion', '2016040100'));

$this->DB->CommitTrans();
?>
