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

$DB->Execute("ALTER TABLE assignments CHANGE userid customerid INT(11) DEFAULT '0' NOT NULL");
$DB->Execute("ALTER TABLE events CHANGE userid customerid INT(11) DEFAULT '0' NOT NULL");
$DB->Execute("ALTER TABLE rttickets CHANGE userid customerid INT(11) DEFAULT '0' NOT NULL");
$DB->Execute("ALTER TABLE rtmessages CHANGE userid customerid INT(11) DEFAULT '0' NOT NULL");
$DB->Execute("ALTER TABLE cash CHANGE userid customerid INT(11) DEFAULT '0' NOT NULL");
$DB->Execute("ALTER TABLE cash DROP INDEX userid");
$DB->Execute("ALTER TABLE cash ADD INDEX customerid (customerid)");
$DB->Execute("ALTER TABLE userassignments CHANGE userid customerid INT(11) DEFAULT '0' NOT NULL");
$DB->Execute("ALTER TABLE userassignments DROP INDEX userassignment");
$DB->Execute("ALTER TABLE userassignments ADD UNIQUE (usergroupid, customerid)");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?",array('2005052300', 'dbversion'));

?>
