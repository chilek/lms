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
ALTER TABLE invoicecontents ADD itemid smallint;
UPDATE invoicecontents set itemid = 0 where itemid is NULL;
ALTER TABLE invoicecontents ALTER itemid SET DEFAULT 0;
ALTER TABLE invoicecontents ALTER itemid set NOT NULL;

ALTER TABLE cash add itemid smallint;
UPDATE cash set itemid=0 where itemid is NULL;
ALTER TABLE cash ALTER itemid SET DEFAULT 0;
ALTER TABLE cash ALTER itemid SET NOT NULL;
	UPDATE dbinfo SET keyvalue = '2005013000' WHERE keytype = 'dbversion'
");
$DB->CommitTrans();

?>
