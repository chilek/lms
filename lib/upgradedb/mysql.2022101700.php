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

$this->BeginTrans();

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'promotions',
        'activation_at_next_day',
        'phpui',
        'promotion_activation_at_next_day',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'promotions',
        'activation_at_same_day',
        'phpui',
        'promotion_activation_at_same_day',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'promotions',
        'allow_modify_values_for_privileged_user',
        'phpui',
        'promotion_allow_modify_values_for_privileged_user',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'promotions',
        'force_at_next_day',
        'phpui',
        'promotion_force_at_next_day',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'promotions',
        'preserve_at_day',
        'phpui',
        'promotion_preserve_at_day',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'promotions',
        'schema_all_terminal_check',
        'phpui',
        'promotion_schema_all_terminal_check',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'promotions',
        'schema_name_limit',
        'phpui',
        'promotion_schema_name_limit',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'promotions',
        'show_period_values',
        'phpui',
        'promotion_show_period_values',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'promotions',
        'tariff_duplicates',
        'phpui',
        'promotion_tariff_duplicates',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'promotions',
        'use_discounts',
        'phpui',
        'promotion_use_discounts',
    )
);

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2022101700', 'dbversion'));

$this->CommitTrans();
