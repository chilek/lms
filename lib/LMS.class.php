<?php

/*
 * LMS version 1.1-cvs
 *
 *  (C) Copyright 2001-2003 LMS Developers
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

// LMS Class - contains internal LMS database functions used
// to fetch data like usernames, searching for mac's by ID, etc..

class LMS
{

     var $ADB;          // obiekt ADOdb
     var $SESSION;          // obiekt z Session.class.php (zarz±dzanie sesj±)
     var $CONFIG;          // tablica zawieraj±ca zmienne z lms.ini
     var $_version = NULL;     // wersja klasy

     function LMS($DB,$SESSION) // ustawia zmienne klasy
     {
          $this->_version = eregi_replace('^.Revision: ([0-9.]+).*','\1','$Revision$');
          $this->SESSION = $SESSION;
          $this->DB = $DB;
     }

     /*
      *  Funkcje bazodanowe (backupy, timestampy)
      */

     function SetTS($table) // ustawia timestamp tabeli w tabeli 'timestamps'
     {
          if($this->DB->GetOne('SELECT * FROM timestamps WHERE tablename=?',array($table)))
               $this->DB->Execute('UPDATE timestamps SET time = ?NOW? WHERE tablename=?',array($table));
          else
               $this->DB->Execute('INSERT INTO timestamps (tablename, time) VALUES (?, ?NOW?)',array($table));

          if($this->DB->GetOne('SELECT * FROM timestamps WHERE tablename=?',array('_global')))
               $this->DB->Execute('UPDATE timestamps SET time = ?NOW? WHERE tablename=?',array('_global'));
          else
               $this->DB->Execute('INSERT INTO timestamps (tablename, time) VALUES (?, ?NOW?)',array('_global'));
     }

     function GetTS($table) // zwraca timestamp tabeli zapisany w tabeli 'timestamps'
     {
          return $this->DB->GetOne("SELECT time FROM timestamps WHERE tablename=?",array($table));
     }

     function DeleteTS($table) // usuwa timestamp tabeli zapisany w tabeli 'timestamps'
     {
          return $this->DB->Execute("DELETE FROM timestamps WHERE tablename=?",array($table));
     }

     function DatabaseList() // zwraca listê kopii baz danych w katalogu z backupami
     {
          if ($handle = opendir($this->CONFIG['backup_dir']))
          {
               while (false !== ($file = readdir($handle)))
               {
                    if ($file != "." && $file != "..")
                    {
                         $path = pathinfo($file);
                         if($path['extension'] = "sql")
                         {
                              if(substr($path['basename'],0,4)=="lms-")
                              {
                                   $dblist['time'][] = substr(basename("$file",".sql"),4);
                                   $dblist['size'][] = filesize($this->CONFIG['backup_dir']."/".$file);
                              }
                         }
                    }
               }
               closedir($handle);
          }
          if(sizeof($dblist['time']))
               array_multisort($dblist['time'],$dblist['size']);
          $dblist['total'] = sizeof($dblist['time']);
          return $dblist;
     }

     function DatabaseRecover($dbtime) // wczytuje backup bazy danych o podanym timestampie
     {
          if(file_exists($this->CONFIG['backup_dir'].'/lms-'.$dbtime.'.sql'))
          {
               return $this->DBLoad($this->CONFIG['backup_dir'].'/lms-'.$dbtime.'.sql');
          }
          else
               return FALSE;
     }

     function DBLoad($filename=NULL) // wczytuje plik z backupem bazy danych
     {
          if(!$filename)
               return FALSE;
          $file = fopen($filename,"r");
          $this->DB->BeginTrans(); // przyspieszmy dzia³anie je¿eli baza danych obs³uguje transakcje
          while(!feof($file))
          {
               $line = fgets($file,4096);
               if($line!="")
               {
                    $line=str_replace(";\n","",$line);
                    $this->DB->Execute($line);
               }
          }
          $this->DB->CommitTrans();
          fclose($file);

          // Okej, zróbmy parê bzdurek db depend :S
          // Postgres sux ! (warden)
          // Tak, a ³y¿ka na to 'niemo¿liwe' i polecia³a za wann± potr±caj±c bannanem musztardê (lukasz)

          switch($this->DB->databaseType)
          {
               case "postgres":
                    // uaktualnijmy sequencery postgresa
                    foreach($this->DB->ListTables() as $tablename)
                         $this->DB->Execute("SELECT setval('".$tablename."_id_seq',max(id)) FROM ".$tablename);
               break;
          }
     }

     function DBDump($filename=NULL) // zrzuca bazê danych do pliku
     {
          if(!$filename)
               return FALSE;
          if($dumpfile = fopen($filename,"w"))
          {
               foreach($this->DB->ListTables() as $tablename)
               {
                    fputs($dumpfile,"DELETE FROM $tablename;\n");
                    if($dump = $this->DB->GetAll("SELECT * FROM ".$tablename))
                         foreach($dump as $row)
                         {
                              fputs($dumpfile,"INSERT INTO $tablename (");
                              foreach($row as $field => $value)
                              {
                                   $fields[] = $field;
                                   $values[] = "'".addcslashes($value,"\r\n\'\"\\")."'";
                              }
                              fputs($dumpfile,implode(", ",$fields));
                              fputs($dumpfile,") VALUES (");
                              fputs($dumpfile,implode(", ",$values));
                              fputs($dumpfile,");\n");
                              unset($fields);
                              unset($values);
                         }
               }
               fclose($dumpfile);
          }
          else
               return FALSE;
     }

     function DatabaseCreate() // wykonuje zrzut kopii bazy danych
     {
          return $this->DBDump($this->CONFIG['backup_dir'].'/lms-'.time().'.sql');
     }

     function DatabaseDelete($dbtime) // usuwa plik ze zrzutem
     {
          if(@file_exists($this->CONFIG['backup_dir'].'/lms-'.$dbtime.'.sql'))
          {
               return @unlink($this->CONFIG['backup_dir'].'/lms-'.$dbtime.'.sql');
          }
          else
               return FALSE;
     }

     function DatabaseFetchContent($dbtime) // zwraca zawarto¶æ tekstow± kopii bazy danych
     {
          if(file_exists($this->CONFIG['backup_dir'].'/lms-'.$dbtime.'.sql'))
          {
               $content = file($this->CONFIG['backup_dir'].'/lms-'.$dbtime.'.sql');
               foreach($content as $value)
                    $database['content'] .= $value;
               $database['size'] = filesize($this->CONFIG['backup_dir'].'/lms-'.$dbtime.'.sql');
               $database['time'] = $dbtime;
               return $database;
          }
          else
               return FALSE;
     }

     /*
      *  Zarz±dzanie kontami administratorów
      */

     function SetAdminPassword($id,$passwd) // ustawia has³o admina o id równym $id na $passwd
     {
          $this->SetTS("admins");
          $this->DB->Execute("UPDATE admins SET passwd=? WHERE id=?",array(crypt($passwd),$id));
     }

     function GetAdminName($id) // zwraca imiê admina
     {
          return $this->DB->GetOne("SELECT name FROM admins WHERE id=?",array($id));
     }

     function GetAdminList() // zwraca listê administratorów
     {

         $query = "SELECT id, login, name, lastlogindate, lastloginip FROM admins ORDER BY login ASC";
         if($adminslist = $this->DB->GetAll($query))
          {
               foreach($adminslist as $idx => $row)
               {
                    if($row['lastlogindate'])
                         $adminslist[$idx]['lastlogin'] = date("Y/m/d H:i",$row['lastlogindate']);
                    else
                         $adminslist[$idx]['lastlogin'] = "-";

                    if(check_ip($row['lastloginip']))
                         $adminslist[$idx]['lastloginhost'] = gethostbyaddr($row['lastloginip']);
                    else
                    {
                         $adminslist[$idx]['lastloginhost'] = "-";
                         $adminslist[$idx]['lastloginip'] = "-";
                    }
               }
          }

          $adminslist['total'] = sizeof($adminslist);
          return $adminslist;
     }

     function GetAdminIDByLogin($login) // zwraca id admina na podstawie loginu
     {
          return $this->DB->GetOne("SELECT id FROM admins WHERE login=?",array($login));
     }

     function AdminAdd($adminadd) // dodaje admina. wymaga tablicy zawieraj±cej dane admina
     {
          $this->SetTS("admins");
          if($this->DB->Execute("INSERT INTO admins (login, name, email, passwd, rights) VALUES (?, ?, ?, ?, ?)",array($adminadd['login'], $adminadd['name'], $adminadd['email'], crypt($adminadd['password']),$adminadd['rights'])))
               return $this->DB->GetOne("SELECT id FROM admins WHERE login=?",array($adminadd['login']));
          else
               return FALSE;
     }

     function AdminDelete($id) // usuwa admina o podanym id
     {
          return $this->DB->Execute("DELETE FROM admins WHERE id=?",array($id));
     }

     function AdminExists($id) // zwraca TRUE/FALSE zale¿nie od tego czy admin istnieje czy nie
     {
          return ($this->DB->GetOne("SELECT * FROM admins WHERE id=?",array($id))?TRUE:FALSE);
     }


     function GetAdminInfo($id) // zwraca pe³ne info o podanym adminie
     {
          if($admininfo = $this->DB->GetRow("SELECT id, login, name, email, lastlogindate, lastloginip, failedlogindate, failedloginip FROM admins WHERE id=?",array($id)))
          {
               if($admininfo['lastlogindate'])
                    $admininfo['lastlogin'] = date("Y/m/d H:i",$admininfo['lastlogindate']);
               else
                    $admininfo['lastlogin'] = "-";

               if($admininfo['failedlogindate'])
                    $admininfo['faillogin'] = date("Y/m/d H:i",$admininfo['failedlogindate']);
               else
                    $admininfo['faillogin'] = "-";


               if(check_ip($admininfo['lastloginip']))
                    $admininfo['lastloginhost'] = gethostbyaddr($admininfo['lastloginip']);
               else
               {
                    $admininfo['lastloginhost'] = "-";
                    $admininfo['lastloginip'] = "-";
               }

               if(check_ip($admininfo['failedloginip']))
                    $admininfo['failloginhost'] = gethostbyaddr($admininfo['failedloginip']);
               else
               {
                    $admininfo['failloginhost'] = "-";
                    $admininfo['failloginip'] = "-";
               }
          }
          return $admininfo;
     }

     function AdminUpdate($admininfo) // uaktualnia rekord admina.
     {
          $this->SetTS("admins");
          return $this->DB->Execute("UPDATE admins SET login=?, name=?, email=?, rights=? WHERE id=?",array($admininfo['login'],$admininfo['name'],$admininfo['email'],$admininfo['rights'],$admininfo['id']));
     }

     function GetAdminRights($id)
     {
          $mask = $this->DB->GetOne("SELECT rights FROM admins WHERE id=?",array($id));
          if($mask == "")
               $mask = "1";
          $len = strlen($mask);
          for($cnt=$len; $cnt > 0; $cnt --)
               $bin = sprintf("%04b",hexdec($mask[$cnt-1])).$bin;
          for($cnt=strlen($bin)-1; $cnt >= 0; $cnt --)
               if($bin[$cnt] == "1")
                    $result[] = strlen($bin) - $cnt -1;
          return $result;
     }

     /*
      *  Funkcje do obs³ugi rekordów u¿ytkowników
      */

     function GetUserName($id)
     {
          return $this->DB->GetOne("SELECT ".$this->DB->Concat("UPPER(lastname)","' '","name")." FROM users WHERE id=?",array($id));
     }

     function GetEmails($group)
     {
          return $this->DB->GetAll("SELECT email, ".$this->DB->Concat("lastname", "' '", "name")." AS username FROM users WHERE 1=1 ".($group !=0 ? " AND status='".$group."'" : "")." AND email != ''");
     }

     function GetUserEmail($id)
     {
          return $this->DB->GetOne("SELECT email FROM users WHERE id=?",array($id));
     }

     function UserExists($id)
     {
          $got = $this->DB->GetOne("SELECT deleted FROM users WHERE id=?",array($id));
          switch($this->DB->GetOne("SELECT deleted FROM users WHERE id=?",array($id)))
          {
               case '0':
                    return TRUE;
                    break;
               case '1':
                    return -1;
                    break;
               case '':
               default:
                    return FALSE;
                    break;
          }
     }

     function RecoverUser($id)
     {
          $this->SetTS('users');
          return $this->DB->Execute("UPDATE users SET deleted=0 WHERE id=?",array($id));
     }

     function GetUsersWithTariff($id)
     {
          return $this->DB->GetOne('SELECT COUNT(userid) FROM assignments, users WHERE users.id = userid AND deleted = 0 AND tariffid = ?',array($id));
     }

     function UserAdd($useradd)
     {
          $this->SetTS("users");

          if($this->DB->Execute("INSERT INTO users (name, lastname, phone1, phone2, phone3, gguin, address, zip, city, email, nip, status, creationdate, creatorid, info) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?NOW?, ?, ?)",array(ucwords($useradd['name']), strtoupper($useradd['lastname']), $useradd['phone1'], $useradd['phone2'], $useradd['phone3'], $useradd['gguin'], $useradd['address'], $useradd['zip'], $useradd['city'], $useradd['email'], $useradd['nip'], $useradd['status'], $this->SESSION->id, $useradd['info'])))
               return $this->DB->GetOne("SELECT MAX(id) FROM users");
          else
               return FALSE;
     }

     function DeleteUser($id)
     {
          $this->SetTS("users");
          $this->SetTS("nodes");
          $res1=$this->DB->Execute("DELETE FROM nodes WHERE ownerid=?",array($id));
          $res2=$this->DB->Execute("UPDATE users SET deleted=1 WHERE id=?",array($id));
          return $res1 || $res2;
     }

     function UserUpdate($userdata)
     {
          $this->SetTS("users");
          return $this->DB->Execute("UPDATE users SET status=?, phone1=?, phone2=?, phone3=?, address=?, zip=?, city=?, email=?, gguin=?, nip=?, moddate=?NOW?, modid=?, info=?, lastname=?, name=?, deleted=0 WHERE id=?", array( $userdata['status'], $userdata['phone1'], $userdata['phone2'], $userdata['phone3'], $userdata['address'], $userdata['zip'], $userdata['city'], $userdata['email'], $userdata['gguin'], $userdata['nip'], $this->SESSION->id, $userdata['info'], strtoupper($userdata['lastname']), $userdata['name'], $userdata['id'] ) );
     }

     function GetUserNodesNo($id)
     {
          return $this->DB->GetOne("SELECT COUNT(*) FROM nodes WHERE ownerid=?",array($id));
     }

     function GetUserIDByIP($ipaddr)
     {
          return $this->DB->GetOne("SELECT ownerid FROM nodes WHERE ipaddr=?",array(ip_long($ipaddr)));
     }

     function GetCashByID($id)
     {
          return $this->DB->GetRow("SELECT time, adminid, type, value, userid, comment FROM `cash` WHERE id=?",array($id));
     }

     function GetUserStatus($id)
     {
          return $this->DB->GetOne("SELECT status FROM users WHERE id=?",array($id));
     }

     function GetUser($id)
     {
          if($result = $this->DB->GetRow("SELECT id, ".$this->DB->Concat("UPPER(lastname)","' '","name")." AS username, lastname, name, status, email, gguin, phone1, phone2, phone3, address, zip, nip, city, info, creationdate, moddate, creatorid, modid, deleted FROM users WHERE id=?",array($id)))
          {
               $result['createdby'] = $this->GetAdminName($result['creatorid']);
               $result['modifiedby'] = $this->GetAdminName($result['modid']);
               $result['creationdateh'] = date("Y-m-d, H:i",$result['creationdate']);
               $result['moddateh'] = date("Y-m-d, H:i",$result['moddate']);
               $result['balance'] = $this->GetUserBalance($result['id']);
               $result['tariffsvalue'] = $this->GetUserTariffsValue($result['id']);
               return $result;
          }else
               return FALSE;
     }

     function GetUserNames()
     {
          return $this->DB->GetAll("SELECT id, ".$this->DB->Concat("UPPER(lastname)","' '","name")." AS username FROM users WHERE status=3 AND deleted = 0 ORDER BY username");
     }

     function GetUserNodesAC($id)
     {
          if($acl = $this->DB->GetALL("SELECT access FROM nodes WHERE ownerid=?",array($id)))
          {
               foreach($acl as $value)
                    if($value['access'])
                         $y++;
                    else
                         $n++;

               if($y && !$n) return TRUE;
               if($n && !$y) return FALSE;
          }
          if($this->DB->GetOne("SELECT COUNT(*) FROM nodes WHERE ownerid=?",array($id)))
               return 2;
          else
               return FALSE;
     }

     function SearchUserList($order=NULL,$state=NULL,$search=NULL)
     {

          list($order,$direction)=explode(",",$order);

          ($direction != "desc") ? $direction = "asc" : $direction = "desc";

          switch($order){

               case "phone":
                    $sqlord = "ORDER BY deleted ASC, phone1";
               break;

               case "id":
                    $sqlord = "ORDER BY deleted ASC, id";
               break;

               case "address":
                    $sqlord = "ORDER BY deleted ASC, address";
               break;

               case "email":
                    $sqlord = "ORDER BY deleted ASC, email";
               break;

               case "balance":
                    $sqlord = "ORDER BY deleted ASC, balance";
               break;

               case "gg":
                    $sqlord = "ORDER BY deleted ASC, gguin";
               break;

               case "nip":
                    $sqlord = "ORDER BY deleted ASC, nip";
               break;

               default:
                    $sqlord = "ORDER BY deleted ASC, ".$this->DB->Concat("UPPER(lastname)","' '","name");
               break;
          }

          if(sizeof($search))
               foreach($search as $key => $value)
               {
                    $value = str_replace(" ","%",trim($value));
                    if($value!="")
                    {
                         $value = "'%".$value."%'";
                         if($key=="phone")
                              $searchargs[] = "(phone1 ?LIKE? $value OR phone2 ?LIKE? $value OR phone3 ?LIKE? $value)";
                         elseif($key=="username")
                              $searchargs[] = $this->DB->Concat("UPPER(lastname)","' '","name")." ?LIKE? ".$value;
                         elseif($key!="s")
                              $searchargs[] = $key." ?LIKE? ".$value;
                    }
               }

          if($searchargs)
               $sqlsarg = implode(" AND ",$searchargs);

          if(!isset($state))
               $state = 3;

          if($userlist = $this->DB->GetAll("SELECT users.id AS id, ".$this->DB->Concat("UPPER(lastname)","' '","users.name")." AS username, deleted, status, email, phone1, address, gguin, nip, zip, city, info, SUM((type * -2 + 7) * cash.value) AS balance FROM users LEFT JOIN cash ON users.id = cash.userid AND (cash.type = 3 OR cash.type = 4) WHERE 1=1 ".($state !=0 ? " AND status = '".$state."'":"").($sqlsarg !="" ? " AND ".$sqlsarg :"")." GROUP BY users.id, deleted, lastname, users.name, status, email, phone1, phone2, phone3, address, gguin, nip, zip, city, info ".($sqlord !="" ? $sqlord." ".$direction:"")))
          {
               $tariffvalues = $this->DB->GetAllByKey("SELECT users.id AS id, SUM(value) AS value FROM users, assignments, tariffs WHERE users.id = assignments.userid AND tariffs.id = tariffid GROUP by users.id",'id');
               $access = $this->DB->GetAllByKey("SELECT ownerid AS id, SUM(access) AS acsum, COUNT(access) AS account FROM nodes GROUP BY ownerid",'id');

               foreach($userlist as $idx => $row)
               {
                    $userlist[$idx]['tariffvalue'] = $tariffvalues[$row['id']]['value'];
                    if($access[$row['id']]['account'] == $access[$row['id']]['acsum'])
                         $userlist[$idx]['nodeac'] = 1;
                    elseif($access[$row['id']]['acsum'] == 0)
                         $userlist[$idx]['nodeac'] = 0;
                    else
                         $userlist[$idx]['nodeac'] = 2;
                    if($userlist[$idx]['balance'] > 0)
                         $over += $userlist[$idx]['balance'];
                    elseif($userlist[$idx]['balance'] < 0)
                         $below += $userlist[$idx]['balance'];
               }                                                                                                                                                      
               $userlist['total']=sizeof($userlist);
               $userlist['state']=$state;
               $userlist['order']=$order;
               $userlist['below']=$below;
               $userlist['over']=$over;
               $userlist['direction']=$direction;
          }
          return $userlist;
     }

     function GetUserList($order="username,asc",$state=NULL)
     {

          list($order,$direction)=explode(",",$order);

          ($direction != "desc")  ? $direction = "asc" : $direction = "desc";

          switch($order){

               case "phone":
                    $sqlord = "ORDER BY phone1";
               break;

               case "id":
                    $sqlord = "ORDER BY id";
               break;

               case "address":
                    $sqlord = "ORDER BY address";
               break;

               case "email":
                    $sqlord = "ORDER BY email";
               break;

               case "balance":
                    $sqlord = "ORDER BY balance";
               break;

               case "gg":
                    $sqlord = "ORDER BY gguin";
               break;

               case "nip":
                    $sqlord = "ORDER BY nip";
               break;

               default:
                    $sqlord = "ORDER BY ".$this->DB->Concat("UPPER(lastname)","' '","name");
               break;
          }

          if(!isset($state))
               $state = 3;

          if($userlist = $this->DB->GetAll("SELECT users.id AS id, ".$this->DB->Concat("UPPER(lastname)","' '","users.name")." AS username, status, email, phone1, address, gguin, nip, zip, city, info, SUM((type * -2 + 7) * cash.value) AS balance FROM users LEFT JOIN cash ON users.id = cash.userid AND (cash.type = 3 OR cash.type = 4) WHERE deleted = 0 ".($state !=0 ? " AND status = '".$state."'":"")." GROUP BY users.id, lastname, users.name, status, email, phone1, phone2, phone3, address, gguin, nip, zip, city, info ".($sqlord !="" ? $sqlord." ".$direction:"")))
          {
               $tariffvalues = $this->DB->GetAllByKey("SELECT users.id AS id, SUM(value) AS value FROM users, assignments, tariffs WHERE users.id = assignments.userid AND tariffs.id = tariffid GROUP by users.id",'id');

               $access = $this->DB->GetAllByKey("SELECT ownerid AS id, SUM(access) AS acsum, COUNT(access) AS account FROM nodes GROUP BY ownerid",'id');
               foreach($userlist as $idx => $row)
               {
                    $userlist[$idx]['tariffvalue'] = $tariffvalues[$row['id']]['value'];
                    if($access[$row['id']]['account'] == $access[$row['id']]['acsum'])
                         $userlist[$idx]['nodeac'] = 1;
                    elseif($access[$row['id']]['acsum'] == 0)
                         $userlist[$idx]['nodeac'] = 0;
                    else
                         $userlist[$idx]['nodeac'] = 2;
                    if($userlist[$idx]['balance'] > 0)
                         $over += $userlist[$idx]['balance'];
                    elseif($userlist[$idx]['balance'] < 0)
                         $below += $userlist[$idx]['balance'];
               }
          }

          $userlist['total']=sizeof($userlist);
          $userlist['state']=$state;
          $userlist['order']=$order;
          $userlist['below']=$below;
          $userlist['over']=$over;
          $userlist['direction']=$direction;

          return $userlist;
     }

     function GetUserNodes($id)
     {
          if($result = $this->DB->GetAll("SELECT id, name, mac, ipaddr, access FROM nodes WHERE ownerid=? ORDER BY name ASC",array($id))){
               foreach($result as $idx => $row)
                    $result[$idx]['ip'] = long2ip($row['ipaddr']);
               $result['total'] = sizeof($result);
               $result['ownerid'] = $id;
          }
          return $result;
     }

     function GetUserBalance($id)
     {
          $bin = $this->DB->GetOne("SELECT SUM(value) FROM cash WHERE userid=? AND type='3'",array($id));
          $bou = $this->DB->GetOne("SELECT SUM(value) FROM cash WHERE userid=? AND type='4'",array($id));
          return round($bin-$bou,2);
     }

     function GetUserBalanceList($id)
     {

          // wrapper do starego formatu

          if($talist = $this->DB->GetAll("SELECT id, name FROM admins"))
               foreach($talist as $idx => $row)
                    $adminslist[$row['id']] = $row['name'];

          // wrapper do starego formatu

          if($tslist = $this->DB->GetAll("SELECT id, time, adminid, type, value, userid, comment FROM cash WHERE userid=? ORDER BY time",array($id)))
               foreach($tslist as $row)
                    foreach($row as $column => $value)
                         $saldolist[$column][] = $value;


          if(sizeof($saldolist['id']) > 0){
               foreach($saldolist['id'] as $i => $v)
               {
                    ($i>0) ? $saldolist['before'][$i] = $saldolist['after'][$i-1] : $saldolist['before'][$i] = 0;

                    $saldolist['adminname'][$i] = $adminslist[$saldolist['adminid'][$i]];
                    $saldolist['value'][$i] = round($saldolist['value'][$i],3);

                    (strlen($saldolist['comment'][$i])<3) ? $saldolist['comment'][$i] = $saldolist['name'][$i] : $saldolist['comment'][$i] =  $saldolist['comment'][$i];

                    switch ($saldolist['type'][$i]){

                         case "3":
                              $saldolist['after'][$i] = round(($saldolist['before'][$i] + $saldolist['value'][$i]),4);
                              $saldolist['name'][$i] = "Wp³ata";
//                              $saldolist['comment'][$i] = "Abonament za".date("Y/m",$saldolist['time'][$i]) || $saldolist['comment'][$i];
                         break;

                         case "4":
                              $saldolist['after'][$i] = round(($saldolist['before'][$i] - $saldolist['value'][$i]),4);
                              $saldolist['name'][$i] = "Obci±¿enie";
                         break;

                    }

                    $saldolist['date'][$i]=date("Y/m/d H:i",$saldolist['time'][$i]);
                    // nie chce mi sie czytac, ale czy to nie jest pare linii wy¿ej ?
                    (strlen($saldolist['comment'][$i])<3) ? $saldolist['comment'][$i] = $saldolist['name'][$i] : $saldolist['comment'][$i] =  $saldolist['comment'][$i];
               }

               $saldolist['balance'] = $saldolist['after'][sizeof($saldolist['id'])-1];
               $saldolist['total'] = sizeof($saldolist['id']);

          }else{
               $saldolist['balance'] = 0;
          }

          if($saldolist['total'])
          {
               foreach($saldolist['value'] as $key => $value)
                    $saldolist['value'][$key] = $value;
               foreach($saldolist['after'] as $key => $value)
                    $saldolist['after'][$key] = $value;
               foreach($saldolist['before'] as $key => $value)
                    $saldolist['before'][$key] = $value;
          }

          $saldolist['userid'] = $id;
          return $saldolist;

     }

     function UserStats()
     {
          $result['total'] = $this->DB->GetOne("SELECT COUNT(id) FROM users");
          $result['connected'] = $this->DB->GetOne("SELECT COUNT(id) FROM users WHERE status=3");
          $result['awaiting'] = $this->DB->GetOne("SELECT COUNT(id) FROM users WHERE status=2");
          $result['interested'] = $this->DB->GetOne("SELECT COUNT(id) FROM users WHERE status=1");
          $result['debt'] = 0;
          $result['debtvalue'] = 0;
          if($users = $this->DB->GetAll("SELECT id FROM users"))
               foreach($users as $idx => $row)
               {
                    $row['balance'] = $this->GetUserBalance($row['id']);
                    if($row['balance'] < 0)
                    {
                         $result['debt'] ++;
                         $result['debtvalue'] -= $row['balance'];
                    }
               }
          return $result;
     }

     /*
      *  Funkcje do obs³ugi rekordów z komputerami
      */

     function GetNodeOwner($id)
     {
          return $this->DB->GetOne("SELECT ownerid FROM nodes WHERE id=?",array($id));
     }

     function NodeUpdate($nodedata)
     {
          $this->SetTS("nodes");
          return $this->DB->Execute("UPDATE nodes SET name=?, ipaddr=?, mac=?, moddate=?NOW?, modid=?, access=?, ownerid=? WHERE id=?",array(strtoupper($nodedata['name']), ip_long($nodedata['ipaddr']), strtoupper($nodedata['mac']), $this->SESSION->id, $nodedata['access'], $nodedata['ownerid'], $nodedata['id']));
     }

     function DeleteNode($id)
     {
          return $this->DB->Execute("DELETE FROM nodes WHERE id=?",array($id));
     }

     function GetNodeNameByMAC($mac)
     {
          return $this->DB->GetOne("SELECT name FROM nodes WHERE mac=?",array($mac));
     }

     function GetNodeIDByIP($ipaddr)
     {
          return $this->DB->GetOne("SELECT id FROM nodes WHERE ipaddr=?",array(ip_long($ipaddr)));
     }

     function GetNodeIDByMAC($mac)
     {
          return $this->DB->GetOne("SELECT id FROM nodes WHERE mac=?",array($mac));
     }

     function GetNodeIDByName($name)
     {
          return $this->DB->GetOne("SELECT id FROM nodes WHERE name=?",array($name));
     }

     function GetNodeIPByID($id)
     {
          return long2ip($this->DB->GetOne("SELECT ipaddr FROM nodes WHERE id=?",array($id)));
     }

     function GetNodeMACByID($id)
     {
          return $this->DB->GetOne("SELECT mac FROM nodes WHERE id=?",array($id));
     }

     function GetNodeName($id)
     {
          return $this->DB->GetOne("SELECT name FROM nodes WHERE id=?",array($id));
     }

     function GetNodeNameByIP($ipaddr)
     {
          return $this->DB->GetOne("SELECT name FROM nodes WHERE ipaddr=?",array(ip_long($ipaddr)));

     }

     function GetNode($id)
     {
          if($result = $this->DB->GetRow("SELECT id, name, ownerid, ipaddr, mac, access, creationdate, moddate, creatorid, modid, netdev FROM nodes WHERE id=?",array($id)))
          {
               $result['ip'] = long2ip($result['ipaddr']);
               $result['createdby'] = $this->GetAdminName($result['creatorid']);
               $result['modifiedby'] = $this->GetAdminName($result['modid']);
               $result['creationdateh'] = date("Y-m-d, H:i",$result['creationdate']);
               $result['moddateh'] = date("Y-m-d, H:i",$result['moddate']);
               $result['owner'] = $this->GetUsername($result['ownerid']);
               $result['netid'] = $this->GetNetIDByIP($result['ip']);
               $result['netname'] = $this->GetNetworkName($result['netid']);
               $result['producer'] = get_producer($result['mac']);
               $result['devicename'] = $this->GetNetDevName($result['netdevid']);
               return $result;
          }else
               return FALSE;
     }

     function GetNodeList($order="name,asc")
     {

          if($order=="")
               $order="name,asc";

          list($order,$direction) = explode(",",$order);

          ($direction=="desc") ? $direction = "desc" : $direction = "asc";

          switch($order)
          {
               case "name":
                    $sqlord = " ORDER BY name";
               break;

               case "id":
                    $sqlord = " ORDER BY id";
               break;

               case "mac":
                    $sqlord = " ORDER BY mac";
               break;

               case "ip":
                    $sqlord = " ORDER BY ipaddr";
               break;
          }

          if($username = $this->DB->GetAll("SELECT id, ".$this->DB->Concat("UPPER(lastname)","' '","name")." AS username FROM users"))
               foreach($username as $idx => $row)
                    $usernames[$row['id']] = $row['username'];

          if($nodelist = $this->DB->GetAll("SELECT id, ipaddr, mac, name, ownerid, access FROM nodes ".($sqlord != "" ? $sqlord." ".$direction : "")))
          {
               foreach($nodelist as $idx => $row)
               {
                    $nodelist[$idx]['ip'] = long2ip($row['ipaddr']);
                    $nodelist[$idx]['owner'] = $usernames[$row['ownerid']];
                    ($row['access']) ? $totalon++ : $totaloff++;
               }
          }

          switch($order)
          {
               case "owner":
                    foreach($nodelist as $idx => $row)
                    {
                         $ownertable['idx'][] = $idx;
                         $ownertable['owner'][] = $row['owner'];
                    }
                    if(is_array($ownertable))
                    {
                         array_multisort($ownertable['owner'],($direction == "DESC" ? SORT_DESC : SORT_ASC),$ownertable['idx']);
                         foreach($ownertable['idx'] as $idx)
                              $nnodelist[] = $nodelist[$idx];
                    }
                    $nodelist = $nnodelist;
               break;
          }

          $nodelist['total'] = sizeof($nodelist);
          $nodelist['order'] = $order;
          $nodelist['direction'] = $direction;
          $nodelist['totalon'] = $totalon;
          $nodelist['totaloff'] = $totaloff;

          return $nodelist;
     }

     function SearchNodeList($args, $order="name,asc")
     {
          if($order=="")     
               $order="name,asc";

          list($order,$direction) = explode(",",$order);

          ($direction=="desc") ? $direction = "desc" : $direction = "asc";

          switch($order)
          {
               case "name":
                    $sqlord = " ORDER BY name";
               break;

               case "id":
                    $sqlord = " ORDER BY id";
               break;

               case "mac":
                    $sqlord = " ORDER BY mac";
               break;

               case "ip":
                    $sqlord = " ORDER BY ipaddr";
               break;
          }

          foreach($args as $idx => $value)
          {
               if($value!="")
               {
                    switch($idx)
                    {
                        case "ipaddr" : 
			    if (ip_long($value))
                            {
			           $searchargs[] = "ipaddr = ".ip_long($value);
                    	    } else 
			    {
				list($net,$broadcast) = get_ip_range_from_ip_part($value);
				if(check_ip($net) && check_ip($broadcast)) //na wszelki wypadek
				    $searchargs[] = "ipaddr > ".ip_long($net)." AND ipaddr < ".ip_long($broadcast);
			    }
			break;
                        default : 
                             $searchargs[] = $idx." ?LIKE? '%".$value."%'";
                        break;
                    }
               }
          }

          if($searchargs)
               $searchargs = " WHERE 1=1 AND ".implode(" AND ",$searchargs);

          if($username = $this->DB->GetAll("SELECT id, ".$this->DB->Concat("UPPER(lastname)","' '","name")." AS username FROM users"))
               foreach($username as $idx => $row)
                    $usernames[$row['id']] = $row['username'];

          if($nodelist = $this->DB->GetAll("SELECT id, ipaddr, mac, name, ownerid, access FROM nodes ".$searchargs." ".($sqlord != "" ? $sqlord." ".$direction : "")))
          {
               foreach($nodelist as $idx => $row)
               {
                    $nodelist[$idx]['ip'] = long2ip($row['ipaddr']);
                    $nodelist[$idx]['owner'] = $usernames[$row['ownerid']];
                    ($row['access']) ? $totalon++ : $totaloff++;
               }
          }

          switch($order)
          {
               case "owner":
                    foreach($nodelist as $idx => $row)
                    {
                         $ownertable['idx'][] = $idx;
                         $ownertable['owner'][] = $row['owner'];
                    }
                    array_multisort($ownertable['owner'],($direction == "DESC" ? SORT_DESC : SORT_ASC),$ownertable['idx']);
                    foreach($ownertable['idx'] as $idx)
                         $nnodelist[] = $nodelist[$idx];
                    $nodelist = $nnodelist;
               break;
          }

          $nodelist['total'] = sizeof($nodelist);
          $nodelist['order'] = $order;
          $nodelist['direction'] = $direction;
          $nodelist['totalon'] = $totalon;
          $nodelist['totaloff'] = $totaloff;

          return $nodelist;
     }

     function NodeSet($id)
     {
          $this->SetTS("nodes");
          if($this->DB->GetOne("SELECT access FROM nodes WHERE id=?",array($id)) == 1 )
               return $this->DB->Execute("UPDATE nodes SET access=0 WHERE id=?",array($id));
          else
               return $this->DB->Execute("UPDATE nodes SET access=1 WHERE id=?",array($id));
     }

     function NodeSetU($id,$access=FALSE)
     {
          $this->SetTS("nodes");
          if($access)
               return $this->DB->Execute("UPDATE nodes SET access=? WHERE ownerid=?",array(1,$id));
          else
               return $this->DB->Execute("UPDATE nodes SET access=? WHERE ownerid=?",array(0,$id));
     }

     function NodeAdd($nodedata)
     {
          $this->SetTS("nodes");
          if($this->DB->Execute("INSERT INTO nodes (name, mac, ipaddr, ownerid, creatorid, creationdate) VALUES (?, ?, ?, ?, ?, ?NOW?)",array(strtoupper($nodedata['name']),strtoupper($nodedata['mac']),ip_long($nodedata['ipaddr']),$nodedata['ownerid'],$this->SESSION->id)))
               return $this->DB->GetOne("SELECT MAX(id) FROM nodes");
          else
               return FALSE;
     }

     function NodeExists($id)
     {
          return ($this->DB->GetOne("SELECT * FROM nodes WHERE id=?",array($id))?TRUE:FALSE);
     }

     function NodeStats()
     {
          $result['connected'] = $this->DB->GetOne("SELECT COUNT(id) FROM nodes WHERE access=1");
          $result['disconnected'] = $this->DB->GetOne("SELECT COUNT(id) FROM nodes WHERE access=0");
          $result['total'] = $result['connected'] + $result['disconnected'];
          return $result;
     }

     function GetNetDevNode($id)
     {
          if($nodelist = $this->DB->GetAll("SELECT id, name, ownerid, ipaddr, netdev FROM nodes WHERE netdev=?",array($id)))
               foreach($nodelist as $idx => $row)
               {
                    $nodelist[$idx]['ip'] = long2ip($row['ipaddr']);
                    $nodelist[$idx]['owner'] = $this->GetUsername($row['ownerid']);
               }
          return $nodelist;
     }

     function NetDevLinkComputer($id,$netid)
     {
          $this->SetTS("nodes");
          return $this->DB->Execute("UPDATE nodes SET netdev=".$netid." WHERE id=".$id);
     }

     /*
      *  Obs³uga taryf
      */

     function GetUserTariffsValue($id)
     {
          return $this->DB->GetOne("SELECT sum(value) FROM assignments, tariffs WHERE tariffid = tariffs.id AND userid=?",array($id));
     }

     function GetUserAssignments($id)
     {
          if($assignments = $this->DB->GetAll("SELECT assignments.id AS id, tariffid, userid, period, at, value, uprate, downrate, name FROM assignments, tariffs WHERE userid=? AND tariffs.id = tariffid",array($id)))
          {
               foreach($assignments as $idx => $row)
               {
                    switch($row['period'])
                    {
                         case 0:
                              $row['period'] = 'co miesi±c';
                         break;

                         case 1:
                              $row['period'] = 'co tydzieñ';
                              $dni = array('poniedzia³ek', 'wtorek', '¶roda', 'czwartek', 'pi±tek', 'sobota', 'niedziela');
                              $row['at'] = $dni[$row['at'] - 1];
                         break;

                         case 2:
                              $row['period'] = 'co rok';
                              $miesiace = array('styczeñ','luty', 'marzec', 'kwiecieñ', 'maj', 'czerwiec', 'lipiec', 'sierpieñ', 'wrzesieñ', 'pa¼dziernik', 'listopad', 'grudzieñ');
                              $row['at'] --;
                              $ttime = $row['at'] * 86400 + mktime(12, 0, 0, 1, 1, 1990);
                              $row['at'] = date('j ',$ttime);
                              $row['at'] .= $miesiace[date('n',$ttime) - 1];
                         break;
                    }

                    $assignments[$idx] = $row;

               }
          }

          return $assignments;
     }

     function DeleteAssignment($id,$balance = FALSE)
     {
          return $this->DB->Execute('DELETE FROM assignments WHERE id=?',array($id));
     }

     function AddAssignment($assignmentdata)
     {
          $this->SetTS('assignments');
          return $this->DB->Execute("INSERT INTO assignments (tariffid, userid, period, at) VALUES (?, ?, ?, ?)",array($assignmentdata['tariffid'], $assignmentdata['userid'], $assignmentdata['period'], $assignmentdata['at']));
     }

     function GetTariffList()
     {
          if($tarifflist = $this->DB->GetAll("SELECT id, name, value, description, uprate, downrate FROM tariffs ORDER BY value DESC"))
          {
               $total = sizeof($tarifflist);
               foreach($tarifflist as $idx => $row)
               {
                    $tarifflist[$idx]['users'] = $this->GetUsersWithTariff($row['id']);
                    $tarifflist[$idx]['userscount'] = sizeof($this->DB->GetCol("SELECT userid FROM assignments, users WHERE users.id = userid AND deleted = 0 AND tariffid = ? GROUP BY userid",array($row['id'])));
                    echo mysql_error();
                    $tarifflist[$idx]['income'] = $tarifflist[$idx]['users'] * $row['value'];
                    $tarifflist['totalincome'] += $tarifflist[$idx]['income'];
                    $tarifflist['totalusers'] += $tarifflist[$idx]['users'];
                    $tarifflist['totalcount'] += $tarifflist[$idx]['userscount'];
               }
          }
          $tarifflist['total'] = $total;

          return $tarifflist;

     }

     function TariffMove($from, $to)
     {
          $this->SetTS('assignments');
          $ids = $this->DB->GetCol("SELECT assignments.id AS id FROM assignments, users WHERE userid = users.id AND deleted = 0 AND tariffid = ?",array($from));
          foreach($ids as $id)
               $this->DB->Execute("UPDATE assignments SET tariffid=? WHERE id=? AND tariffid=?",array($to, $id, $from));
     }

     function GetTariffIDByName($name)
     {
          return $this->DB->GetOne("SELECT id FROM tariffs WHERE name=?",array($name));
     }

     function TariffAdd($tariffdata)
     {
          $this->SetTS("tariffs");
          if($this->DB->Execute("INSERT INTO tariffs (name, description, value, uprate, downrate)
               VALUES (?, ?, ?, ?, ?)",
               array(
                    $tariffdata['name'],
                    $tariffdata['description'],
                    $tariffdata['value'],
                    $tariffdata['uprate'],
                    $tariffdata['downrate']
               )
          ))
               return $this->DB->GetOne("SELECT id FROM tariffs WHERE name=?",array($tariffdata['name']));
          else
               return FALSE;
     }

     function TariffUpdate($tariff)
     {
          $this->SetTS("tariffs");
          return $this->DB->Execute("UPDATE tariffs SET name=?, description=?, value=?, uprate=?, downrate=? WHERE id=?",array($tariff['name'], $tariff['description'], $tariff['value'], $tariff['uprate'], $tariff['downrate'], $tariff['id']));
     }

     function TariffDelete($id)
     {
           if (!$this->GetUsersWithTariff($id))
           return $this->DB->Execute("DELETE FROM tariffs WHERE id=?",array($id));
           else
           return FALSE;
     }

     function GetTariffValue($id)
     {
          return $this->DB->GetOne("SELECT value FROM tariffs WHERE id=?",array($id));
     }

     function GetTariffName($id)
     {
          return $this->DB->GetOne("SELECT name FROM tariffs WHERE id=?",array($id));
     }

     function GetTariff($id)
     {
          $result = $this->DB->GetRow("SELECT id, name, value, description, uprate, downrate FROM tariffs WHERE id=?",array($id));
          $result['users'] = $this->DB->GetAll("SELECT users.id AS id, COUNT(users.id) AS cnt, ".$this->DB->Concat('upper(lastname)',"' '",'name')." AS username FROM assignments, users WHERE users.id = userid AND deleted = 0 AND tariffid = ? GROUP BY users.id, username",array($id));
          $result['userscount'] = sizeof($result['users']);
          $result['count'] = $this->GetUsersWithTariff($id);
          $result['totalval'] = $result['value'] * $result['count'];
	  $result['rows'] = ceil(sizeof($result['users'])/2);
          return $result;
     }

     function GetTariffs()
     {
          return $this->DB->GetAll("SELECT id, name, value, uprate, downrate FROM tariffs ORDER BY value DESC");
     }

     function TariffExists($id)
     {
          return ($this->DB->GetOne("SELECT * FROM tariffs WHERE id=?",array($id))?TRUE:FALSE);
     }

     function SetBalanceZero($user_id)
     {
          $this->SetTS("cash");
          $stan=$this->GetUserBalance($user_id);
          $stan=-$stan;
          return $this->DB->Execute("INSERT INTO cash (time, adminid, type, value, userid) VALUES (?NOW?, ?, ?, ?, ?)",array($this->SESSION->id, 3 , round("$stan",2) , $user_id));
     }
     function AddBalance($addbalance)
     {
          $this->SetTS("cash");
          return $this->DB->Execute("INSERT INTO cash (time, adminid, type, value, userid, comment) VALUES (?NOW?, ?, ?, ?, ?, ?)",array($this->SESSION->id, $addbalance['type'], round($addbalance['value'],2) , $addbalance['userid'], $addbalance['comment']));
     }
     function GetBalanceList()
     {
          $adminlist = $this->DB->GetAllByKey('SELECT id, name FROM admins','id');
          $userslist = $this->DB->GetAllByKey("SELECT id, ".$this->DB->Concat("UPPER(lastname)","' '","name")." AS username FROM users","id");
          if($balancelist = $this->DB->GetAll("SELECT id, time, adminid, type, value, userid, comment FROM cash ORDER BY time ASC"))
          {
               foreach($balancelist as $idx => $row)
               {
                    $balancelist[$idx]['admin'] = $adminlist[$row['adminid']]['name'];
                    $balancelist[$idx]['value'] = $row['value'];
                    $balancelist[$idx]['username'] = $userslist[$row['userid']]['username'];
                    if($idx)
                         $balancelist[$idx]['before'] = $balancelist[$idx-1]['after'];
                    else
                         $balancelist[$idx]['before'] = 0;

                    switch($row['type'])
                    {
                         case "1":
                              $balancelist[$idx]['type'] = "przychód";
                              $balancelist[$idx]['after'] = $balancelist[$idx]['before'] + $balancelist[$idx]['value'];
                              $balancelist['income'] = $balancelist['income'] + $balancelist[$idx]['value'];
                         break;

                         case "2":
                              $balancelist[$idx]['type'] = "rozchód";
                              $balancelist[$idx]['after'] = $balancelist[$idx]['before'] - $balancelist[$idx]['value'];
                              $balancelist['expense'] = $balancelist['expense'] + $balancelist[$idx]['value'];
                         break;

                         case "3":
                              $balancelist[$idx]['type'] = "wp³ata u¿";
                              $balancelist[$idx]['after'] = $balancelist[$idx]['before'] + $balancelist[$idx]['value'];
                              $balancelist['incomeu'] = $balancelist['incomeu'] + $balancelist[$idx]['value'];
                         break;
                         case "4":
                              $balancelist[$idx]['type'] = "obci±¿enie u¿";
                              $balancelist[$idx]['after'] = $balancelist[$idx]['before'];
                              $balancelist['uinvoice'] = $balancelist['uinvoice'] + $balancelist[$idx]['value'];
                         break;
                         default:
                              $balancelist[$idx]['type'] = '<FONT COLOR="RED">???</FONT>';
                              $balancelist[$idx]['after'] = $balancelist[$idx]['before'];
                         break;
                    }

               }

               $balancelist['total'] = $balancelist[$idx]['after'];

          }

          return $balancelist;
     }

     function ScanNodes()
     {
          $networks = $this->GetNetworks();
          if($networks)
               foreach($networks as $idx => $network)
               {
                    $out = split("\n",execute_program("nbtscan","-q -s: ".$network['address']."/".$network['prefix']));
                    foreach($out as $line)
                    {
                         list($ipaddr,$name,$null,$login,$mac)=split(":",$line);
                         $row['ipaddr'] = trim($ipaddr);
                         $row['name'] = trim($name);
                         $row['mac'] = str_replace("-",":",trim($mac));
                         if(!$this->GetNodeIDByIP($row['ipaddr']) && $row['ipaddr'] && $row['mac'] != "00:00:00:00:00:00")
                              $result[] = $row;
                    }
               }
          return $result;
     }

     /*
      *  Obs³uga rekordów z sieciami
      */

     function NetworkExists($id)
     {
          return ($this->DB->GetOne("SELECT * FROM networks WHERE id=?",array($id)) ? TRUE : FALSE);
     }

     function IsIPFree($ip)
     {
          return !($this->DB->GetOne("SELECT * FROM nodes WHERE ipaddr=?",array(ip_long($ip))) ? TRUE : FALSE);
     }

     function GetPrefixList()
     {
          for($i=30;$i>15;$i--)
          {
               $prefixlist['id'][] = $i;
               $prefixlist['value'][] = $i." (".pow(2,32-$i)." adresów)";
          }

          return $prefixlist;
     }

     function NetworkAdd($netadd)
     {
          if($netadd['prefix'] != "")
               $netadd['mask'] = prefix2mask($netadd['prefix']);
          $this->SetTS("networks");
          if($this->DB->Execute("INSERT INTO networks (name, address, mask, interface, gateway, dns, dns2, domain, wins, dhcpstart, dhcpend) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array(strtoupper($netadd['name']),$netadd['address'],$netadd['mask'],strtolower($netadd['interface']),$netadd['gateway'],$netadd['dns'],$netadd['dns2'],$netadd['domain'],$netadd['wins'],$netadd['dhcpstart'],$netadd['dhcpend'])))
               return $this->DB->GetOne("SELECT id FROM networks WHERE address=?",array($netadd['address']));
          else
               return FALSE;
     }

     function NetworkDelete($id)
     {
          $this->SetTS("networks");
          return $this->DB->Execute("DELETE FROM networks WHERE id=?",array($id));
     }

     function GetNetworkName($id)
     {
          return $this->DB->GetOne("SELECT name FROM networks WHERE id=?",array($id));
     }


     function GetNetIDByIP($ipaddr)
     {
          if($networks = $this->DB->GetAll("SELECT id, address, mask FROM networks"))
               foreach($networks as $idx => $row)
                    if(isipin($ipaddr,$row['address'],$row['mask']))
                         return $row['id'];
          return FALSE;
     }

     function GetNetworks()
     {
          if($netlist = $this->DB->GetAll("SELECT id, name, address, mask FROM networks"))
               foreach($netlist as $idx => $row)
               {
                    $netlist[$idx]['addresslong'] = ip_long($row['address']);
                    $netlist[$idx]['prefix'] = mask2prefix($row['mask']);
               }

          return $netlist;
     }

     function GetNetworkParams($id)
     {
          if($params = $this->DB->GetRow("SELECT * FROM networks WHERE id=?",array($id)))
          {
               $params['broadcast'] = ip_long(getbraddr($params['address'],$params['mask']));
               $params['address'] = ip_long($params['address']);
          }
          return $params;
     }

     function GetNetworkList()
     {

          if($networks = $this->DB->GetAll("SELECT id, name, address, mask, interface, gateway, dns, dns2, domain, wins, dhcpstart, dhcpend FROM networks"))
               foreach($networks as $idx => $row)
               {
                    $row['prefix'] = mask2prefix($row['mask']);
                    $row['size'] = pow(2,(32 - $row['prefix']));
                    $row['addresslong'] = ip_long($row['address']);
                    $row['broadcast'] = getbraddr($row['address'],$row['mask']);
                    $row['broadcastlong'] = ip_long($row['broadcast']);
                    $row['assigned'] = $this->DB->GetOne("SELECT COUNT(*) FROM nodes WHERE ipaddr >= ? AND ipaddr <= ?",array($row['addresslong'], $row['broadcastlong']));
                    $networks[$idx] = $row;
                    $networks['size'] += $row['size'];
                    $networks['assigned'] += $row['assigned'];
               }

          return $networks;
     }

     function IsIPValid($ip,$checkbroadcast=FALSE,$ignoreid=0)
     {
          $networks = $this->GetNetworks();
          if($networks = $this->GetNetworks())
          {
               foreach($networks as $idx => $row)
               {
                    if($row['id'] != $ignoreid)
                         if($checkbroadcast)
                         {
                              if((ip_long($ip) > $row['addresslong'] - 1)&&(ip_long($ip) < ip_long(getbraddr($row['address'],$row['mask'])) + 1))
                              {
                                   return TRUE;
                              }
                         }
                         else
                         {
                              if((ip_long($ip) > $row['addresslong'])&&(ip_long($ip) < ip_long(getbraddr($row['address'],$row['mask']))))
                              {
                                   return TRUE;
                              }
                         }
               }
          }

          return FALSE;
     }

     function NetworkOverlaps($network,$mask,$ignorenet=0)
     {
          $networks = $this->GetNetworks();
          $cnetaddr = ip_long($network);
          $cbroadcast = ip_long(getbraddr($network,$mask));

          if($networks = $this->GetNetworks())
               foreach($networks as $idx => $row)
               {
                    $broadcast = ip_long(getbraddr($row['address'],$row['mask']));
                    $netaddr = $row['addresslong'];
                    if($row['id'] != $ignorenet)
                    {
                         if(
                                   ($cbroadcast == $broadcast)
                                   ||
                                   ($cnetaddr == $netaddr)
                                   ||
                                   (
                                    ($cnetaddr < $netaddr)
                                    &&
                                    ($cbroadcast > $broadcast)
                                    )
                                   ||
                                   (
                                    ($cnetaddr > $netaddr)
                                    &&
                                    ($cbroadcast < $broadcast)
                                    )
                                   )
                              return TRUE;

                    }
               }
          return FALSE;
     }

     function NetworkShift($network="0.0.0.0",$mask="0.0.0.0",$shift=0)
     {
          $this->SetTS("nodes");
          $this->SetTS("networks");
          return $this->DB->Execute("UPDATE nodes SET ipaddr = ipaddr + ? WHERE ipaddr >= ? AND ipaddr <= ?",array($shift,ip_long($network), ip_long(getbraddr($network,$mask))));
     }

     function NetworkUpdate($networkdata)
     {
          $this->SetTS("networks");
          return $this->DB->Execute("UPDATE networks SET name=?, address=?, mask=?, interface=?, gateway=?, dns=?, dns2=?, domain=?, wins=?, dhcpstart=?, dhcpend=? WHERE id=?",array(strtoupper($networkdata['name']),$networkdata['address'],$networkdata['mask'],strtolower($networkdata['interface']),$networkdata['gateway'],$networkdata['dns'],$networkdata['dns2'],$networkdata['domain'],$networkdata['wins'],$networkdata['dhcpstart'],$networkdata['dhcpend'],$networkdata['id']));
     }


     function NetworkCompress($id,$shift=0)
     {
          $this->SetTS("nodes");
          $this->SetTS("networks");
          $network=$this->GetNetworkRecord($id);
          $address = $network['addresslong']+$shift;
          foreach($network['nodes']['id'] as $key => $value)
          {
               if($value)
               {
                    $address ++;
                    $this->DB->Execute("UPDATE nodes SET ipaddr=? WHERE id=?",array($address,$value));
               }
          }
     }

     function NetworkRemap($src,$dst)
     {
          $this->SetTS("nodes");
          $this->SetTS("networks");
          $network['source'] = $this->GetNetworkRecord($src);
          $network['dest'] = $this->GetNetworkRecord($dst);
          foreach($network['source']['nodes']['id'] as $key => $value)
               if($this->NodeExists($value))
                    $nodelist[] = $value;
          $counter = 1;
          if(sizeof($nodelist))
               foreach($nodelist as $value)
               {
                    while($this->NodeExists($network['dest']['nodes']['id'][$counter]))
                         $counter++;
                    $this->DB->Execute("UPDATE nodes SET ipaddr=? WHERE id=?",array($network['dest']['nodes']['addresslong'][$counter],$value));
                    $counter++;
               }
          return $counter;
     }

     function GetNetworkRecord($id,$page = 0, $plimit = 4294967296)
     {
          $network = $this->DB->GetRow("SELECT id, name, address, mask, interface, gateway, dns, dns2, domain, wins, dhcpstart, dhcpend FROM networks WHERE id=?",array($id));
          $network['prefix'] = mask2prefix($network['mask']);
          $network['addresslong'] = ip_long($network['address']);
          $network['size'] = pow(2,32-$network['prefix']);
          $network['assigned'] = 0;
          $network['broadcast'] = getbraddr($network['address'],$network['mask']);
          $network['pagemax'] = ceil($network['size'] / $plimit);

          if($page > $network['pagemax'])
               $page = $network['pagemax'];
          if($page < 1)
               $page = 1;

          $page --;
          $start = $page * $plimit;
          $end = ($network['size'] > $plimit ? $start + $plimit : $network['size']);

          $nodes = $this->DB->GetAllByKey("SELECT id, name, ipaddr, ownerid FROM nodes WHERE ipaddr >= ? AND ipaddr <= ?",'ipaddr',array(($network['addresslong'] + $start), ($network['addresslong'] + $end)));

          for($i = 0; $i < ($end - $start) ; $i ++)
          {

               $longip = $network['addresslong'] + $i + $start;
               $node = $nodes["".$longip.""];
               $network['nodes']['addresslong'][$i] = $longip;
               $network['nodes']['address'][$i] = long2ip($longip);;
               $network['nodes']['id'][$i] = $node['id'];

               if( $network['nodes']['addresslong'][$i] == $network['addresslong'] || $network['nodes']['addresslong'][$i] == $network['addresslong'] + $network['size'] - 1)
                    $network['nodes']['name'][$i] = 'BROADCAST';
               elseif($network['nodes']['addresslong'][$i] >= ip_long($network['dhcpstart']) && $network['nodes']['addresslong'][$i] <= ip_long($network['dhcpend']))
                    $network['nodes']['name'][$i] = 'DHCP';
               else
                    $network['nodes']['name'][$i] = $node['name'];
               $network['nodes']['ownerid'][$i] = $node['ownerid'];
               if($node['id'])
                    $network['pageassigned'] ++;
          }

          $network['assigned'] = $this->DB->GetOne("SELECT COUNT(*) FROM nodes WHERE ipaddr >= ? AND ipaddr < ?",array($network['addresslong'], $network['addresslong'] + $network['size']));

          $network['rows'] = ceil(sizeof($network['nodes']['address']) / 4);
          $network['free'] = $network['size'] - $network['assigned'] - 2;
          $network['pages'] = ceil($network['size'] / $plimit);
          $network['page'] = $page + 1;

          return $network;
     }

     function GetNetwork($id)
     {
          if($row = $this->DB->GetRow("SELECT address, mask, name FROM networks WHERE id=?",array($id)))
               foreach($row as $field => $value)
                    $$field = $value;

          for($i=ip_long($address)+1;$i<ip_long(getbraddr($address,$mask));$i++)
          {
               $result['addresslong'][] = $i;
               $result['address'][] = long2ip($i);
               $result['nodeid'][] = 0;
               $result['nodename'][] = "";
               $result['ownerid'][] = 0;
          }

          if(sizeof($result['address']))
               if($nodes = $this->DB->GetAll("SELECT name, id, ownerid, ipaddr FROM nodes WHERE ipaddr >= ? AND ipaddr <= ?",array(ip_long($address), ip_long(getbraddr($address,$mask)))))
                    foreach($nodes as $node)
                    {
                         $pos = ($node['ipaddr'] - ip_long($address) - 1);
                         $result['nodeid'][$pos] = $node['nodeid'];
                         $result['nodename'][$pos] = $node['name'];
                         $result['ownerid'][$pos] = $node['ownerid'];
                    }
          return $result;
     }
     
     /*
      * Ewidencja sprzêtu sieciowego
      */
     
     function GetNetDevName($id)
     {
          return $this->DB->GetRow("SELECT name, model, location FROM netdevices WHERE id=?",array($id));
     }
     
     function CountNetDevLinks($id)
     {
          return array_merge(
               $this->DB->GetOne("SELECT COUNT(id) FROM netlinks WHERE src = ? OR dst = ?",array($id,$id)),
               $this->DB->GetOne("SELECT COUNT(Id) FROM nodes WHERE netdev = ?",array($id))
               );
     }
     
     function GetNetDevConnected($id)
     {
          return $this->DB->GetAll("SELECT (CASE src WHEN ".$id." THEN src ELSE dst END) AS src, (CASE src WHEN ".$id." THEN dst ELSE src END) AS dst FROM netlinks WHERE src = ".$id." OR dst = ".$id);
     }
     
     function GetNetDevConnectedNames($id)
     {
          // To powinno byæ lepiej zrobione...
          $list =  $this -> GetNetDevConnected($id);
          $id=0;
          if ($list) {
              foreach($list as $row) {
               $names[$id]= $this -> GetNetDev($row[dst]);
               $id++;
              }
          }
          return $names;
     }
     
     function GetNetDevList($order="name,asc")
     {

          if($order=="")
               $order="name,asc";

          list($order,$direction) = explode(",",$order);

          ($direction=="desc") ? $direction = "desc" : $direction = "asc";

          switch($order)
          {
               case "name":
                    $sqlord = " ORDER BY name";
               break;

               case "id":
                    $sqlord = " ORDER BY id";
               break;

               case "producer":
                    $sqlord = " ORDER BY producer";
               break;

               case "model":
                    $sqlord = " ORDER BY model";
               break;

               case "ports":
                    $sqlord = " ORDER BY ports";
               break;

               case "serialnumber":
                    $sqlord = " ORDER BY serialnumber";
               break;

               case "location":
                    $sqlord = " ORDER BY location";
               break;
          }

          if($netdevlist = $this->DB->GetAll("SELECT id, name, location, description, producer, model, serialnumber, ports FROM netdevices ".($sqlord != "" ? $sqlord." ".$direction : "")))
               foreach($netdevlist as $idx => $row)
                    $netdevlist[$idx]['takenports'] = $this -> CountNetDevLinks($row['id']);

          $netdevlist['total'] = sizeof($netdevlist);
          $netdevlist['order'] = $order;
          $netdevlist['direction'] = $direction;
          return $netdevlist;
     }
      
     function GetNetDev($id)
     {
          $result = $this->DB->GetRow("SELECT name, location, description, producer, model, serialnumber, ports FROM netdevices WHERE id=?",array($id));
          $result['takenports'] = $this->CountNetDevLinks($id);
          $result['id'] = $id;
          return $result;
     }

     function DeleteNetDev($id)
     {
          return $this->DB->Execute("DELETE FROM netdevices WHERE id=?",array($id));
     }

     function NetDevAdd($netdevdata)
     {
          $this->SetTS("netdevices");
          if($this->DB->Execute("INSERT INTO netdevices (name, location, description, producer, model, serialnumber, ports) VALUES (?, ?, ?, ?, ?, ?, ?)",array($netdevdata['name'],$netdevdata['location'],$netdevdata['description'],$netdevdata['producer'],$netdevdata['model'],$netdevdata['serialnumber'],$netdevdata['ports'])))
               return $this->DB->GetOne("SELECT MAX(id) FROM netdevices");
          else
               return FALSE;
     }

     function NetDevUpdate($netdevdata)
     {
          $this->DB->Execute("UPDATE netdevices SET name=?, location=?, description=?, producer=?, model=?, serialnumber=?, ports=? WHERE id=?", array( $netdevdata['name'], $netdevdata['location'], $netdevdata['description'], $netdevdata['producer'], $netdevdata['model'], $netdevdata['serialnumber'], $netdevdata['ports'], $netdevdata['id'] ) );
     }

     /*
      * Pozosta³e funkcje...
      */

     function GetRemoteMACs($host = "127.0.0.1", $port = 1029)
     {
          if($socket = socket_create (AF_INET, SOCK_STREAM, 0))
               if(@socket_connect ($socket, $host, $port))
               {
                    while ($input = socket_read ($socket, 2048))
                         $inputbuf .= $input;
                    socket_close ($socket);                    
               }
          foreach(split("\n",$inputbuf) as $line)
          {
               list($ip,$hwaddr) = split(' ',$line);
               if(check_mac($hwaddr))
               {
                    $result['mac'][] = $hwaddr;
                    $result['ip'][] = $ip;
                    $result['longip'][] = ip_long($ip);
                    $result['nodename'][] = $this->GetNodeNameByMAC($mac);
               }
          }
          return $result;
     }

     function GetMACs()
     {
          switch(PHP_OS)
          {
               case "Linux":
                    if(@is_readable("/proc/net/arp"))
                         $file=fopen("/proc/net/arp","r");
                    else
                         return FALSE;
                    while(!feof($file))
                    {
                         $line=fgets($file,4096);
                         $line=eregi_replace("[\t ]+"," ",$line);
                         list($ip, $hwtype, $flags, $hwaddr, $mask, $device) = split(' ',$line);
                         if($flags == "0x2")
                         {
                              $result['mac'][] = $hwaddr;
                              $result['ip'][] = $ip;
                              $result['longip'][] = ip_long($ip);
                              $result['nodename'][] = $this->GetNodeNameByMAC($mac);
                         }
                    }
                    break;

               default:
                    exec("arp -an|grep -v incompl",$result);
                    foreach($result as $arpline)
                    {
                         list($fqdn,$ip,$at,$mac,$hwtype,$perm) = explode(" ",$arpline);
                         $ip = str_replace("(","",str_replace(")","",$ip));
                         if($perm != "PERM")
                         {
                              $result['mac'][] = $mac;
                              $result['ip'][] = $ip;
                              $result['longip'][] = ip_long($ip);
                              $result['nodename'][] = $this->GetNodeNameByMAC($mac);
                         }
                    }
                    break;

          }
          array_multisort($result['longip'],$result['mac'],$result['ip'],$result['nodename']);
          return $result;
     }

     function Mailing($mailing)
     {
          $SESSION=$this->SESSION;
          $emails = $this->GetEmails($mailing['group']);

          if($emails = $this->GetEmails($mailing['group']))
          {
               if($this->CONFIG['debug_email'])
                    echo "<B>Uwaga! Tryb debug (u¿ywam adresu ".$this->CONFIG['debug_email']."</B><BR>";

               foreach($emails as $key => $row)
               {
                    if($this->CONFIG['debug_email'])
                         $row['email'] = $this->CONFIG['debug_email'];

                    mail (
                         $row['username']." <".$row['email'].">",
                         $mailing['subject'],
                         $mailing['body'],
                         "From: ".$mailing['sender']." <".$mailing['from'].">\r\n"."Content-type: text/plain; charset=\"iso-8858-2\"\r\n"."X-Mailer: LMS-".$this->_version."/PHP-".phpversion()."\r\n"."X-Remote-IP: ".$_SERVER['REMOTE_ADDR']."\r\n"."X-HTTP-User-Agent: ".$_SERVER['HTTP_USER_AGENT']."\r\n"
                    );

                    echo "<img src=\"img/mail.gif\" border=\"0\" align=\"absmiddle\" alt=\"\"> ".($key+1)." z ".sizeof($emails)." (".sprintf("%02.2f",round((100/sizeof($emails))*($key+1),2))."%): ".$row['username']." &lt;".$row['email']."&gt;<BR>\n";
                    flush();

               }
          }
     }
     
     /*
      *     Statystyki
      */
     
     function Traffic($from = 0, $to = "?NOW?", $net = 0, $order = "", $limit = 0)
     {
         // period
         if (is_array($from))
          $fromdate = mktime($from[hour],$from[minute],$from[second],$from[month],$from[day],$from[year]);
         else $fromdate = $from;
         if (is_array($to))
          $todate = mktime($to[hour],$to[minute],$to[second],$to[month],$to[day],$to[year]);
         else $todate = $to;
         $dt = "( dt >= $fromdate AND dt <= $todate )";
         
         // nets
         if ($net != "allnets")
          {
              $params = $this->GetNetworkParams($net);
              $ipfrom = $params['address']+1;
              $ipto = $params['broadcast']-1;
          $net = " AND ( ipaddr > $ipfrom AND ipaddr < $ipto )";
          } else $net = "";
         
         // order
         switch ($order)
          {
          case "nodeid"        : $order = " ORDER BY nodeid";                 break;
              case "download"      : $order = " ORDER BY download DESC";      break;
              case "upload"        : $order = " ORDER BY upload DESC";      break;
              case "name"          : $order = " ORDER BY name";                 break;
              case "ip"                : $order = " ORDER BY ipaddr";                 break;
          }
         
         // limits
         if( $limit > 0 ) $limit = " LIMIT ".$limit; else $limit = "";
         
         // join query from parts
         $query = "SELECT nodeid, name, ipaddr, sum(upload) as upload, sum(download) as download FROM stats LEFT JOIN nodes ON stats.nodeid=nodes.id WHERE $dt $net GROUP BY nodeid, name, ipaddr $order $limit";

         // get results
         if ($traffic = $this->DB->GetAll($query))
          {
           foreach ($traffic as $idx => $row)
                   {
                  $traffic[upload][data]          [] = $row[upload];
                   $traffic[download][data]     [] = $row[download];
                   $traffic[upload][name]          [] = $row[name];
                   $traffic[download][name]     [] = $row[name];
                   $traffic[upload][ipaddr]     [] = long2ip($row[ipaddr]);
                   $traffic[download][ipaddr]     [] = long2ip($row[ipaddr]);
                   $traffic[download][sum][data]      += $row[download];
                   $traffic[upload][sum][data]      += $row[upload];
                   }
          
          // get maximum data from array
          $maximum = max($traffic[download][data]);
           if ($maximum < max($traffic[upload][data]))
               $maximum = max($traffic[upload][data]);
           if($maximum == 0)          // do not need divide by zero
               $maximum = 1;
           
          // make data for bars drawing
          $x = 0;
          foreach ($traffic[download][data] as $data)
                   {
                   $traffic[download][bar]     [] = round($data * 150 / $maximum);
                   list($traffic[download][data][$x], $traffic[download][unit][$x]) = setunits($data);
                   $x++;
                   }
          $x = 0;
           foreach ($traffic[upload][data] as $data)
                   {
                   $traffic[upload][bar]     [] = round($data * 150 / $maximum);
                   list($traffic[upload][data][$x], $traffic[upload][unit][$x]) = setunits($data);
                   $x++;
                   }
          
          //set units for data
          list($traffic[download][sum][data], $traffic[download][sum][unit]) = setunits($traffic[download][sum][data]);
           list($traffic[upload][sum][data], $traffic[upload][sum][unit]) = setunits($traffic[upload][sum][data]);
          }
          
         return $traffic;
     }
}

/*
 * $Log$
 * Revision 1.239  2003/09/23 19:21:50  alec
 * poprawione zliczanie zysku miesiecznego oraz bledy w zapytaniu wystepujace na postgresie
 *
 * Revision 1.238  2003/09/23 18:57:43  alec
 * new method for ip search in SearchNodeList()
 *
 * Revision 1.237  2003/09/23 14:25:25  alec
 * update SearchNodeList() ze wzglêdu na nowy format zapisu adresu IP w bazie, dodany ¶rednik w CountNetDevLinks()
 *
 * Revision 1.236  2003/09/22 23:56:47  lukasz
 * *** empty log message ***
 *
 * Revision 1.235  2003/09/22 20:54:09  alec
 * LIKE -> ?LIKE? in SearchNodeList
 *
 * Revision 1.234  2003/09/22 18:07:56  lexx
 * - dalej netdev
 *
 * Revision 1.233  2003/09/22 01:13:54  lukasz
 * - foreach() error
 *
 * Revision 1.232  2003/09/21 18:06:12  lexx
 * - yyy... dalej netdev
 *
 * Revision 1.231  2003/09/18 14:15:53  alec
 * usuniety fatal error powodowany przez podwojona funkcje GetNetDev(), dodana funkcja GetNetDevName i w zwiazku z tym zmiana w GetNode()
 *
 * Revision 1.230  2003/09/17 03:10:39  lukasz
 * - very experimental support for lms-arpd
 *
 * Revision 1.229  2003/09/17 02:14:09  lukasz
 * - to samo co poprzednio dla innych osów
 *
 * Revision 1.228  2003/09/17 02:10:40  lukasz
 * - zmiana zachowania procedury zczytuj±cej mac adresy - na Linuksie szuka
 *   ona wpisów 0x2 (podczas gdy pernamentne s± oznaczone 0x6)
 * - pozosta³e zmiany - zignorowaæ ;)
 *
 * Revision 1.227  2003/09/16 18:35:50  alec
 * Traffic() modified
 *
 * Revision 1.226  2003/09/15 20:53:23  alec
 * function setunits for Traffic() added
 *
 * Revision 1.225  2003/09/15 16:31:04  alec
 * added function Traffic() for stats
 *
 * Revision 1.224  2003/09/13 20:19:56  lexx
 * - lokalizacja
 *
 * Revision 1.223  2003/09/13 12:49:49  lukasz
 * - tsave
 *
 * Revision 1.222  2003/09/12 20:59:20  lexx
 * - netdev
 *
 * Revision 1.221  2003/09/12 20:43:51  lukasz
 * - more cosmetics
 *
 * Revision 1.220  2003/09/12 20:32:24  lukasz
 * - cosmetics
 *
 * Revision 1.219  2003/09/11 19:17:30  lukasz
 * - forgot about SetTS
 *
 * Revision 1.218  2003/09/11 03:42:37  lukasz
 * - rekord u¿ytkownika zwraca tak¿e sumê op³at
 *
 * Revision 1.217  2003/09/10 19:02:52  alec
 * bug fix for postgres in GetUserList
 *
 * Revision 1.216  2003/09/09 21:19:45  lukasz
 * - cleanup
 *
 * Revision 1.215  2003/09/09 20:23:00  lukasz
 * - literówka, czyli Baseciq zna jêz. angielski
 *
 * Revision 1.214  2003/09/09 01:41:09  lukasz
 * - wy¶wietlanie sumy taryf w wynikach wyszukiwania
 *
 * Revision 1.213  2003/09/09 01:22:28  lukasz
 * - nowe finanse
 * - kosmetyka
 * - bugfixy
 * - i inne rzeczy o których aktualnie nie pamiêtam
 *
 * Revision 1.212  2003/09/05 02:07:04  lukasz
 * - massive attack: s/this->ADB->/this->DB->/g
 *
 * Revision 1.211  2003/08/31 19:48:34  alec
 * removed bug in GetNetworkParams()
 *
 * Revision 1.209  2003/08/31 19:16:54  alec
 * added GetNetworkParams
 *
 * Revision 1.208  2003/08/30 01:11:21  lukasz
 * - nowe pole w li¶cie sieci: interfejs
 *
 * Revision 1.207  2003/08/29 22:53:19  lukasz
 * - lista taryf zlicza³a tak¿e u¿ytkowników usuniêtych
 *
 * Revision 1.206  2003/08/29 01:16:28  lukasz
 * - w³±czenie transakcji przy odczytywaniu backup bazy z dysku
 *
 * Revision 1.205  2003/08/28 13:02:05  lukasz
 * - podawanie dnia naliczania op³aty podczas dodawania usera nic dawa³o
 *
 * Revision 1.204  2003/08/27 21:38:13  alec
 * removed two ' :)
 *
 * Revision 1.203  2003/08/27 21:00:33  alec
 * Popr. RecoverUser()
 *
 * Revision 1.202  2003/08/27 20:32:54  lukasz
 * - changed another ENUM (users.deleted) to BOOL
 *
 * Revision 1.201  2003/08/27 20:18:42  lukasz
 * - changed nodes.access from ENUM to BOOL;
 *
 * Revision 1.200  2003/08/27 19:25:00  lukasz
 * - changed format of ipaddr storage in database
 * - propably improved performance
 *
 * Revision 1.199  2003/08/25 02:14:05  lukasz
 * - zmieniona obs³uga usuwania userów
 *
 * Revision 1.198  2003/08/24 13:12:54  lukasz
 * - massive attack: s/<?/<?php/g - that was causing problems on some fucked
 *   redhat's :>
 *
 * Revision 1.197  2003/08/24 00:59:29  lukasz
 * - LMSDB: GetAllByKey($query, $key, $inputarray)
 * - LMS: more fixes for new DAL
 *
 * Revision 1.196  2003/08/23 12:46:57  alec
 * literówka
 *
 * Revision 1.195  2003/08/22 13:15:00  lukasz
 * - fixed MetaTables
 *
 * Revision 1.194  2003/08/22 00:17:50  lukasz
 * - removed ADODB ;>
 *
 * Revision 1.193  2003/08/21 03:14:29  lukasz
 * - http://lists.rulez.pl/lms/0835.html
 *
 * Revision 1.192  2003/08/20 01:46:12  lukasz
 * - do not display MAC's '00:00:00:00:00:00'
 *
 * Revision 1.191  2003/08/18 16:57:00  lukasz
 * - more cvs tags :>
 *
 */

?>
