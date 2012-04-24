<?php

/*
* LMS version 1.11-git
*
* (C) Copyright 2001-2012 LMS Developers
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License Version 2 as
* published by the Free Software Foundation.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
* USA.
*
*/

$DB->BeginTrans();



$spr = $DB->Execute("SELECT value FROM uiconfig WHERE var = 'default_assignment_settlement' AND section = 'phpui'");
if (empty($spr)){
$DB->Execute("INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('phpui', 'default_assignment_settlement', '0', 'with settlement of first deficient period. (0-disabled, 1-enabled)', '0')");
};

$spr = $DB->Execute("SELECT value FROM uiconfig WHERE var = 'default_cutomer_assignment_nodes' AND section = 'phpui'");
if (empty($spr)){
$DB->Execute("INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('phpui', 'default_cutomer_assignment_nodes', '0', 'Assignment with all computers. (0-disabled, 1-enabled)', '0')");
};

$spr = $DB->Execute("SELECT value FROM uiconfig WHERE var = 'default_printpage' AND section = 'invoices'");
if (empty($spr)){
$DB->Execute("INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('invoices', 'default_printpage', 'original,copy', '', '0')");
};


$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2012041901', 'dbversion'));

$DB->CommitTrans();

?>
