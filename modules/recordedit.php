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

$domain=$DB->GetRow('SELECT domains.name FROM domains,records WHERE records.domain_id=domains.id and records.id = ?', array($id));


if (isset($_POST['record']))
{
        include("domainf.php");
	$record = $_POST['record'];
	$record['id'] = $id;
	$arpa_record_type_allowed=array("PTR","SOA","NS","TXT","CNAME","MX","SPF","NAPTR","URL","MBOXFW","CURL","SSHFP");
	


        $tlds=explode(".",$domain['name']);

        if ($tlds[count($tlds)-2].$tlds[count($tlds)-1]=="in-addrarpa"){ //domena in-add.arpa

            if (!is_numeric($record['name']) && $record['name']!="")
             $error['name']=trans("Wrong record name");

            if (!in_array($record['type'],$arpa_record_type_allowed))
             $error['type']=trans("Wrong record type");

            if (in_array($record['type'],array("PTR","NS"))) {
             $errorcontent=trans(is_not_valid_hostname_fqdn($record['content'],0,1));
             if ($errorcontent) $error['content']=$errorcontent;
           }


        }
         else {
             if ($record['type']=="PTR")
               $error['type']=trans("You can't add PTR record to this domain");
          }

        if ( $record['ttl']*1<=0 || !is_numeric($record['ttl']))
           $error['ttl']=trans("Wrong TTL");

        if ( empty ($record['content']))
           $error['content']=trans("Wrong Content");

        if ( !empty ($record['name'])){
        if ($errorname=trans(is_not_valid_hostname_fqdn($record['name'],1,0)))
          $error['name']=$errorname;
        }


        if ($record['type']=="SOA") {
          $soa=$DB->GetRow('select type from records where type="SOA" AND domain_id=(select domain_id from records where id=? AND type!="SOA")', array($id));
          if ($soa['type']=="SOA")
             $error['type']=trans("Reocrd SOA alredy exist");
        }



  if (!$error){

	if (trim($record['name'])!='' )
	  $record['name']=trim($record['name'],'.').'.';			

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

    $domainid=$DB->GetRow("SELECT domain_id from records WHERE records.id=$id");
    update_soa_serial($domainid['domain_id']);
    $SESSION->redirect('?m=recordslist');  
 }
}
  else {
    $record=$DB->GetRow('SELECT * FROM records WHERE id = ?', array($id));  
    $record['name']=substr($record['name'],0,-(strlen($domain['name'])+1));        
 }


$layout['pagetitle'] = trans('Record edit for zone:');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);


$record['content'] = htmlentities($record['content']);
$domain=$DB->GetRow('SELECT domains.name FROM domains,records WHERE records.domain_id=domains.id and records.id = ?', array($id));

$SMARTY->assign('error',$error);
$SMARTY->assign('record',$record );
$SMARTY->assign('domain',$domain );

$SMARTY->display('recordedit.html');

?>
