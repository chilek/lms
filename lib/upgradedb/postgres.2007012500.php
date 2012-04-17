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

$DB->Execute("ALTER TABLE nodes ADD halfduplex smallint");
$DB->Execute("ALTER TABLE tariffs ADD dlimit integer");

$DB->Execute("UPDATE nodes SET halfduplex = 0");
$DB->Execute("UPDATE tariffs SET dlimit = 0");

$DB->Execute("ALTER TABLE nodes ALTER halfduplex SET NOT NULL");
$DB->Execute("ALTER TABLE nodes ALTER halfduplex SET DEFAULT 0");
$DB->Execute("ALTER TABLE tariffs ALTER dlimit SET NOT NULL");
$DB->Execute("ALTER TABLE tariffs ALTER dlimit SET DEFAULT 0");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2007012500', 'dbversion'));

$DB->CommitTrans();

?>
