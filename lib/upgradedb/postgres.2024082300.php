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
        'groups_required_on_add',
        'phpui',
        'add_customer_group_required',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'capitalize_names',
        'phpui',
        'capitalize_customer_names',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'event_limit',
        'phpui',
        'customer_event_limit',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'identity_card_expiration_check',
        'phpui',
        'customer_identity_card_expiration_check',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'invoice_notice_consent_check',
        'phpui',
        'customer_invoice_notice_consent_check',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'karma_change_interval',
        'phpui',
        'customer_karma_change_interval',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'ssn_existence_check',
        'phpui',
        'customer_ssn_existence_check',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'ssn_existence_scope',
        'phpui',
        'customer_ssn_existence_scope',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'ten_existence_check',
        'phpui',
        'customer_ten_existence_check',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'ten_existence_scope',
        'phpui',
        'customer_ten_existence_scope',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'default_consents',
        'phpui',
        'default_customer_consents',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'default_document_memo',
        'phpui',
        'default_customer_document_memo',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'default_email_flags',
        'phpui',
        'default_customer_email_flags',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'default_flags',
        'phpui',
        'default_customer_flags',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'default_phone_flags',
        'phpui',
        'default_customer_phone_flags',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'default_type',
        'phpui',
        'default_customer_type',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'delete_related_resources',
        'phpui',
        'delete_related_customer_resources',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'disable_contacts_on_delete',
        'phpui',
        'disable_contacts_during_customer_delete',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'node_access_change_allowed_on_statuses',
        'phpui',
        'node_access_change_allowed_customer_statuses',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'reuse_id',
        'phpui',
        'reuse_customer_id',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'show_due_balance',
        'phpui',
        'show_customer_due_balance',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'show_expired_balance',
        'phpui',
        'show_customer_expired_balance',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'customers',
        'supported_contact_types',
        'phpui',
        'supported_customer_contact_types',
    )
);

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2024082300', 'dbversion'));

$this->CommitTrans();
