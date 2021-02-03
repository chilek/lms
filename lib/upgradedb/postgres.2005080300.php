<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
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
 *  $Id$
 */

$invoice_number_template = $this->GetOne(
    "SELECT value FROM uiconfig WHERE section = ? AND var = ? AND disabled = ?",
    array('invoices', 'number_template', 0)
);
$invoice_monthly_numbering = $this->GetOne(
    "SELECT value FROM uiconfig WHERE section = ? AND var = ? AND disabled = ?",
    array('invoices', 'monthly_numbering', 0)
);
$receipt_number_template = $this->GetOne(
    "SELECT value FROM uiconfig WHERE section = ? AND var = ? AND disabled = ?",
    array('receipts', 'number_template', 0)
);
$receipt_monthly_numbering = $this->GetOne(
    "SELECT value FROM uiconfig WHERE section = ? AND var = ? AND disabled = ?",
    array('receipts', 'monthly_numbering', 0)
);

$this->BeginTrans();

$this->Execute("
    CREATE SEQUENCE numberplans_id_seq;
    CREATE TABLE numberplans (
	id integer DEFAULT nextval('numberplans_id_seq'::text) NOT NULL,
	template varchar(255) DEFAULT '' NOT NULL,
	period smallint DEFAULT 0 NOT NULL,
	doctype integer DEFAULT 0 NOT NULL,
	isdefault smallint DEFAULT 0 NOT NULL,
	PRIMARY KEY (id))
");

$this->Execute(
    "INSERT INTO numberplans (template, period, doctype, isdefault) VALUES(?,?,1,1)",
    array(str_replace('%M', '%m', $invoice_number_template), $invoice_monthly_numbering ? 3 : 5)
);
$this->Execute(
    "INSERT INTO numberplans (template, period, doctype, isdefault) VALUES(?,?,2,1)",
    array(str_replace('%M', '%m', $receipt_number_template), $receipt_monthly_numbering ? 3 : 5)
);

$this->Execute("
    ALTER TABLE documents ADD numberplanid integer;
    UPDATE documents SET numberplanid = 0;
    ALTER TABLE documents ALTER numberplanid SET NOT NULL;
    ALTER TABLE documents ALTER numberplanid SET DEFAULT 0;
    UPDATE documents SET numberplanid = 1 WHERE type = 1;
    UPDATE documents SET numberplanid = 2 WHERE type = 2;
    
    CREATE INDEX documents_numberplanid_idx ON documents(numberplanid);
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2005080300', 'dbversion'));

$this->CommitTrans();
