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

$this->BeginTrans();

$this->Execute("CREATE SEQUENCE voip_numbers_id_seq;
                CREATE TABLE voip_numbers (
                   id integer DEFAULT nextval('voip_numbers_id_seq'::text) NOT NULL,
                   voip_account_id integer NOT NULL
                       REFERENCES voipaccounts (id) ON DELETE CASCADE ON UPDATE CASCADE,
                   phone varchar(20) NOT NULL,
                   UNIQUE(phone));
               ");

$this->Execute("INSERT INTO voip_numbers (voip_account_id, phone)
                SELECT id, phone FROM voipaccounts;");

$this->Execute("ALTER TABLE voipaccounts DROP COLUMN phone;");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016082900', 'dbversion'));

$this->CommitTrans();

?>
