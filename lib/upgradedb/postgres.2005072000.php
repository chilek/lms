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
	CREATE TABLE documentcontents (
	    docid integer DEFAULT 0 NOT NULL,
	    title text DEFAULT '' NOT NULL,
	    fromdate integer DEFAULT 0 NOT NULL,
	    todate integer DEFAULT 0 NOT NULL,
	    filename varchar(255) DEFAULT '' NOT NULL,
	    contenttype varchar(255) DEFAULT '' NOT NULL,
	    md5sum varchar(32) DEFAULT '' NOT NULL,
	    description text DEFAULT '' NOT NULL,
	    UNIQUE (docid))
");
$this->Execute("CREATE INDEX documentcontents_md5sum_idx ON documentcontents (md5sum)");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2005072000', 'dbversion'));

$this->CommitTrans();
