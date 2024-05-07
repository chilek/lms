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

if (!$this->ResourceExists('cryptokeys.published', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("
        ALTER TABLE domains ALTER COLUMN notified_serial TYPE bigint;
        ALTER TABLE domains ADD CONSTRAINT domains_name_check CHECK (((name)::text = LOWER((name)::text)));
        ALTER TABLE records ADD CONSTRAINT records_name_check CHECK (((name)::text = LOWER((name)::text)));
        CREATE INDEX records_name_idx ON records (name);
        CREATE INDEX records_domain_id_ordername_idx ON records (domain_id, ordername text_pattern_ops);
        ALTER TABLE cryptokeys ADD COLUMN published boolean DEFAULT 't'
    ");
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2024050700', 'dbversion'));

$this->CommitTrans();
