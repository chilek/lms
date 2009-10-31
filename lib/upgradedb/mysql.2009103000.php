<?php

/*
 * LMS version 1.11-cvs
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


$DB->Execute("ALTER TABLE domains ADD master VARCHAR(128) DEFAULT NULL");
$DB->Execute("ALTER TABLE domains ADD last_check INT DEFAULT NULL");
$DB->Execute("ALTER TABLE domains ADD type    VARCHAR(6) NOT NULL");
$DB->Execute("ALTER TABLE domains ADD notified_serial INT DEFAULT NULL");
$DB->Execute("ALTER TABLE domains ADD account VARCHAR(40) DEFAULT NULL");
$DB->Execute("ALTER TABLE domains engine=innodb");


$DB->Execute("CREATE UNIQUE INDEX name_index ON domains(name)");

$DB->Execute("CREATE TABLE records (
  id              INT auto_increment,
  domain_id       INT DEFAULT NULL,
  name            VARCHAR(255) DEFAULT NULL,
  type            VARCHAR(6) DEFAULT NULL,
  content         VARCHAR(255) DEFAULT NULL,
  ttl             INT DEFAULT NULL,
  prio            INT DEFAULT NULL,
  change_date     INT DEFAULT NULL,
  primary key(id),
  CONSTRAINT `records_ibfk_1` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE CASCADE
) type=InnoDB");

$DB->Execute("CREATE INDEX rec_name_index ON records(name)");
$DB->Execute("CREATE INDEX nametype_index ON records(name,type)");
$DB->Execute("CREATE INDEX domain_id ON records(domain_id)");

$DB->Execute("CREATE TABLE supermasters (
  ip VARCHAR(25) NOT NULL,
  nameserver VARCHAR(255) NOT NULL,
  account VARCHAR(40) DEFAULT NULL
)");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2009103000', 'dbversion'));

?>
