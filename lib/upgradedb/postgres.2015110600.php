<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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

define('EMAIL', 8);
define('EMAIL_INVOICE', 16);

$this->BeginTrans();

$this->Execute(
    "UPDATE customercontacts SET type = ? WHERE customerid IN (SELECT id FROM customers WHERE einvoice = 1 AND invoicenotice = 1) AND (type & ?) > 0",
    array(EMAIL | EMAIL_INVOICE, EMAIL)
);

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2015110600', 'dbversion'));

$this->CommitTrans();
