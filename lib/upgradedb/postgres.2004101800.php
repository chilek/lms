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
    ALTER TABLE nodes ADD COLUMN linktype smallint;
    UPDATE nodes SET linktype=0;
    ALTER TABLE nodes ALTER linktype SET DEFAULT 0;
    ALTER TABLE nodes ALTER linktype SET NOT NULL;

    ALTER TABLE netlinks ADD COLUMN type smallint;
    UPDATE netlinks SET type=0;
    ALTER TABLE netlinks ALTER type SET DEFAULT 0;
    ALTER TABLE netlinks ALTER type SET NOT NULL;
    
    UPDATE dbinfo SET keyvalue = '2004101800' WHERE keytype = 'dbversion';
");
$this->CommitTrans();
