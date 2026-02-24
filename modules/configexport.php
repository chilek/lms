<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2021 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
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
 *  $Id$
 */

$fileType = null;
if (empty($_GET['fileType'])) {
    die();
} else {
    $fileType = $_GET['fileType'];
}

$lms = LMS::getInstance();

function write_ini_file($configs_arr)
{
    $content = "";
    foreach ($configs_arr as $key => $elem) {
        $content .= "[" . $key . "]\n";
        foreach ($elem as $key2 => $elem2) {
            if (is_array($elem2)) {
                for ($i = 0; $i < count($elem2); $i++) {
                    $content .= $key2 . "[] = \"" . str_replace('"', '\"', $elem2[$i]) . "\"\n";
                }
            } else if ($elem2 == "") {
                $content .= $key2 . " = \n";
            } else {
                $content .= $key2 . " = \"" . str_replace('"', '\"', $elem2) . "\"\n";
            }
        }
    }
    print $content;
}

function write_sql_file($configs_arr)
{
    $content = "";
    foreach ($configs_arr as $key => $elem) {
        unset($elem['id'], $elem['userid'], $elem['configid'], $elem['divisionid']);
        $content .= "INSERT INTO uiconfig (section, var, value, description, disabled, type)";
        $content .= " VALUES ("
            . "'" . $elem['section'] . "',"
            . "'" . $elem['var'] . "',"
            . "'" . $elem['value'] . "',"
            . "'" . $elem['description'] . "',"
            . $elem['disabled'] . ","
            . $elem['type'] . ");". PHP_EOL;
    }
    print $content;
}

if (isset($_POST['marks'])) {
    $options = Utils::filterIntegers($_POST['marks']);
    if (!empty($options)) {
        $configs = array();
        foreach ($options as $idx => $option) {
            $variable = $lms->GetConfigVariable($idx);
            if ($fileType == 'ini') {
                $configs[$variable['section']][$variable['var']] = $variable['value'];
            } else {
                $configs[] = $variable;
            }
        }
    }
}

if (!empty($configs)) {
    $cdate = date('YmdHi', time());
    $filename = 'configexport';

    if ($fileType == 'ini') {
        if (count($configs) == 1) {
            reset($configs);
            $section = key($configs);
            $filename .= '-' . $section;
        }
    } else {
        $section = array_unique(array_column($configs, 'section'));
        $distinctSections = count($section);
        if (count($section) == 1) {
            $filename .= '-' . $section[0];
        }
    }
    if (!isset($_GET['source-division']) && !isset($_GET['source-user'])) {
        $filename .= '-' . trans('global value');
    }
    if (isset($_GET['source-division']) && !isset($_GET['source-user'])) {
        $filename .= '-' . $_GET['source-division'];
    }
    if (isset($_GET['source-division'], $_GET['source-user'])) {
        $filename .= '-' . $_GET['source-division'] . '-' . $_GET['source-user'];
    }
    if (!isset($_GET['source-division'], $_GET['source-user'])) {
        $filename .= '-' . $_GET['source-user'];
    }
    $filename .= '-' . $cdate;

    $filename = clear_utf($filename);
    $fileExtension = ($fileType == 'ini' ? '.ini' : '.sql');
    $filename = str_replace(' ', '', $filename) . $fileExtension;

    // wysyłamy ...
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: public');

    if ($fileType == 'ini') {
        write_ini_file($configs);
    } else {
        write_sql_file($configs);
    }
}
