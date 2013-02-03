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

if(isset($_GET['is_sure']))
{
	$basename = 'lms-'.$_GET['db'];
	
	if(@file_exists($CONFIG['directories']['backup_dir'].'/'.$basename.'.sql'))
	{
		@unlink($CONFIG['directories']['backup_dir'].'/'.$basename.'.sql');
	}
	elseif((extension_loaded('zlib'))&&((@file_exists($CONFIG['directories']['backup_dir'].'/'.$basename.'.sql.gz'))))
	{
		@unlink($CONFIG['directories']['backup_dir'].'/'.$basename.'.sql.gz');
	}

	$SESSION->redirect('?m=dblist');
} 
else
{
	$layout['pagetitle'] = trans('Backup Delete');
	$SMARTY->display('header.html');
	echo '<H1>'.trans('Deletion of Database Backup').'</H1>';
	echo '<P>'.trans('Are you sure, you want to delete database backup created at $a ?',date('Y/m/d H:i.s',$_GET['db'])).'</P>';
	echo '<a href="?m=dbdel&db='.$_GET['db'].'&is_sure=1">'.trans('Yes, I am sure.').'</A>';
	$SMARTY->display('footer.html');
}

?>
