<?php

/*
 * LMS version 1.11-git
 *
 * (C) Copyright 2001-2013 LMS Developers
 *
 * Please, see the doc/AUTHORS for more information about authors!
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License Version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 * USA.
 *
 * $Id$
 */

$this->Execute("ALTER TABLE admins CHANGE id id INT(11) NOT NULL AUTO_INCREMENT, CHANGE login login VARCHAR(32) NOT NULL, CHANGE name name VARCHAR(64) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE rights rights VARCHAR(64) NOT NULL, CHANGE passwd passwd VARCHAR(255) NOT NULL, CHANGE lastlogindate lastlogindate INT(11) DEFAULT '0' NOT NULL, CHANGE lastloginip lastloginip VARCHAR(16) NOT NULL, CHANGE failedlogindate failedlogindate INT(11) DEFAULT '0' NOT NULL, CHANGE failedloginip failedloginip VARCHAR(16) NOT NULL");
$this->Execute("ALTER TABLE admins DROP INDEX id");
$this->Execute("ALTER TABLE admins DROP INDEX id_2");
$this->Execute("ALTER TABLE admins ADD UNIQUE (login)");
$this->Execute("ALTER TABLE assignments DROP INDEX id");
$this->Execute("ALTER TABLE assignments DROP INDEX id_2");
$this->Execute("ALTER TABLE cash DROP INDEX id");
$this->Execute("ALTER TABLE cash DROP INDEX id_2");
$this->Execute("ALTER TABLE cash CHANGE value value DECIMAL(9, 2) NOT NULL");
$this->Execute("ALTER TABLE dbinfo DROP INDEX keytype");
$this->Execute("ALTER TABLE dbinfo DROP INDEX keytype_2");
$this->Execute("ALTER TABLE invoicecontents CHANGE pkwiu pkwiu VARCHAR(255) DEFAULT '' NOT NULL");
$this->Execute("ALTER TABLE invoices DROP INDEX id");
$this->Execute("ALTER TABLE invoices DROP INDEX id_2");
$this->Execute("ALTER TABLE invoices CHANGE nip nip VARCHAR(16) NOT NULL, CHANGE pesel pesel VARCHAR(11) NOT NULL");
$this->Execute("ALTER TABLE netdevices CHANGE name name VARCHAR(32) NOT NULL, CHANGE location location VARCHAR(255) NOT NULL, CHANGE description description VARCHAR(255) NOT NULL, CHANGE producer producer VARCHAR(64) NOT NULL, CHANGE model model VARCHAR(32) NOT NULL, CHANGE serialnumber serialnumber VARCHAR(32) NOT NULL, CHANGE ports ports INT(10) NOT NULL");
$this->Execute("ALTER TABLE netdevices DROP INDEX name");
$this->Execute("ALTER TABLE netlinks ADD UNIQUE (src, dst)");
$this->Execute("ALTER TABLE networks CHANGE gateway gateway VARCHAR(16) NOT NULL, CHANGE interface interface VARCHAR(8) NOT NULL, CHANGE dns dns VARCHAR(16) NOT NULL, CHANGE dns2 dns2 VARCHAR(16) NOT NULL, CHANGE wins wins VARCHAR(16) NOT NULL, CHANGE dhcpstart dhcpstart VARCHAR(16) NOT NULL, CHANGE dhcpend dhcpend VARCHAR(16) NOT NULL");
$this->Execute("ALTER TABLE networks DROP INDEX id");
$this->Execute("ALTER TABLE networks DROP INDEX id_2");
$this->Execute("ALTER TABLE nodes DROP INDEX id");
$this->Execute("ALTER TABLE nodes DROP INDEX id_2");
$this->Execute("ALTER TABLE nodes CHANGE netdev netdev INT(11) DEFAULT '0' NOT NULL");
$this->Execute("ALTER TABLE nodes ADD UNIQUE (name)");
$this->Execute("ALTER TABLE nodes ADD UNIQUE (mac)");
$this->Execute("ALTER TABLE nodes ADD UNIQUE (ipaddr)");
$this->Execute("ALTER TABLE payments CHANGE description description TEXT NOT NULL");
$this->Execute("ALTER TABLE payments DROP INDEX id");
$this->Execute("ALTER TABLE payments DROP INDEX id_2");
$this->Execute("ALTER TABLE rtqueues ADD UNIQUE (name)");
$this->Execute("ALTER TABLE stats DROP PRIMARY KEY");
$this->Execute("ALTER TABLE stats ADD UNIQUE (nodeid, dt)");
$this->Execute("ALTER TABLE tariffs CHANGE value value DECIMAL(9, 2) NOT NULL, CHANGE taxvalue taxvalue DECIMAL(9, 2) NOT NULL, CHANGE pkwiu pkwiu VARCHAR(255) NOT NULL, CHANGE description description TEXT NOT NULL");
$this->Execute("ALTER TABLE tariffs DROP INDEX id");
$this->Execute("ALTER TABLE tariffs DROP INDEX id_2");
$this->Execute("ALTER TABLE tariffs ADD UNIQUE (name)");
$this->Execute("ALTER TABLE timestamps ADD UNIQUE (tablename)");
$this->Execute("ALTER TABLE users CHANGE lastname lastname VARCHAR(255) NOT NULL, CHANGE name name VARCHAR(255) NOT NULL, CHANGE status status INT(11) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE phone1 phone1 VARCHAR(255) NOT NULL, CHANGE phone2 phone2 VARCHAR(255) NOT NULL, CHANGE phone3 phone3 VARCHAR(255) NOT NULL, CHANGE gguin gguin INT(11) NOT NULL DEFAULT 0, CHANGE zip zip VARCHAR(6) NOT NULL, CHANGE city city VARCHAR(32) NOT NULL, CHANGE nip nip VARCHAR(16) NOT NULL, CHANGE pesel pesel VARCHAR(11) NOT NULL, CHANGE info info TEXT NOT NULL");
$this->Execute("ALTER TABLE users DROP INDEX id");
$this->Execute("ALTER TABLE users DROP INDEX id_2");
$this->Execute("ALTER TABLE users DROP tariff, DROP payday");
$this->Execute("ALTER TABLE users CHANGE message message TEXT NOT NULL");
$this->Execute("ALTER TABLE invoicecontents CHANGE value value DECIMAL(9, 2) NOT NULL, CHANGE taxvalue taxvalue DECIMAL(9, 2) NOT NULL, CHANGE count count DECIMAL(9, 2) NOT NULL ");
$this->Execute("UPDATE dbinfo SET keyvalue='2004031400' WHERE keytype='dbversion'");
