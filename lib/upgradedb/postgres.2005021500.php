<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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

$DB->BeginTrans();
$DB->Execute("
	ALTER TABLE invoices ADD zip1 VARCHAR(10);
	UPDATE invoices SET zip1=zip;
	ALTER TABLE invoices DROP zip;
	ALTER TABLE invoices ADD zip VARCHAR (10);
	UPDATE invoices SET zip=zip1;
	ALTER TABLE invoices ALTER zip SET DEFAULT '';
	ALTER TABLE invoices ALTER zip SET NOT NULL;
");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?",array('2005021500', 'dbversion'));
$DB->CommitTrans();

?>
