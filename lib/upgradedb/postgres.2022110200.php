<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

if (!$this->ResourceExists('promotionattachments', LMSDB::RESOURCE_TYPE_TABLE)) {
    $this->Execute("CREATE SEQUENCE promotionattachments_id_seq");

    $this->Execute("
        CREATE TABLE promotionattachments (
            id integer DEFAULT nextval('promotionattachments_id_seq'::text) NOT NULL,
            filename varchar(255) NOT NULL,
            label varchar(255) NOT NULL,
            checked smallint DEFAULT 0,
            promotionid integer DEFAULT NULL
                CONSTRAINT promotionattachments_promotionid_fkey REFERENCES promotions (id) ON DELETE CASCADE ON UPDATE CASCADE,
            promotionschemaid integer DEFAULT NULL
                CONSTRAINT promotionattachments_promotionschemaid_fkey REFERENCES promotionschemas (id) ON DELETE CASCADE ON UPDATE CASCADE,
            PRIMARY KEY (id)
        )
    ");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2022110200', 'dbversion'));

$this->CommitTrans();
