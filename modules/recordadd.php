<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2009 Webvisor Sp. z o.o.
 *
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
 */

if (isset($_POST['record']))
{
	$record=$_POST['record'];


	$domain=$DB->GetRow('SELECT name FROM domains WHERE id = ?', array($record['domain_id']));

        if (trim($record['name'])!='' )
           $record['name']=trim($record['name'],'.').'.';
                   
        if ($record['type']=="PTR")
           $record['name']='';
  
       $record['name'].=$domain['name'];                                                                                              


	$DB->Execute('INSERT INTO records (name, type, content, ttl, prio, domain_id)
                  VALUES (?, ?, ?, ?, ?, ?)',
                  array(  
                  $record['name'],
                  $record['type'],
                  $record['content'],
                  $record['ttl'],
                  $record['prio'],
                  $record['domain_id']
        ));

	$SESSION->redirect('?m=recordslist');  
} 
  else
  $record['prio']=0;

$d = $_GET['d']*1;

$layout['pagetitle'] = trans('Record add to zone');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('domain_id', $d);
$SMARTY->assign('record', $record);
$SMARTY->assign('domain', $DB->GetRow('SELECT name FROM domains WHERE id = ?', array($d)));
$SMARTY->display('recordadd.html');

?>
