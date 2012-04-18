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

$DB->Execute("
    BEGIN;
    CREATE SEQUENCE rtrights_id_seq;
    CREATE TABLE rtrights (
    id integer DEFAULT nextval('rtrights_id_seq'::text) NOT NULL, 
    adminid integer DEFAULT 0 NOT NULL,
    queueid integer DEFAULT 0 NOT NULL,
    rights integer DEFAULT 0 NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (adminid, queueid)
    );
    ALTER TABLE rtqueues ADD description text;
    ALTER TABLE rtqueues ALTER description SET DEFAULT '';
    UPDATE rtqueues SET description='';
    ALTER TABLE rtqueues ALTER description SET NOT NULL;

    UPDATE dbinfo SET keyvalue = '2004070100' WHERE keytype = 'dbversion';
    COMMIT;
");

?>
