<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

/**
 * @author Maciej_Wawryk
 */

$this->BeginTrans();

define('CONFIG_TYPE_MAIL_BACKEND', 9);
define('CONFIG_TYPE_MAIL_SECURE', 10);

$this->Execute("INSERT INTO uiconfig (section, var, value, type) VALUES('mail', 'backend', 'pear', ?)", array(CONFIG_TYPE_MAIL_BACKEND));
$this->Execute("INSERT INTO uiconfig (section, var, value, type) VALUES('mail', 'smtp_secure', 'ssl', ?)", array(CONFIG_TYPE_MAIL_SECURE));

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016012500', 'dbversion'));

$this->CommitTrans();

?>
