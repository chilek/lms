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


$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'timetable',
        'work_time_step',
        'phpui',
        'timetable_working_hours_interval',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'timetable',
        'work_time_hours',
        'phpui',
        'timetable_working_hours',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'timetable',
        'allow_past_events',
        'phpui',
        'timetable_allow_past_events',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'timetable',
        'show_delayed_events',
        'phpui',
        'timetable_overdue_events',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'timetable',
        'row_user_limit',
        'phpui',
        'timetable_user_row_limit',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'timetable',
        'distant_event_day_trigger',
        'phpui',
        'timetable_distant_event_day_trigger',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'timetable',
        'distant_event_restriction',
        'phpui',
        'timetable_distant_event_restriction',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'timetable',
        'hide_disabled_users',
        'phpui',
        'timetable_hide_disabled_users',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'timetable',
        'hide_deleted_users',
        'phpui',
        'timetable_hide_deleted_users',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'timetable',
        'hide_description',
        'phpui',
        'timetable_hide_description',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ? WHERE section = ? AND var = ?",
    array(
        'timetable',
        'phpui',
        'event_time_step',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ? WHERE section = ? AND var = ?",
    array(
        'timetable',
        'phpui',
        'event_user_required',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ? WHERE section = ? AND var = ?",
    array(
        'timetable',
        'phpui',
        'event_overlap_warning',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ? WHERE section = ? AND var = ?",
    array(
        'timetable',
        'phpui',
        'customer_event_limit',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'rt',
        'hide_disabled_users',
        'phpui',
        'helpdesk_hide_disabled_users',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'rt',
        'hide_deleted_users',
        'phpui',
        'helpdesk_hide_deleted_users',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'rt',
        'allow_empty_categories',
        'phpui',
        'helpdesk_allow_empty_categories',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'rt',
        'empty_category_warning',
        'phpui',
        'helpdesk_empty_category_warning',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'rt',
        'customer_notify',
        'phpui',
        'helpdesk_customer_notify',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'rt',
        'sender_email',
        'phpui',
        'helpdesk_sender_email',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'rt',
        'notification_attachments',
        'phpui',
        'helpdesk_notification_attachments',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'rt',
        'author_notify',
        'phpui',
        'helpdesk_author_notify',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'rt',
        'additional_user_permission_checks',
        'phpui',
        'helpdesk_additional_user_permission_checks',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'rt',
        'allow_all_users_modify_deadline',
        'phpui',
        'helpdesk_allow_all_users_modify_deadline',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'rt',
        'allow_change_ticket_state_from_open_to_new',
        'phpui',
        'helpdesk_allow_change_ticket_state_from_open_to_new',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'rt',
        'block_ticket_close_with_open_events',
        'phpui',
        'helpdesk_block_ticket_close_with_open_events',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'rt',
        'check_owner_verifier_conflict',
        'phpui',
        'helpdesk_check_owner_verifier_conflict',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'rt',
        'customer_notification_mail_subject',
        'phpui',
        'helpdesk_customer_notification_mail_subject',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ? WHERE section = ? AND var = ?",
    array(
        'rt',
        'phpui',
        'default_show_closed_tickets',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ? WHERE section = ? AND var = ?",
    array(
        'rt',
        'phpui',
        'category_adjustments_in_ticketedit',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ? WHERE section = ? AND var = ?",
    array(
        'rt',
        'phpui',
        'ticket_property_change_notify',
    )
);
