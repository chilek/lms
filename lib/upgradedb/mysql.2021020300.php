<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2021 LMS Developers
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


$this->Execute("
    CREATE TABLE numberplanusers (
        planid int(11) NOT NULL,
        userid int(11) NOT NULL,
        UNIQUE KEY numberplanusers_userid_ukey (planid, userid),
        INDEX numberplanusers_userid_idx (userid),
        CONSTRAINT numberplanusers_planid_fkey
           FOREIGN KEY (planid) REFERENCES numberplans (id) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT numberplanusers_userid_fkey
           FOREIGN KEY (userid) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB
");
