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
Localisation::initDefaultCurrency();
$_currency = Localisation::getDefaultCurrency();

setlocale(LC_NUMERIC, 'C');

// Document types
if (!defined('DOC_INVOICE')) {
    define('DOC_INVOICE', 1);
}
if (!defined('DOC_RECEIPT')) {
    define('DOC_RECEIPT', 2);
}
if (!defined('DOC_CNOTE')) {
    define('DOC_CNOTE', 3);
}
if (!defined('DOC_DNOTE')) {
    define('DOC_DNOTE', 5);
}
if (!defined('DOC_INVOICE_PRO')) {
    define('DOC_INVOICE_PRO', 6);
}


$this->Execute("
    ALTER TABLE cash ADD COLUMN currency varchar(3);
    ALTER TABLE cash ADD COLUMN currencyvalue numeric(17,10) DEFAULT 1.0;
    ALTER TABLE tariffs ADD COLUMN currency varchar(3);
    ALTER TABLE tariffs DROP CONSTRAINT tariffs_name_key;
    ALTER TABLE tariffs ADD CONSTRAINT tariffs_name_key UNIQUE (name, value, currency, period);
    ALTER TABLE assignments ADD COLUMN currency varchar(3);
    ALTER TABLE liabilities ADD COLUMN currency varchar(3);
    ALTER TABLE documents ADD COLUMN currency varchar(3);
    ALTER TABLE documents ADD COLUMN currencyvalue numeric(17,10) DEFAULT 1.0;
");

$this->Execute("UPDATE cash SET currencyvalue = ?, currency = ?", array(1.0, $_currency));
$this->Execute(
    "UPDATE documents SET currencyvalue = ?, currency = ? WHERE type IN ?",
    array(1.0, $_currency, array(DOC_INVOICE, DOC_INVOICE_PRO, DOC_CNOTE, DOC_DNOTE, DOC_RECEIPT))
);

foreach (array('tariffs', 'assignments', 'liabilities') as $sql_table) {
    $this->Execute("UPDATE " . $sql_table . " SET currency = ?", array($_currency));
}
