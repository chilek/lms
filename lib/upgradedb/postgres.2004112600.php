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
    CREATE SEQUENCE uiconfig_id_seq;
    CREATE TABLE uiconfig (
	id integer DEFAULT nextval('uiconfig_id_seq'::text) NOT NULL,
	section varchar(255) NOT NULL DEFAULT '',
	var varchar(255) NOT NULL DEFAULT '',
        value text NOT NULL DEFAULT '',
	description text NOT NULL DEFAULT '',
	disabled smallint NOT NULL DEFAULT 0,
	PRIMARY KEY (id)
    );
    UPDATE dbinfo SET keyvalue = '2004112600' WHERE keytype = 'dbversion';
");
$DB->CommitTrans();

?>
