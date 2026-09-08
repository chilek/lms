<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2025 LMS Developers
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

$debtInterestPercentages = array(
    '1997.01.01' => 35.0,
    '1998.04.15' => 33.0,
    '1999.02.01' => 24.0,
    '1999.05.15' => 21.0,
    '2000.11.01' => 30.0,
    '2001.12.15' => 20.0,
    '2002.07.25' => 16.0,
    '2003.02.01' => 13.0,
    '2003.09.25' => 12.25,
    '2005.01.10' => 13.5,
    '2005.10.15' => 11.5,
    '2008.12.15' => 13.0,
    '2014.12.23' => 8.0,
    '2016.01.01' => 7.0,
    '2020.03.18' => 6.5,
    '2020.04.09' => 6.0,
    '2020.05.29' => 5.6,
    '2021.10.07' => 6.0,
    '2021.11.04' => 6.75,
    '2021.12.09' => 7.25,
    '2022.01.05' => 7.75,
    '2022.02.09' => 8.25,
    '2022.03.09' => 9.0,
    '2022.04.07' => 10.0,
    '2022.05.06' => 10.75,
    '2022.06.09' => 11.5,
    '2022.07.08' => 12.0,
    '2022.09.08' => 12.25,
    '2023.09.07' => 11.50,
    '2023.10.05' => 11.25,
    '2025.05.08' => 10.75,
    '2025.07.03' => 10.5,
    '2025.09.04' => 10.25,
);
$configurationVariableValue = implode(
    "\n",
    array_map(
        function ($date, $percentage) {
            return $date . ':' . $percentage;
        },
        array_keys($debtInterestPercentages),
        $debtInterestPercentages
    )
);

if (!$this->getOne("SELECT 1 FROM uiconfig WHERE section = ? AND var = ?", array('finances', 'debt_interest_percentages'))) {
    $this->Execute(
        "INSERT INTO uiconfig (section, var, value) VALUES (?, ?, ?)",
        array(
            'finances',
            'debt_interest_percentages',
            $configurationVariableValue,
        )
    );
} else {
    $this->Execute(
        "UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?",
        array(
            $configurationVariableValue,
            'finances',
            'debt_interest_percentages',
        )
    );
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2025091200', 'dbversion'));

$this->CommitTrans();
