<?php
/*
 * LMS version 1.1-cvs
 *
 *  (C) Copyright 2001-2003 LMS Developers
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
function lms_parse_ini_file($filename, $process_sections = false) {
  $ini_array = array();
  $sec_name = "";
  $lines = file($filename);
  foreach($lines as $line) {
    $line = trim($line);

    if($line == "" || $line[0] == ";" || $line[0] == "#") {
      continue;
    }

    if($line[0] == "[" && $line[strlen($line) - 1] == "]") {
      $sec_name = trim(substr($line, 1, strlen($line) - 2));
    }
    else {
      $pos = strpos($line, "=");
      $property = trim(substr($line, 0, $pos));
      $value = trim(substr($line, $pos + 1)," \"'");

      if($process_sections) {
        $ini_array[$sec_name][$property] = $value;
      }
      else {
        $ini_array[$property] = $value;
      }
    }
  }

  return $ini_array;
}

?>


