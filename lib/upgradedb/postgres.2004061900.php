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
    ALTER TABLE tariffs ADD upceil integer;
    ALTER TABLE tariffs ALTER upceil SET DEFAULT 0;
    UPDATE tariffs SET upceil=0;
    ALTER TABLE tariffs ALTER upceil SET NOT NULL;

    ALTER TABLE tariffs ADD downceil integer;
    ALTER TABLE tariffs ALTER downceil SET DEFAULT 0;
    UPDATE tariffs SET downceil=0;
    ALTER TABLE tariffs ALTER downceil SET NOT NULL;

    ALTER TABLE tariffs ADD climit integer;
    ALTER TABLE tariffs ALTER climit SET DEFAULT 0;
    UPDATE tariffs SET climit=0;
    ALTER TABLE tariffs ALTER climit SET NOT NULL;

    ALTER TABLE tariffs ADD plimit integer;
    ALTER TABLE tariffs ALTER plimit SET DEFAULT 0;
    UPDATE tariffs SET plimit=0;
    ALTER TABLE tariffs ALTER plimit SET NOT NULL;

    UPDATE tariffs SET upceil=uprate, downceil=downrate;

    UPDATE dbinfo SET keyvalue = '2004061900' WHERE keytype = 'dbversion';
    COMMIT;
");
