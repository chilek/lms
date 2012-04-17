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

$DB->Execute("ALTER TABLE assignments CHANGE at at smallint DEFAULT '0' NOT NULL");
$DB->Execute("ALTER TABLE assignments CHANGE period period smallint DEFAULT '0' NOT NULL");
$DB->Execute("UPDATE assignments SET period = period + 2");

$DB->Execute("ALTER TABLE payments CHANGE at at smallint DEFAULT '0' NOT NULL");
$DB->Execute("ALTER TABLE payments CHANGE period period smallint DEFAULT '0' NOT NULL");
$DB->Execute("UPDATE payments SET period = period + 2");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?",array('2005072300', 'dbversion'));

?>
