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
    CREATE OR REPLACE FUNCTION mask2prefix(bigint) RETURNS smallint AS $$
    SELECT
	    length(replace(ltrim(textin(bit_out($1::bit(32))), '0'), '0', ''))::smallint;
    $$ LANGUAGE SQL IMMUTABLE;
    
    CREATE OR REPLACE FUNCTION broadcast(bigint, bigint) RETURNS bigint AS $$
    SELECT
	($1::bit(32) |  ~($2::bit(32)))::bigint;
    $$ LANGUAGE SQL IMMUTABLE;

-- add IMMUTABLE flag for old functions
    CREATE OR REPLACE FUNCTION inet_ntoa(bigint) RETURNS text AS $$
    SELECT
        ($1/(256*256*256))::text
        ||'.'||
	($1/(256*256) - $1/(256*256*256)*256)::text
	||'.'||
	($1/256 - $1/(256*256)*256)::text
	||'.'||
	($1 - $1/256*256)::text;
    $$ LANGUAGE SQL IMMUTABLE;
				   
    CREATE OR REPLACE FUNCTION inet_aton(text) RETURNS bigint AS $$
    SELECT
	split_part($1,'.',1)::int8*(256*256*256)+
	split_part($1,'.',2)::int8*(256*256)+
	split_part($1,'.',3)::int8*256+
	split_part($1,'.',4)::int8;
    $$ LANGUAGE SQL IMMUTABLE;			       
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2008012200', 'dbversion'));

$this->CommitTrans();
