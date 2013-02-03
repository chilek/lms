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

$DB->BeginTrans();

$create_reg = $DB->GetOne('SELECT docid FROM receiptcontents LIMIT 1');

$DB->Execute("ALTER TABLE receiptcontents ADD COLUMN regid int(11) NOT NULL DEFAULT '0'");
$DB->Execute("UPDATE receiptcontents SET regid = ?", array($create_reg ? 1 : 0));
$DB->Execute("ALTER TABLE receiptcontents ADD INDEX regid (regid)");

$DB->Execute("CREATE TABLE cashrights (
	id int(11) 	NOT NULL auto_increment,
        userid int(11) 	DEFAULT '0' NOT NULL,
	regid int(11) 	DEFAULT '0' NOT NULL,
	rights int(11) 	DEFAULT '0' NOT NULL,
	PRIMARY KEY (id),
	UNIQUE KEY userid (userid, regid)
    ) ENGINE=MyISAM
");

$DB->Execute("CREATE TABLE cashregs (
	id int(11) 		NOT NULL auto_increment,
        name varchar(255) 	DEFAULT '' NOT NULL,
	description text 	DEFAULT '' NOT NULL,
	in_numberplanid int(11) DEFAULT '0' NOT NULL,
	out_numberplanid int(11) DEFAULT '0' NOT NULL,
	PRIMARY KEY (id),
	UNIQUE KEY name (name)
    ) ENGINE=MyISAM
");

if($create_reg)
	$DB->Execute("INSERT INTO cashregs (name) VALUES ('default')");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?",array('2005122800', 'dbversion'));

$DB->CommitTrans();

?>
