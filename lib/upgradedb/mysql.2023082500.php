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

if (!$this->GetOne(
    'SELECT 1 FROM uiconfig WHERE section = ? AND var = ?',
    array(
        'rt',
        'parser_default_queue',
    )
)) {
    $config = $this->GetRow(
        'SELECT section, var, value, disabled FROM uiconfig WHERE section = ? AND var = ?',
        array(
            'rt',
            'default_queue',
        )
    );

    if (!empty($config)) {
        $this->Execute(
            'INSERT INTO uiconfig (section, var, value, disabled) VALUES (?, ?, ?, ?)',
            array(
                'rt',
                'parser_default_queue',
                $config['value'],
                $config['disabled'],
            )
        );
    }
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2023082500', 'dbversion'));

$this->CommitTrans();
