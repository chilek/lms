<?php

/*
 * LMS version 1.7-cvs
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

function DBLoad($filename=NULL)
{
	global $DB, $CONFIG;

	if(!$filename)
		return FALSE;
	$finfo = pathinfo($filename);
	$ext = $finfo['extension'];

	if ((extension_loaded('zlib'))&&($ext=='gz'))
		$file = gzopen($filename,'r'); //jezeli chcemy gz to plik najpierw trzeba rozpakowac
	else
		$file = fopen($filename,'r');

	$DB->BeginTrans(); // przyspieszmy dzia³anie je¿eli baza danych obs³uguje transakcje
	while(!feof($file))
	{
		$line = fgets($file,4096);
		if($line!='')
		{
			$line=str_replace(';\n','',$line);
			$DB->Execute($line);
		}
	}
	$DB->CommitTrans();

	if ((extension_loaded('zlib'))&&($ext=='gz'))
		gzclose($file);
	else
		fclose($file);

	// Okej, zróbmy parê bzdurek db depend :S
	// Postgres sux ! (warden)
	// Tak, a ³y¿ka na to 'niemo¿liwe' i polecia³a za wann± potr±caj±c bannanem musztardê (lukasz)

	switch($CONFIG['database']['type'])
	{
		case 'postgres':
			// actualize postgres sequences ...
			foreach($DB->ListTables() as $tablename)
				// ... where we have *_id_seq
				if(!in_array($tablename, array(
							'rtattachments',
							'dbinfo',
							'invoicecontents',
							'receiptcontents',
							'stats',
							'timestamps',
							'eventassignments',
							'sessions')))
					$DB->Execute("SELECT setval('".$tablename."_id_seq',max(id)) FROM ".$tablename);
		break;
	}
}

if(isset($_GET['is_sure']))
{
	if ($_GET['gz'])
		$LMS->DatabaseCreate(TRUE);
	else
		$LMS->DatabaseCreate();

	$db = $_GET['db'];

	if(file_exists($CONFIG['directories']['backup_dir'].'/lms-'.$db.'.sql'))
	{
		DBLoad($CONFIG['directories']['backup_dir'].'/lms-'.$db.'.sql');
	}
	elseif ((extension_loaded('zlib'))&&(file_exists($CONFIG['directories']['backup_dir'].'/lms-'.$db.'.sql.gz')))
	{
		DBLoad($CONFIG['directories']['backup_dir'].'/lms-'.$db.'.sql.gz');
	}
	
	$SESSION->redirect('?m='.$SESSION->get('lastmodule'));
}else{
	$layout['pagetitle'] = trans('Database Backup Recovery');
	$SMARTY->display('header.html');
	echo '<H1>'.trans('Database Backup Recovery').'</H1>';
	echo '<P>'.trans('Are you sure, you want to recover database created at $0?', date('Y/m/d H:i.s',$_GET['db'])).'</P>';
	echo '<A href="?m=dbrecover&db='.$_GET['db'].'&is_sure=1">'.trans('Yes, I am sure.').'</A>';
	$SMARTY->display('footer.html');
}

?>
