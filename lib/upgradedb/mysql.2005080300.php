<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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

if($temp = $DB->GetOne('SELECT value FROM uiconfig WHERE section=? AND var=? AND disabled=0', 
		array('invoices', 'number_template')))
	$CONFIG['invoices']['number_template'] = $temp;

if($temp = $DB->GetOne('SELECT value FROM uiconfig WHERE section=? AND var=? AND disabled=0', 
		array('receipts', 'number_template')))
	$CONFIG['receipts']['number_template'] = $temp;

if($temp = $DB->GetOne('SELECT value FROM uiconfig WHERE section=? AND var=? AND disabled=0', 
		array('invoices', 'monthly_numbering')))
	$CONFIG['invoices']['monthly_numbering'] = $temp;

if($temp = $DB->GetOne('SELECT value FROM uiconfig WHERE section=? AND var=? AND disabled=0', 
		array('receipts', 'monthly_numbering')))
	$CONFIG['receipts']['monthly_numbering'] = $temp;

$DB->BeginTrans();

$DB->Execute("
    CREATE TABLE numberplans (
	id int(11) NOT NULL auto_increment,
	template varchar(255) NOT NULL DEFAULT '',
	period smallint NOT NULL DEFAULT '0',
	doctype int(11) NOT NULL DEFAULT '0',
	isdefault tinyint(1) NOT NULL DEFAULT '0',
	PRIMARY KEY (id))
");

$DB->Execute("INSERT INTO numberplans (template, period, doctype, isdefault) VALUES(?,?,1,1)", 
		array(str_replace('%M','%m',$CONFIG['invoices']['number_template']), $CONFIG['invoices']['monthly_numbering'] ? 3 : 5));
$DB->Execute("INSERT INTO numberplans (template, period, doctype, isdefault) VALUES(?,?,2,1)", 
		array(str_replace('%M','%m',$CONFIG['receipts']['number_template']), $CONFIG['receipts']['monthly_numbering'] ? 3 : 5));

$DB->Execute("ALTER TABLE documents ADD numberplanid int(11) NOT NULL DEFAULT '0'");
$DB->Execute("UPDATE documents SET numberplanid = 0");
$DB->Execute("UPDATE documents SET numberplanid = 1 WHERE type = 1");
$DB->Execute("UPDATE documents SET numberplanid = 2 WHERE type = 2");
$DB->Execute("ALTER TABLE documents ADD INDEX numberplanid (numberplanid)");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?",array('2005080300', 'dbversion'));

$DB->CommitTrans();

?>
