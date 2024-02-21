<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2024 LMS Developers
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

define('RT_RIGHT_NOTICE_2024022100', 8);
define('RT_RIGHT_SMS_NOTICE_2024022100', 8);
define('RT_RIGHT_EMAIL_NOTICE_2024022100', 32);

$this->Execute(
    "UPDATE rtrights SET rights = (rights | ?) WHERE (rights & ?) > 0",
    array(
        RT_RIGHT_EMAIL_NOTICE_2024022100,
        RT_RIGHT_NOTICE_2024022100,
    )
);

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2024022100', 'dbversion'));

$this->CommitTrans();
