<?php

/*
 *  LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2011 LMS Developers
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
 *  $Id: functions.php,v 1.7 2011/01/18 08:12:35 alec Exp $
 */

function module_main()
{
$debug=0;

    global $LMS,$SMARTY,$SESSION,$DB;

    $punkty = 0;
    $punkty_max = 7; //tyle ile pytan

    $userinfo = $LMS->GetCustomer($SESSION->id);    
    $assignments = $LMS->GetCustomerAssignments($SESSION->id);    
    //echo '<pre>';print_r($userinfo);echo '</pre>';

    //do testow i sprawdzania
    //if($userinfo[id]==10) $punkty++;

    if(!empty($userinfo[email])) 
    {
        $punkty++;	
        if($debug==1) echo "1. EMAIL OK<br>";
    }

    if($userinfo[type]==0 AND !empty($userinfo[ssn]))
    {
	$punkty++;
        if($debug==1) echo "2. PESEL OK<br>";	
    }
    elseif($userinfo[type]==1 AND !empty($userinfo[ten]))
    {
	$punkty++;
        if($debug==1) echo "2. NIP OK<br>";	
    }	


    
    if($userinfo[consentdate]>0) 
    {
	$punkty++;
        if($debug==1) echo "3. ZGODA OK<br>";	
    }	
    
    foreach($userinfo[contacts] as $item)
    {

        if(count($item[phone]>0) )
        {	
    	    $punkty++;      
            if($debug==1) echo "4. TELEFON OK<br>";        
        }
    }        



    //pobierz kwote abonamentu
    $taryfa_kwota = $assignments[0][value];

    //pobierz id taryfy
    $taryfa_id = $assignments[0][tariffid];
    //$taryfa_id2 = $assignments[1][tariffid]; //jak ktos ma wiecej niz jedna taryfe
    //echo '<pre>';print_r($assignments);echo '</pre>';    
    
    //taryfy, ktore sa objete bonusem, czyli bloki na szkle
    $premiowane_taryfy = array(27,33,34,35);

    // sprawdzenie czy obecna taryfa jest taryfa z bonusem
    $taryfy_premium = array(53,54,55,56);


    //if (in_array($taryfa_id2,$premiowane_taryfy))
    //{
//	$taryfa_id=$taryfa_id2; //jak druga taryfa jest w premiowanych to przypisz 
    //}
    
    if(in_array($taryfa_id,$taryfy_premium))
    {
	$taryfa_bonus =true;
	$punkty++;
	$status=2; //bonus aktywny
        if($debug==1) echo "5. taryfa OK<br>";	
    }    
    elseif (in_array($taryfa_id,$premiowane_taryfy))
    {
	$taryfa = true;
	$punkty++;   
        if($debug==1) echo "5. taryfa OK<br>";
    //przypisanie taryfy z bonusem w zaleznosci od posiadanej taryfy
    if($taryfa_id==27) $taryfa_premium=53; //standard
    if($taryfa_id==33) $taryfa_premium=54; //plus
    if($taryfa_id==35) $taryfa_premium=55; //max
    if($taryfa_id==34) $taryfa_premium=56; //pro
    }


//echo $userinfo[balance];
    if( (date("j")>15) AND ($userinfo[balance]>=0))
    {
	$punkty++;
        if($debug==1) echo "6. bilans > 15 OK<br>";	
        $brak_regularnych_wplat=1;
    }
    elseif( (date("j")<16) AND (($userinfo[balance]+$taryfa_kwota)>=0))
    {
	$punkty++;
        if($debug==1) echo "6. bilans < 16 OK<br>";	
        $brak_regularnych_wplat=1;        
    }	



    $miesiecy_wstecz=6;
    $ilosc_wplat = $DB->GetOne('SELECT COUNT(value) as ile FROM cash WHERE customerid = ? AND value < ?', array($SESSION->id,0));    
    if($ilosc_wplat >= $miesiecy_wstecz )
    {
	$punkty++;    
        if($debug==1) echo "7. ZOB OK<br>";	
    }

    //sprawdzanie czy nie odebrac bonusu
    //czyli jesli mial bonus i teraz ma mniej punktow niz punkty_max odbieramy bonus

    if($punkty<$punkty_max AND $taryfa_bonus===true)
    {
	$bonus=false;
	if($taryfa_id==53) $taryfa_old_id=27;
	if($taryfa_id==54) $taryfa_old_id=33;	
	if($taryfa_id==55) $taryfa_old_id=35;
	if($taryfa_id==56) $taryfa_old_id=34;	
	
	$status=0; //bonus nie aktywny	
	$cid=$userinfo['id'];
	if($taryfa_old_id)
	{
	    //$LMS->DB->Execute('UPDATE assignments SET tariffid = ? WHERE customerid = ?', array($taryfa_old_id, $cid));	
	}
    }elseif($punkty==$punkty_max){
	$bonus=true;
	if($status!=2) $status=1;  //bonus aktywyuj
    }
    
    $SMARTY->assign('status',$status); // 
//    $SMARTY->assign('suma_wplat',$suma_wplat); // suma wplat od x miesiecy do czasu w zaleznosci od nr dnia    
    $SMARTY->assign('bonus',$bonus); // true or false - czy zostawic bonus
    $SMARTY->assign('uid',$userinfo[id]); // id customer
    $SMARTY->assign('punkty',$punkty); // punkty integer
    $SMARTY->assign('taryfa',$taryfa); // true or false - czy taryfa moze byc z bonusem
    $SMARTY->assign('taryfa_bonus',$taryfa_bonus); // true or false - czy obecna taryfa jest z bonusem
    $SMARTY->assign('taryfa_premium',$taryfa_premium);        
    $SMARTY->assign('miesiecy_wstecz',$miesiecy_wstecz);            
    $SMARTY->assign('ilosc_wplat',$ilosc_wplat); // ile wystawiono zobowiazan wciagu $miesiecy_wstecz
    $SMARTY->assign('userinfo',$userinfo);
    $SMARTY->assign('miesiac',$miesiac);    
    $SMARTY->assign('brak_regularnych_wplat',$brak_regularnych_wplat);    
    $SMARTY->assign('bilans',$userinfo[balance]);    
    $SMARTY->display('module:bonus.html');
} 

function module_premia()
{
    global $LMS,$SMARTY,$SESSION,$DB;

    $userinfo = $LMS->GetCustomer($SESSION->id);

    $taryfa=$_POST['taryfa_premium'];        
    $cid=$userinfo['id'];

    // sprawdzamy czy jest ustawiona taryfa i user ID oraz czy zapytanie sql sie wykona
    if($taryfa>0 AND $cid>0 AND $LMS->DB->Execute('UPDATE assignments SET tariffid = ? WHERE customerid = ?', array($taryfa, $cid)) )
    {
	$premia=true;
    }
    
    $SMARTY->assign('premia',$premia);            
    $SMARTY->display('module:bonus_submit.html');
}

	


?>
