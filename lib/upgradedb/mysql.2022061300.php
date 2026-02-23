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


if (!$this->ResourceExists('voip_cdr.cdate', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("ALTER TABLE voip_cdr ADD COLUMN cdate int(11) DEFAULT 0 NOT NULL");
    $this->Execute("CREATE INDEX voip_cdr_cdate_idx ON voip_cdr (cdate)");
    $this->Execute("UPDATE voip_cdr SET cdate = call_start_time + totaltime");

    $this->Execute("
        CREATE TRIGGER voip_cdr_insert_trigger BEFORE INSERT ON voip_cdr
            FOR EACH ROW
                BEGIN
            IF NEW.cdate = 0 THEN
                SET NEW.cdate = UNIX_TIMESTAMP();
            END IF;
        END
    ");
}
