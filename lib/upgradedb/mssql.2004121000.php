<?php

/*
 * LMS version 1.5-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

$DB->Execute("IF EXISTS (SELECT name FROM sysobjects WHERE name='uiconfig' AND type='U') 
    DROP TABLE uiconfig");
$DB->Execute("CREATE TABLE uiconfig (
    id int NOT NULL IDENTITY(1,1),
    section varchar(64) NOT NULL DEFAULT '',
    var varchar(64) NOT NULL DEFAULT '',
    value text NOT NULL DEFAULT '',
    description text NOT NULL DEFAULT '',
    disabled smallint NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE (section, var))");

$DB->Execute("IF EXISTS (SELECT name FROM sysobjects WHERE name='passwd' AND type='U') 
    DROP TABLE passwd");
$DB->Execute("CREATE TABLE passwd (
    id int NOT NULL IDENTITY(1,1),
    ownerid int NOT NULL DEFAULT 0,
    login varchar(200) NOT NULL DEFAULT '',
    password varchar(200) NOT NULL DEFAULT '',
    lastlogin int NOT NULL DEFAULT 0,
    uid int NOT NULL DEFAULT 0,
    home varchar(255) NOT NULL DEFAULT '',
    type smallint NOT NULL DEFAULT 0,
    expdate int NOT NULL DEFAULT 0,
    domainid int NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE (login))");

$DB->Execute("IF EXISTS (SELECT name FROM sysobjects WHERE name='aliases' AND type='U') 
    DROP TABLE aliases");
$DB->Execute("CREATE TABLE aliases (
    id int NOT NULL IDENTITY(1,1),
    login varchar(255) NOT NULL DEFAULT '',
    accountid int NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE (login, accountid))");

$DB->Execute("IF EXISTS (SELECT name FROM sysobjects WHERE name='domains' AND type='U') 
    DROP TABLE domains");
$DB->Execute("CREATE TABLE domains (
    id int NOT NULL IDENTITY(1,1),
    name varchar(255) NOT NULL DEFAULT '',
    description text NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    UNIQUE (name))");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?",array('2004121000', 'dbversion'));
$DB->CommitTrans();
?>
