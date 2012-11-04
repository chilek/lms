<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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

$DB->BeginTrans();

$DB->Execute("
CREATE TABLE IF NOT EXISTS syslog (
id bigint(20) NOT NULL AUTO_INCREMENT,
cdate int(11) DEFAULT NULL,
uid int(11) DEFAULT NULL,
cid int(11) DEFAULT NULL,
nid int(11) DEFAULT NULL,
module tinyint(4) DEFAULT NULL,
event tinyint(4) DEFAULT NULL,
msg varchar(255) COLLATE utf8_polish_ci DEFAULT NULL,
diff text COLLATE utf8_polish_ci,
PRIMARY KEY (id),
KEY cdate (cdate),
KEY uid (uid),
KEY module (module),
KEY event (event)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci AUTO_INCREMENT=1 ;
");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2012110500', 'dbversion'));

$DB->CommitTrans();

?>
