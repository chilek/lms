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

$this->BeginTrans();

$this->Execute("
CREATE TABLE imessengers (
    id int(11) NOT NULL auto_increment, 
    customerid int(11) NOT NULL DEFAULT '0', 
    uid varchar(32) NOT NULL DEFAULT '',
    type tinyint(1) NOT NULL DEFAULT '0',
    PRIMARY KEY (id),
    INDEX customerid (customerid)
) ENGINE=MyISAM;
");

$this->Execute("INSERT INTO imessengers (customerid, uid)
        SELECT id, im FROM customers WHERE im > 0");
    
$this->Execute("ALTER TABLE customers DROP im");
    
$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2007033100', 'dbversion'));

$this->CommitTrans();
