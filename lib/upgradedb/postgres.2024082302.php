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
    "UPDATE uiconfig SET section = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'phpui',
        'call_phone_url',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'phpui',
        'default_billing_address_state',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'phpui',
        'default_location_address_state',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'phpui',
        'default_postal_address_state',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'phpui',
        'default_status',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'ignore_deleted',
        'phpui',
        'ignore_deleted_customers',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'phpui',
        'legal_person_required_properties',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'phpui',
        'natural_person_required_properties',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'phpui',
        'pin_allowed_characters',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'pin_min_length',
        'phpui',
        'pin_min_size',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'pin_max_length',
        'phpui',
        'pin_max_size',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'phpui',
        'pin_restriction_description',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'phpui',
        'unsecure_pin_validity',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'phpui',
        'validate_changed_pin',
    )
);

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2024082302', 'dbversion'));

$this->CommitTrans();
