<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 */

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');

// Document types
define('DOC_INVOICE', 1);
define('DOC_RECEIPT', 2);
define('DOC_CNOTE', 3);
define('DOC_DNOTE', 5);
define('DOC_INVOICE_PRO', 6);

$this->BeginTrans();

$this->Execute("ALTER TABLE cash ADD COLUMN currency varchar(3)");
$this->Execute("ALTER TABLE cash ADD COLUMN currencyvalue decimal(17,10) DEFAULT 1.0");
$this->Execute("ALTER TABLE tariffs ADD COLUMN currency varchar(3)");
$this->Execute("ALTER TABLE tariffs DROP INDEX name");
$this->Execute("ALTER TABLE tariffs ADD UNIQUE KEY tariffs_name_key (name, value, currency, period)");
$this->Execute("ALTER TABLE assignments ADD COLUMN currency varchar(3)");
$this->Execute("ALTER TABLE liabilities ADD COLUMN currency varchar(3)");
$this->Execute("ALTER TABLE documents ADD COLUMN currency varchar(3)");
$this->Execute("ALTER TABLE documents ADD COLUMN currencyvalue decimal(17,10) DEFAULT 1.0");

$this->Execute("UPDATE cash SET currencyvalue = ?", array(1.0));
$this->Execute(
    "UPDATE documents SET currencyvalue = ? WHERE type IN ?",
    array(1.0, array(DOC_INVOICE, DOC_INVOICE_PRO, DOC_CNOTE, DOC_DNOTE, DOC_RECEIPT))
);

foreach (array('cash', 'tariffs', 'assignments', 'liabilities') as $sql_table) {
    $this->Execute("UPDATE " . $sql_table . " SET currency = ?", array($_currency));
}
$this->Execute(
    "UPDATE documents SET currency = ? WHERE type IN ?",
    array($_currency, array(DOC_INVOICE, DOC_INVOICE_PRO, DOC_CNOTE, DOC_DNOTE, DOC_RECEIPT))
);

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2019102500', 'dbversion'));

$this->CommitTrans();
