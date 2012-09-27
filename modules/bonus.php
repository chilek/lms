<?php

function GetBonusUsers()
{
    global $LMS,$SMARTY,$DB;
    //$BonusTariffID=array(53,54,55,56);
    
    $bonus=$DB->GetAll('SELECT DISTINCT assignments.customerid,assignments.tariffid,customers.name,customers.lastname FROM assignments INNER JOIN customers ON customers.id = assignments.customerid WHERE assignments.tariffid IN (53,54,55,56) ORDER BY customers.lastname');
//    echo '<pre>';print_r($bonus);echo '</pre>';

    return $bonus;
}                    



function CheckBonus($cid)
{
    global $LMS,$SMARTY,$DB;

  //$cid=1616;

    $punkty = 0;
    $punkty_max = 7; //tyle ile pytan
          
    $userinfo = $LMS->GetCustomer($cid);    
    $assignments = $LMS->GetCustomerAssignments($cid);    
    //echo '<pre>';print_r($assignments);echo '</pre>';
                      
    //do testow i sprawdzania
    //if($userinfo[id]==10) $punkty++;
                              
    //1
    if(!empty($userinfo[email])) { $punkty++; }
    //echo $punkty;
    
    //2                                              
    if($userinfo[type]==0 AND !empty($userinfo[ssn]))
    {
	$punkty++;
    }
    elseif($userinfo[type]==1 AND !empty($userinfo[ten]))
    {
	$punkty++;
    }	
    //echo $punkty;
                
    //3                                                                	    
    if($userinfo[consentdate]>0) $punkty++;
    //echo $punkty;
    
    //echo '<pre>';print_r($userinfo[contacts]);echo '</pre>'
    //echo count($userinfo[contacts]).'<br>';
    
    //4
    foreach($userinfo[contacts] as $item)
    {
        if(count($item[phone])>0) $punkty++; 
        
    }   
    //if(count($userinfo[contacts])==2) $punkty--;    //jak ktos ma wiecej niz 1 telefon
    //echo $punkty;
                                                                    	                                
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
                                                                    	                                                                            




    
    //5                                                                	                                                                                            
    if(in_array($taryfa_id,$taryfy_premium))
    {
	$taryfa_bonus =true;
        $punkty++;
    	$status=2; //bonus aktywny
    }    
    elseif (in_array($taryfa_id,$premiowane_taryfy))
    {
    	$taryfa = true;
	$punkty++;   
        //przypisanie taryfy z bonusem w zaleznosci od posiadanej taryfy
        if($taryfa_id==27) $taryfa_premium=53; //standard
        if($taryfa_id==33) $taryfa_premium=54; //plus
        if($taryfa_id==35) $taryfa_premium=55; //max
        if($taryfa_id==34) $taryfa_premium=56; //pro
    }
//    echo $punkty;                                                                    	                                                                                                		        	                            



// dzien na 18 bo jak 15 trafi w piatek....
    
    //6
    if((date("j")>18) AND ($userinfo[balance]>=0))
    {
	$punkty++;
//	echo $userinfo[balance];
    }
    elseif( (date("j")<=18) AND (($userinfo[balance]+$taryfa_kwota)>=0))
    {
    	$punkty++;
    //    echo $punkty;	
           }
    //echo $punkty;    
    






    $miesiecy_wstecz=7;
//echo     $suma_wplat = $DB->GetOne('SELECT SUM(value) as suma FROM cash WHERE customerid = ?', array($cid));    
    $ilosc_wplat = $DB->GetOne('SELECT COUNT(value) as ile FROM cash WHERE customerid = ? AND value < ?', array($cid,0));    
    if($ilosc_wplat >= $miesiecy_wstecz )
    { 
	$punkty++;  
    }



    
    
    //echo $punkty;
    //sprawdzanie czy nie odebrac bonusu
    //czyli jesli mial bonus i teraz ma mniej punktow niz punkty_max odbieramy bonus
    if($punkty<$punkty_max AND $taryfa_bonus===true)
    {
    //echo 1;
    	$bonus=false;
        if($taryfa_id==53) $taryfa_old_id=27;
	if($taryfa_id==54) $taryfa_old_id=33;	
	if($taryfa_id==55) $taryfa_old_id=35;
        if($taryfa_id==56) $taryfa_old_id=34;	
        $status=0; //bonus nie aktywny	
        $cid=$userinfo['id'];

        if($taryfa_old_id)
        {
// szukam problemu wylanczania bonusu        
//    	    $LMS->DB->Execute('UPDATE assignments SET tariffid = ? WHERE customerid = ?', array($taryfa_old_id, $cid));	
        }
    }elseif($punkty==$punkty_max){
    	$bonus=true;
        if($status!=2) $status=1;  //bonus aktywyuj
    }

return $status;
}

GetBonusUsers();
CheckBonus();

$bonus=GetBonusUsers();
$ilu=count($bonus);

//echo "<ol>";
for($i=0;$i<$ilu;$i++)
{
    $bonus[$i][status]=checkBonus($bonus[$i][customerid]);
    //echo "<li>".$bonus[$i][lastname]." ".$bonus[$i][name]." - ".$bonus[$i][status]."</li>";
}
//echo "</ol>";


$SMARTY->assign('bonus',$bonus);
$SMARTY->display('bonus.html');
?>

