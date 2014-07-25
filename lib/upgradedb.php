<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

define('DBVERSION', '2014072500'); // here should be always the newest version of database!
				 // it placed here to avoid read disk every time when we call this file.

/*
 * This file contains procedures for upgradeing automagicly database.
 */

function getdir($pwd = './', $pattern = '^.*$')
{
	if ($handle = @opendir($pwd))
	{
		while (($file = readdir($handle)) !== FALSE)
			if(preg_match('/'.$pattern.'/',$file))
				$files[] = $file;
		closedir($handle);
	}
	return $files;
}

if($dbversion = $DB->GetOne('SELECT keyvalue FROM dbinfo WHERE keytype = ?',array('dbversion'))) {
	if(DBVERSION > $dbversion)
	{
		set_time_limit(0);
		$lastupgrade = $dbversion;
		$_dbtype = ConfigHelper::getConfig('database.type') == 'mysqli' ? 'mysql' : ConfigHelper::getConfig('database.type');

		$upgradelist = getdir(LIB_DIR.'/upgradedb/', '^'.$_dbtype.'.[0-9]{10}.php$');
		if(sizeof($upgradelist))
			foreach($upgradelist as $upgrade)
			{
				$upgradeversion = preg_replace('/^'.$_dbtype.'\.([0-9]{10})\.php$/','\1',$upgrade);

				if($upgradeversion > $dbversion && $upgradeversion <= DBVERSION)
					$pendingupgrades[] = $upgradeversion;
			}

		if(sizeof($pendingupgrades))
		{
			sort($pendingupgrades);
			foreach($pendingupgrades as $upgrade)
			{
				include(LIB_DIR.'/upgradedb/'.$_dbtype.'.'.$upgrade.'.php');
				if(!sizeof($DB->GetErrors()))
					$lastupgrade = $upgrade;
				else
					break;
			}
		}
	}
} else {
    $err_tmp = $DB->GetErrors(); // save current errors
    $DB->SetErrors();

    $dbinfo = $DB->GetOne('SELECT COUNT(*) FROM information_schema.tables WHERE table_name = ?', array('dbinfo'));                                            // check if dbinfo table exists (call by name)
    $tables = $DB->GetOne('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema NOT IN (?, ?)', array('information_schema', 'pg_catalog'));      // check if any tables exists in this database
    if ($dbinfo == 0 && $tables == 0 && count($DB->GetErrors()) == 0) {  // if there are no tables we can install lms database
        // detect database type and select schema dump file to load
        $schema = '';
        if ($DB->GetDbType() == LMSDB::POSTGRESQL) {
            $schema = 'lms.pgsql';
        } elseif ($DB->GetDbType() == LMSDB::MYSQL || $DB->GetDbType() == LMSDB::MYSQLI) {
            $schema = 'lms.mysql';
        } else
            die ('Could not determine database type!');

        if (! $sql = file_get_contents(SYS_DIR . '/doc/' . $schema))
            die ('Could not open database schema file '.SYS_DIR.'/'.$schema);

        if (! $DB->MultiExecute($sql))    // execute
            die ('Could not load database schema!');
    } else {
        $DB->SetErrors(array_merge($err_tmp, $DB->GetErrors())); // database might be installed so don't miss any error
    }
}

$layout['dbschversion'] = isset($lastupgrade) ? $lastupgrade : DBVERSION;

?>
