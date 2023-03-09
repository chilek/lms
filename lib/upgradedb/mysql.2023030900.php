<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2023 LMS Developers
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
        'assignments',
        'presentation_format',
        'phpui',
        'assignment_presentation_format',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'assignments',
        'default_align_periods',
        'phpui',
        'default_assignment_align_periods',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'assignments',
        'default_at',
        'phpui',
        'default_assignment_at',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'assignments',
        'default_discount_type',
        'phpui',
        'default_assignment_discount_type',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'assignments',
        'default_document_type',
        'phpui',
        'default_assignment_invoice',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'assignments',
        'default_end_period_settlement',
        'phpui',
        'default_assignment_last_settlement',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'assignments',
        'default_list_dataset',
        'phpui',
        'default_assignment_list_dataset',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'assignments',
        'default_period',
        'phpui',
        'default_assignment_period',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'assignments',
        'default_begin_period_settlement',
        'phpui',
        'default_assignment_settlement',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'assignments',
        'default_existing_operation',
        'phpui',
        'default_existing_assignment_operation',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'assignments',
        'default_show_approved_only',
        'phpui',
        'default_show_approved_assignments_only',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'assignments',
        'default_show_expired',
        'phpui',
        'default_show_expired_assignments',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'assignments',
        'default_show_period',
        'phpui',
        'default_show_period_assignments',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'assignments',
        'missed_node_warning',
        'phpui',
        'node_assignment_warning',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'assignments',
        'default_liability_type',
        'phpui',
        'default_liability_type',
    )
);

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2023030900', 'dbversion'));

$this->CommitTrans();
