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

$this->Execute("
    BEGIN;
    ALTER TABLE rtmessages ADD userid integer;
    ALTER TABLE rtmessages ALTER userid SET DEFAULT 0;
    UPDATE rtmessages SET userid=0;
    ALTER TABLE rtmessages ALTER userid SET NOT NULL;
    
    ALTER TABLE rtmessages ADD adminid integer;
    ALTER TABLE rtmessages ALTER adminid SET DEFAULT 0;
    UPDATE rtmessages SET adminid=sender;
    ALTER TABLE rtmessages ALTER adminid SET NOT NULL;
    ALTER TABLE rtmessages DROP COLUMN sender;

    ALTER TABLE rtqueues DROP CONSTRAINT rtqueues_email_key;

    UPDATE dbinfo SET keyvalue = '2004071200' WHERE keytype = 'dbversion';
    COMMIT;
");
