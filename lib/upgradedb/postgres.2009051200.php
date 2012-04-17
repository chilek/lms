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

CREATE SEQUENCE docrights_id_seq;
CREATE TABLE docrights (
    id          integer         DEFAULT nextval('docrights_id_seq'::text) NOT NULL,
    userid      integer         DEFAULT 0 NOT NULL,
    doctype     integer         DEFAULT 0 NOT NULL,
    rights      integer         DEFAULT 0 NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT docrights_userid_key UNIQUE (userid, doctype)
);

");

foreach(array(-1,-2,-3,-4,-5,-6,-7,-8, -9,-10) as $doctype)
	$DB->Execute("INSERT INTO docrights (userid, doctype, rights)
		SELECT id, ?, ? FROM users WHERE deleted = 0",
		array($doctype, 31)); 
/*
1 - view
2 - create
4 - confirm
8 - edit
16 - delete
*/

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2009051200', 'dbversion'));

$DB->CommitTrans();

?>
