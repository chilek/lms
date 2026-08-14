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

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'list_page_limit',
        'phpui',
        'customerlist_pagelimit',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'list_default_order',
        'phpui',
        'customerlist_default_order',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'nodes',
        'list_page_limit',
        'phpui',
        'nodelist_pagelimit',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'nodes',
        'capitalize_names',
        'phpui',
        'capitalize_node_names',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'nodes',
        'default_auth_types',
        'phpui',
        'default_node_auth_types',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'nodes',
        'default_check_mac',
        'phpui',
        'default_node_check_mac',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'nodes',
        'disable_active_links_in_info',
        'phpui',
        'disable_active_links_in_nodeinfo',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'nodes',
        'empty_mac',
        'phpui',
        'node_empty_mac',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'nodes',
        'gps_coordinates_required',
        'phpui',
        'node_gps_coordinates_required',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'nodes',
        'link_technology_required',
        'phpui',
        'node_link_technology_required',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'nodes',
        'login_required',
        'phpui',
        'node_login_required',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'nodes',
        'login_regexp',
        'phpui',
        'node_login_regexp',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'nodes',
        'network_device_connection_required',
        'phpui',
        'node_to_network_device_connection_required',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'nodes',
        'name_regexp',
        'phpui',
        'node_name_regexp',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'nodes',
        'password_allowed_characters',
        'phpui',
        'node_password_allowed_characters',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'nodes',
        'password_length',
        'phpui',
        'node_password_length',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'nodes',
        'password_required',
        'phpui',
        'node_password_required',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'nodes',
        'password_required_for_auth_types',
        'phpui',
        'node_password_required_for_auth_types',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'nodes',
        'multi_tariff_restriction',
        'phpui',
        'node_multi_tariff_restriction',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'nodes',
        'public_ip',
        'phpui',
        'public_ip',
    )
);

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2024082600', 'dbversion'));

$this->CommitTrans();
