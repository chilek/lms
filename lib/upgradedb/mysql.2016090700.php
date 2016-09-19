<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

$this->BeginTrans();

$this->Execute("CREATE TABLE voip_pool_numbers (
                   id int(11) NOT NULL AUTO_INCREMENT,
                   disabled smallint DEFAULT 0,
                   name varchar(30) NOT NULL,
                   poolstart varchar(20) NOT NULL,
                   poolend varchar(20) NOT NULL,
                   description text,
                   PRIMARY KEY (id),
                   UNIQUE (name)
                ) ENGINE=InnoDB;");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016090700', 'dbversion'));

$this->CommitTrans();

?>
