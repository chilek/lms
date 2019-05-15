<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2009 Webvisor Sp. z o.o.
 *
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

$this->Execute("ALTER TABLE domains ADD master VARCHAR(128) DEFAULT NULL");
$this->Execute("ALTER TABLE domains ADD last_check INT(11) DEFAULT NULL");
$this->Execute("ALTER TABLE domains ADD type    VARCHAR(6) NOT NULL");
$this->Execute("ALTER TABLE domains ADD notified_serial INT(11) DEFAULT NULL");
$this->Execute("ALTER TABLE domains ADD account VARCHAR(40) DEFAULT NULL");

$this->Execute("CREATE TABLE records (
  id              INT(11) auto_increment,
  domain_id       INT(11) DEFAULT NULL,
  name            VARCHAR(255) DEFAULT NULL,
  type            VARCHAR(6) DEFAULT NULL,
  content         VARCHAR(255) DEFAULT NULL,
  ttl             INT(11) DEFAULT NULL,
  prio            INT(11) DEFAULT NULL,
  change_date     INT(11) DEFAULT NULL,
  PRIMARY KEY(id),
  INDEX domain_id (domain_id),
  INDEX name_type (name, type, domain_id)
)");

$this->Execute("CREATE TABLE supermasters (
  id            INT(11) auto_increment,
  ip 		VARCHAR(25) NOT NULL,
  nameserver 	VARCHAR(255) NOT NULL,
  account 	VARCHAR(40) DEFAULT NULL,
  PRIMARY KEY (id)
)");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2009103000', 'dbversion'));
