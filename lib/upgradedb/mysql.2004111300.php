<?php

/*
 * LMS version 1.5-cvs
 *
 *  (C) Copyright 2001-2004 LMS Developers
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

$DB->Execute("
    CREATE TABLE `passwd` (
	`id` int(11) NOT NULL auto_increment,
	`OwnerId` int(11) NOT NULL default '0',
	`user` varchar(200) NOT NULL default '',
        `password` varchar(200) NOT NULL default '',
	`LastLogin` timestamp(14) NOT NULL,
	`uid` int(11) NOT NULL default '0',
	`home` varchar(25) NOT NULL default '',
	PRIMARY KEY  (`id`)
    ) TYPE=MyISAM
");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?",array('2004111300', 'dbversion'));

?>
