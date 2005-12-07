<?php

/*
 * LMS version 1.9-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

function DatabaseFetchContent($db,$save=FALSE)
{
	global $DB, $CONFIG;
	
	if(file_exists($CONFIG['directories']['backup_dir'].'/lms-'.$db.'.sql'))
	{		
		$database['content'] = '';
		if($content = file($CONFIG['directories']['backup_dir'].'/lms-'.$db.'.sql'))
			foreach($content as $value)
				$database['content'] .= $value;
		$database['size'] = filesize($CONFIG['directories']['backup_dir'].'/lms-'.$db.'.sql');
		$database['name'] = $db;
		list($database['time']) = explode('-',$db);
		return $database;
	}
	elseif((extension_loaded('zlib'))&&(file_exists($CONFIG['directories']['backup_dir'].'/lms-'.$db.'.sql.gz')))
	{
		if($save==TRUE)
		{
			$file=fopen($CONFIG['directories']['backup_dir'].'/lms-'.$db.'.sql.gz',"r"); //tutaj przepisuje plik binarny 
			$database = '';
			while($part = fread($file,8192))
                            	$database .= $part; 
		}
		else
		{
			$database['content'] = '';
			if($content = gzfile($CONFIG['directories']['backup_dir'].'/lms-'.$db.'.sql.gz'))
				foreach($content as $value)
                        		$database['content'] .= $value;
                	$database['size'] = filesize($CONFIG['directories']['backup_dir'].'/lms-'.$db.'.sql.gz');
                	$database['name'] = $db;
			list($database['time']) = explode('-',$db);
		}
                return $database;
	}
	else
		return FALSE;
}

$layout['pagetitle'] = trans('View Database Backup');

if ((extension_loaded('zlib'))&&(strstr($_GET['file'],"sql.gz")))
{
	$filecontent = DatabaseFetchContent($_GET['db'],true); //dodalem parametr bool na koncu ktory mowi czy gzipowac czy nie
	$database = DatabaseFetchContent($_GET['db']);
}
else
	$database = DatabaseFetchContent($_GET['db']);

if(isset($_GET['rawmode']))
{
	$database['rawmode'] = TRUE;
	if(isset($_GET['save']))
	{
		header('Content-Type: application/octetstream');
		if ((extension_loaded('zlib'))&&($_GET['rawmode']=='true')&&($_GET['save']=='true')&&(strstr($_GET['file'],"sql.gz")))
		{
			header('Content-Disposition: attachment; filename=lms-backup-'.date('Ymd-His',$_GET['db']).'.sql.gz');
			header('Pragma: public');
				print $filecontent;
			return TRUE;
		}
		else
		{
			header('Content-Disposition: attachment; filename=lms-backup-'.date('Ymd-His',$_GET['db']).'.sql');
			Header('Pragma: public');
		}
	}
	else
		header('Content-Type: text/plain; charset='.$LANGDEFS[$LMS->lang]['charset']);
}

$SMARTY->assign('database',$database);

if(!isset($_GET['rawmode']))
	$SMARTY->display('header.html');

if (strstr($_GET['file'],"sql.gz"))
	$SMARTY->assign('use_gzip','true');

$SMARTY->display('dbview.html');

if(!isset($_GET['rawmode']))
	$SMARTY->display('footer.html');

?>
