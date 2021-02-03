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
    
    ALTER TABLE passwd ADD type SMALLINT;
    UPDATE passwd SET type = 32767;
    ALTER TABLE passwd ALTER type SET DEFAULT 0;
    ALTER TABLE passwd ALTER type SET NOT NULL;

    ALTER TABLE passwd ADD expdate INTEGER;
    UPDATE passwd SET expdate = 0;
    ALTER TABLE passwd ALTER expdate SET DEFAULT 0;
    ALTER TABLE passwd ALTER expdate SET NOT NULL;

    ALTER TABLE passwd ADD domain VARCHAR(255);
    UPDATE passwd SET domain = '';
    ALTER TABLE passwd ALTER domain SET DEFAULT '';
    ALTER TABLE passwd ALTER domain SET NOT NULL;

    ALTER TABLE passwd ADD UNIQUE (login);
    
    UPDATE dbinfo SET keyvalue = '2004112100' WHERE keytype = 'dbversion';
");
$this->CommitTrans();
