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



if (isset($_POST['record'])){   

   $record=$_POST['record'];
   $arpa_record_type_allowed=array("PTR","SOA","NS","TXT");

    
                            
 
	$domain=$DB->GetRow('SELECT name FROM domains WHERE id = ?', array($record['domain_id']));
        
        $tlds=explode(".",$domain['name']);
         
        if ($tlds[count($tlds)-2].$tlds[count($tlds)-1]=="in-addrarpa"){ //domena in-add.arpa            
           
            if (!is_numeric($record['name']))     
             $error['name']="Wrong record name";        
             
            if (!in_array($record['type'],$arpa_record_type_allowed)) 
             $error['type']="Wrong record type";                    
             
            if (in_array($record['type'],array("PTR","NS"))) {
	     include('domainf.php');                  
	     $errorcontent=trans(is_not_valid_hostname_fqdn($record['content'],0,1));
             if ($errorcontent) $error['content']=$errorcontent;
           }

        
        }
         else {
             if ($record['type']=="PTR")
               $error['type']="You can't add PTR record to this domain";
          }

	if ( $record['ttl']*1<=0 || !is_numeric($record['ttl']))
	   $error['ttl']="Wrong TTL";
	   
	if ($record['type']=="SOA") {
	  $soa=$DB->GetRow('SELECT type from records where domain_id=?', array($record['domain_id']));
	  if ($soa['type']=="SOA")
	     $error['type']="Reocrd SOA alredy exist";
	}

              
	
   if (!$error) {
   
        if (trim($record['name'])!='' )
           $record['name']=trim($record['name'],'.').'.';   
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
        
   include("domainf.php");
   update_soa_serial($record['domain_id']);

   $SESSION->redirect('?m=recordslist');  
   }
} 
  else
  $record['prio']=0;

$d = $_GET['d']*1;

$layout['pagetitle'] = trans('Record add to zone');

 if (empty($record['ttl'])) {
              $record['ttl']=$CONFIG['zones']['default_ttl'];             
              $error['ttl']="";
              }

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('domain_id', $d);
$SMARTY->assign('record', $record);
$SMARTY->assign('error',$error);
$SMARTY->assign('domain', $DB->GetRow('SELECT name FROM domains WHERE id = ?', array($d)));
$SMARTY->display('recordadd.html');

?>
