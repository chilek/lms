<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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

$DB->Execute("

    CREATE SEQUENCE \"ewx_pt_config_id_seq\";
    CREATE TABLE ewx_pt_config (
        id integer DEFAULT nextval('ewx_pt_config_id_seq'::text) NOT NULL,
	nodeid integer          DEFAULT 0 NOT NULL,
	name varchar(16)        DEFAULT '' NOT NULL,
	mac varchar(20)         DEFAULT '' NOT NULL,
	ipaddr bigint           DEFAULT 0 NOT NULL,
	passwd varchar(32)      DEFAULT '' NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (nodeid)
    );

    CREATE SEQUENCE \"ewx_stm_nodes_id_seq\";
    CREATE TABLE ewx_stm_nodes (
        id integer DEFAULT nextval('ewx_stm_nodes_id_seq'::text) NOT NULL,
	nodeid integer          DEFAULT 0 NOT NULL,
	mac varchar(20)         DEFAULT '' NOT NULL,
	ipaddr bigint           DEFAULT 0 NOT NULL,
	channelid integer       DEFAULT 0 NOT NULL,
	uprate integer          DEFAULT 0 NOT NULL,
	upceil integer          DEFAULT 0 NOT NULL,
	downrate integer        DEFAULT 0 NOT NULL,
	downceil integer        DEFAULT 0 NOT NULL,
	halfduplex smallint     DEFAULT 0 NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (nodeid)
    );

    CREATE SEQUENCE \"ewx_stm_channels_id_seq\";
    CREATE TABLE ewx_stm_channels (
        id integer DEFAULT nextval('ewx_stm_channels_id_seq'::text) NOT NULL,
	customerid integer      DEFAULT 0 NOT NULL,
	upceil integer          DEFAULT 0 NOT NULL,
	downceil integer        DEFAULT 0 NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (customerid)
    );
");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2007030800', 'dbversion'));

$DB->CommitTrans();

?>
