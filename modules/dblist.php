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

$layout['pagetitle'] = trans('Database Backups');

if ($handle = opendir(ConfigHelper::getConfig('directories.backup_dir')))
{
	while (false !== ($file = readdir($handle)))
	{
		if ($file != '.' && $file != '..')
		{
			$path = pathinfo($file);
			
			if(!isset($path['extension']))
				continue;
			
			if($path['extension'] == 'sql')
			{
				if(substr($path['basename'],0,4) == 'lms-')
				{
					$name = substr(basename($file,'.sql'),4,25);
					if($pos = strpos($name,'-'))
					{
						$dblist['dbv'][]  = substr($name, $pos+1);
						$dblist['time'][] = substr($name, 0, $pos);
					} 
					else
					{
						$dblist['dbv'][]  = '';
						$dblist['time'][] = (int) $name;
					}
					
					$dblist['name'][] = $name;
					$dblist['size'][] = $filesize = filesize(ConfigHelper::getConfig('directories.backup_dir').'/'.$file);
					list ($hsize, $hunit) = setunits($filesize);
					$dblist['hsize'][] = f_round($hsize) . ' ' . $hunit;
					$dblist['type'][] = 'plain';
				}
			}
			elseif(extension_loaded('zlib'))
			{
				if((($path['extension'] == 'gz')&&(strstr($file, "sql.gz")))&& (substr($path['basename'],0,4) == 'lms-'))
				{
					$name = substr(basename($file,'.sql.gz'),4,25);
					if($pos = strpos($name,'-'))
					{
						$dblist['dbv'][]  = substr($name, $pos+1);
						$dblist['time'][] = substr($name, 0, $pos);
					} 
					else
					{
						$dblist['dbv'][]  = FALSE;
						$dblist['time'][] = (int) $name;
					}
					$dblist['name'][] = $name;
					$dblist['size'][] = $filesize = filesize(ConfigHelper::getConfig('directories.backup_dir').'/'.$file);
					list ($hsize, $hunit) = setunits($filesize);
					$dblist['hsize'][] = f_round($hsize) . ' ' . $hunit;
					$dblist['type'][] = 'gz';
				}
			}
		}
	}
	closedir($handle);
}

if(isset($dblist['time']))
	array_multisort($dblist['time'],$dblist['size'],$dblist['type'],$dblist['dbv'],$dblist['name'],$dblist['hsize']);

$dblist['total'] = isset($dblist['time']) ? count($dblist['time']) : 0;

$SMARTY->assign('dblist', $dblist);
$SMARTY->display('dblist.html');

?>
