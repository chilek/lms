<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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
 */

$this->BeginTrans();

//Helpdesk ticket source
define('RT_SOURCE_UNKNOWN', 0);
define('RT_SOURCE_PHONE', 1);
define('RT_SOURCE_EMAIL', 2);
define('RT_SOURCE_USERPANEL', 3);
define('RT_SOURCE_PERSONAL', 4);
define('RT_SOURCE_MESSCHAT', 5);
define('RT_SOURCE_PAPER', 6);
define('RT_SOURCE_SMS', 7);

$RT_SOURCES = array(
    RT_SOURCE_UNKNOWN => trans('unknown/other'),
    RT_SOURCE_PHONE => trans('Phone'),
    RT_SOURCE_EMAIL => trans('e-mail'),
    RT_SOURCE_USERPANEL => trans('Userpanel'),
    RT_SOURCE_PERSONAL => trans('Personal'),
    RT_SOURCE_MESSCHAT => trans('Instant messengers'),
    RT_SOURCE_PAPER => trans('Letter complaint'),
    RT_SOURCE_SMS => trans('SMS'),
);

$this->Execute(
    "INSERT INTO uiconfig (section, var, value) VALUES (?, ?, ?)",
    array('userpanel', 'visible_ticket_sources', implode(';', array_keys($RT_SOURCES)))
);

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2017070300', 'dbversion'));

$this->CommitTrans();
