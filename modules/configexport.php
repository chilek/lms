<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

function write_ini_file($configs_arr)
{
    $content = "";
    foreach ($configs_arr as $key => $elem) {
        $content .= "[" . $key . "]\n";
        foreach ($elem as $key2 => $elem2) {
            if (is_array($elem2)) {
                for ($i = 0; $i < count($elem2); $i++) {
                    $content .= $key2 . "[] = \"" . $elem2[$i] . "\"\n";
                }
            } else if ($elem2 == "") {
                $content .= $key2 . " = \n";
            } else {
                $content .= $key2 . " = \"" . $elem2 . "\"\n";
            }
        }
    }
    print $content;
}

if (isset($_POST['marks'])) {
    $options = Utils::filterIntegers($_POST['marks']);
    if (!empty($options)) {
        $configs = array();
        foreach ($options as $idx => $option) {
            $variable = $LMS->GetConfigVariable($idx);
            $configs[$variable['section']][$variable['var']] = $variable['value'];
        }
    }
}

if (!empty($configs)) {
    $cdate = date('YmdHi', time());
    $filename = 'configexport_';
    if (!isset($_GET['source-division']) && !isset($_GET['source-user'])) {
        $filename .= trans('global value');
    }
    if (isset($_GET['source-division']) && !isset($_GET['source-user'])) {
        $filename .= str_replace(' ', '', $_GET['source-division']);
    }
    if (isset($_GET['source-division']) && isset($_GET['source-user'])) {
        $filename .= str_replace(' ', '', $_GET['source-division']) . '_' . str_replace(' ', '', $_GET['source-user']);
    }
    if (!isset($_GET['source-division']) && isset($_GET['source-user'])) {
        $filename .= str_replace(' ', '', $_GET['source-user']);
    }
    $filename .= '_' .$cdate. '.cfg';

    // wysyłamy ...
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename='.$filename);
    header('Pragma: public');

    write_ini_file($configs);
}
