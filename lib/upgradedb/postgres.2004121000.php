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
    ALTER TABLE uiconfig ADD section1 varchar(64);
    UPDATE uiconfig SET section1 = section;
    ALTER TABLE uiconfig DROP section;
    ALTER TABLE uiconfig ADD section varchar(64);
    UPDATE uiconfig SET section = section1;
    ALTER TABLE uiconfig DROP section1;
    ALTER TABLE uiconfig ALTER section SET NOT NULL;
    ALTER TABLE uiconfig ALTER section SET DEFAULT '';

    ALTER TABLE uiconfig ADD var1 varchar(64);
    UPDATE uiconfig SET var1 = var;
    ALTER TABLE uiconfig DROP var;
    ALTER TABLE uiconfig ADD var varchar(64);
    UPDATE uiconfig SET var = var1;
    ALTER TABLE uiconfig DROP var1;
    ALTER TABLE uiconfig ALTER var SET NOT NULL;
    ALTER TABLE uiconfig ALTER var SET DEFAULT '';
    
    UPDATE dbinfo SET keyvalue = '2004121000' WHERE keytype = 'dbversion'
");
$this->CommitTrans();
