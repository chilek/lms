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

define(DBVERSION, '2004111300'); // here should be always the newest version of database!
// it placed here to avoid read disk every time when we call this file.

/*
 * This file contains procedures for upgradeing automagicly database.
 */

if($dbversion = $DB->GetOne('SELECT keyvalue FROM dbinfo WHERE keytype = ?',array('dbversion')))
	if(DBVERSION > $dbversion)
	{
		$lastupgrade = $dbversion;
		
		$upgradelist = getdir($_LIB_DIR.'/upgradedb/', '^'.$_DBTYPE.'.[0-9]{10}.php$');
		if(sizeof($upgradelist))
			foreach($upgradelist as $upgrade)
			{
				$upgradeversion = ereg_replace('^'.$_DBTYPE.'.([0-9]{10}).php$','\1',$upgrade);
				
				if($upgradeversion > $dbversion)
					$pendingupgrades[] = $upgradeversion;
			}
			
		if(sizeof($pendingupgrades))
		{
			sort($pendingupgrades);
			foreach($pendingupgrades as $upgrade)
			{	
				include($_LIB_DIR.'/upgradedb/'.$_DBTYPE.'.'.$upgrade.'.php');
				if(!sizeof($DB->errors))
					$lastupgrade = $upgrade;
				else
					break;
			}
		}
	}

$layout['dbschversion'] = $lastupgrade ? $lastupgrade : DBVERSION;

?>
