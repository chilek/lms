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
    ALTER TABLE assignments ADD pperiod smallint;
    UPDATE assignments SET pperiod = period + 2;
    ALTER TABLE assignments DROP period;
    ALTER TABLE assignments ADD period smallint;
    UPDATE assignments SET period = pperiod;
    ALTER TABLE assignments DROP pperiod;
    ALTER TABLE assignments ALTER period SET NOT NULL;
    ALTER TABLE assignments ALTER period SET DEFAULT 0;

    ALTER TABLE assignments ADD aat smallint;
    UPDATE assignments SET aat = at;
    ALTER TABLE assignments DROP at;
    ALTER TABLE assignments ADD at smallint;
    UPDATE assignments SET at = aat;
    ALTER TABLE assignments DROP aat;
    ALTER TABLE assignments ALTER at SET NOT NULL;
    ALTER TABLE assignments ALTER at SET DEFAULT 0;

    ALTER TABLE payments ADD pperiod smallint;
    UPDATE payments SET pperiod = period + 2;
    ALTER TABLE payments DROP period;
    ALTER TABLE payments ADD period smallint;
    UPDATE payments SET period = pperiod;
    ALTER TABLE payments DROP pperiod;
    ALTER TABLE payments ALTER period SET NOT NULL;
    ALTER TABLE payments ALTER period SET DEFAULT 0;

    ALTER TABLE payments ADD aat smallint;
    UPDATE payments SET aat = at;
    ALTER TABLE payments DROP at;
    ALTER TABLE payments ADD at smallint;
    UPDATE payments SET at = aat;
    ALTER TABLE payments DROP aat;
    ALTER TABLE payments ALTER at SET NOT NULL;
    ALTER TABLE payments ALTER at SET DEFAULT 0;
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2005072300', 'dbversion'));

$this->CommitTrans();
