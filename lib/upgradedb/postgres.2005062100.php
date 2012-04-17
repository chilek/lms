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
	ALTER TABLE tariffs ADD prodid VARCHAR(255);
	UPDATE tariffs SET prodid = pkwiu;
	ALTER TABLE tariffs ALTER prodid SET NOT NULL;
	ALTER TABLE tariffs ALTER prodid SET DEFAULT '';
	ALTER TABLE tariffs DROP pkwiu;
	
	ALTER TABLE invoicecontents ADD prodid VARCHAR(255);
	UPDATE invoicecontents SET prodid = pkwiu;
	ALTER TABLE invoicecontents ALTER prodid SET NOT NULL;
	ALTER TABLE invoicecontents ALTER prodid SET DEFAULT '';
	ALTER TABLE invoicecontents DROP pkwiu;
");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?",array('2005062100', 'dbversion'));

$DB->CommitTrans();

?>
