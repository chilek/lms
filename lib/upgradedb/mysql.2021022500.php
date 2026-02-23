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
        CREATE TABLE netdevicemacs (
            id int(11) NOT NULL auto_increment,
            netdevid int(11) NOT NULL,
            label varchar(30) NOT NULL,
            mac varchar(17) NOT NULL,
            main tinyint DEFAULT '0' NOT NULL,
            PRIMARY KEY (id),       
            CONSTRAINT netdevicemacs_netdevid_fkey
                FOREIGN KEY (netdevid) REFERENCES netdevices (id) ON DELETE CASCADE ON UPDATE CASCADE,
            UNIQUE KEY netdevicemacs_mac_ukey (mac),
            UNIQUE KEY netdevicemacs_netdevid_label_ukey (netdevid, label),
            INDEX netdevicemacs_netdevid_idx (netdevid),
            INDEX netdevicemacs_label_idx (label)
        )
    ");
}
