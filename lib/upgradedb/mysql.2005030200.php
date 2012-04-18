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

$DB->Execute("CREATE INDEX netdev ON nodes(netdev)");
$DB->Execute("CREATE INDEX queueid ON rttickets(queueid)");
$DB->Execute("CREATE INDEX time ON cash(time)");
$DB->Execute("CREATE INDEX cdate ON invoices(cdate)");
$DB->Execute("CREATE INDEX invoiceid ON invoicecontents(invoiceid)");
$DB->Execute("CREATE INDEX hash ON cashimport(hash)");

$DB->Execute("UPDATE dbinfo SET keyvalue = '2005030200' WHERE keytype = 'dbversion'");

$DB->CommitTrans();

?>
