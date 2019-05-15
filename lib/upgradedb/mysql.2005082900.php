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

$this->Execute("CREATE TABLE hosts (
    id int(11) NOT NULL auto_increment,
    name varchar(255) default '' NOT NULL,
    description text default '' NOT NULL,
    lastreload int(11) default '0' NOT NULL,
    reload tinyint(1) default '0' NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY name (name)
) ENGINE=MyISAM");
$this->Execute("INSERT INTO hosts (id, name, description, lastreload, reload) SELECT id, name, description, lastreload, reload FROM daemonhosts");
$this->Execute("DROP TABLE daemonhosts");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2005082900', 'dbversion'));
