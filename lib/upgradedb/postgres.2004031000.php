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

// Some stuff by maniac.

$this->Execute("
    ALTER TABLE users ADD message text;
    ALTER TABLE users ALTER message SET DEFAULT '';
    UPDATE users SET message='';
    ALTER TABLE users ALTER message SET NOT NULL
");

$this->Execute("
    ALTER TABLE nodes ADD warning smallint;
    ALTER TABLE nodes ALTER warning SET DEFAULT 0;
    UPDATE nodes SET warning=0;
    ALTER TABLE nodes ALTER warning SET NOT NULL
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2004031000', 'dbversion'));
