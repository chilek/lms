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

$DB->Execute("DROP VIEW nas");
$DB->Execute("CREATE VIEW nas AS SELECT n.id, inet_ntoa(n.ipaddr) AS nasname, d.shortname, d.nastype AS type,
        d.clients AS ports, d.secret, d.community, d.description
        FROM nodes n
        JOIN netdevices d ON (n.netdev = d.id)
        WHERE n.nas = 1");
$DB->Execute("CREATE TABLE cashsources (
	id          int(11)         NOT NULL auto_increment,
	name        varchar(32)     DEFAULT '' NOT NULL,
	description text	    DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY name (name)
) ENGINE=MyISAM");
$DB->Execute("ALTER TABLE cash ADD importid int(11) DEFAULT NULL");
$DB->Execute("ALTER TABLE cash ADD sourceid int(11) DEFAULT NULL");
$DB->Execute("ALTER TABLE cashimport ADD sourceid int(11) DEFAULT NULL");
$DB->Execute("ALTER TABLE cash ADD INDEX importid (importid)");
$DB->Execute("ALTER TABLE cash ADD INDEX sourceid (sourceid)");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2009060200', 'dbversion'));

?>
