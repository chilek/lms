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
        'uke',
        'siis_header',
        'siis',
        'header',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'uke',
        'sidusis_operator_offer_url',
        'sidusis',
        'operator_offer_url',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'uke',
        'sidusis_operator_project_email',
        'sidusis',
        'operator_project_email',
    )
);

$this->Execute(
    "UPDATE uiconfig SET section = ?, var = ? WHERE section = ? AND var = ?",
    array(
        'uke',
        'sidusis_operator_project_phone',
        'sidusis',
        'operator_project_phone',
    )
);

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2023082800', 'dbversion'));

$this->CommitTrans();
