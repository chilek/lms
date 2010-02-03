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

$id = $_GET['id']*1;

if (isset($_POST['record']))
{
	$record = $_POST['record'];
	$record['id'] = $id;
	
	$domain=$DB->GetRow('SELECT domains.name FROM domains,records WHERE records.domain_id=domains.id and records.id = ?', array($id));

	if (trim($record['name'])!='' )
	   $record['name']=trim($record['name'],'.').'.';	
	
	if ($record['type']=="PTR")
	   $record['name']='';
	
	 $record['name'].=$domain['name'];
        

	$DB->Execute('UPDATE records SET name = ?, type = ?, content = ?,ttl = ?, prio = ?
		WHERE id = ?',
		array( $record['name'],
		        $record['type'], 
			$record['content'],
			$record['ttl'],
			$record['prio'],					
			$record['id']
	));


include("domainf.php");

$domainid=$DB->GetRow("SELECT domain_id from records WHERE records.id=$id");

update_soa_serial($domainid['domain_id']);


$SESSION->redirect('?m=recordslist');  
}


$layout['pagetitle'] = trans('Record edit for zone:');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$record=$DB->GetRow('SELECT * FROM records WHERE id = ?', array($id));

$record['content'] = htmlentities($record['content']);

$domain=$DB->GetRow('SELECT domains.name FROM domains,records WHERE records.domain_id=domains.id and records.id = ?', array($id));
$record['name']=substr($record['name'],0,-(strlen($domain['name'])+1));


$SMARTY->assign('record',$record );
$SMARTY->assign('domain',$domain );

$SMARTY->display('recordedit.html');

?>
