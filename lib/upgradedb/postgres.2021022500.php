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


if (!$this->ResourceExists('netdevicemacs', LMSDB::RESOURCE_TYPE_TABLE)) {
    $this->Execute("
        CREATE SEQUENCE netdevicemacs_id_seq;
        CREATE TABLE netdevicemacs (
            id integer DEFAULT nextval('netdevicemacs_id_seq'::text) NOT NULL,
            netdevid integer NOT NULL
                CONSTRAINT netdevicemacs_netdevid_fkey REFERENCES netdevices (id) ON DELETE CASCADE ON UPDATE CASCADE,
            label varchar(30) NOT NULL,
            mac varchar(17) NOT NULL,
            main smallint DEFAULT 0 NOT NULL,
            PRIMARY KEY (id),
            CONSTRAINT netdevicemacs_mac_ukey UNIQUE (mac),
            CONSTRAINT netdevicemacs_netdevid_label_ukey UNIQUE (netdevid, label)
        );
        CREATE INDEX netdevicemacs_netdevid_idx ON netdevicemacs (netdevid);
        CREATE INDEX netdevicemacs_label_idx ON netdevicemacs (label)
    ");
}
