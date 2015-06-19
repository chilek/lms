<?php
/*
 * OKBANK.PL parser wyciagow w formacie XML dla Orzesko-Knurowskiego Banku Spółdzielczego z siedzibą w Knurowie
 * $upload_dir - gdzie maja sie zapisywac wyciagi
 * $file - sprawdz czy katalog jest zapisywalny
 */
 
$upload_dir = $CONFIG['okbank']['upload_dir'];
$file= $upload_dir.'index.html';
 
$layout['pagetitle'] = 'Wyciagi';
 
function clean($txt) 
{
    $fix = array('\'','"');
    return str_replace($fix, '',stripslashes($txt));
}
 
function parserXML($plik_xml)
{	
global $arr;
global $arr_dup;
global $rows_affected;
global $suma_wplat;
 
$suma_wplat=0;
$rows_affected=0;
 
    $xml = simplexml_load_file($plik_xml);
    // przetwarzanie XML
    foreach($xml -> RACH_WIRTUALNY as $element)
    {
	foreach($element -> attributes() as $nazwa=>$wartosc)
    	{		
	$date=strtotime($element->DATA_KS);
	$nr_dok=clean($element->NR_DOK);	//nr transakcji bez tego moga byc duplikaty
	$value=clean($element->KWOTA);
	$customer=clean($element->ZLECENIODAWCA);
	$description=clean($element->TYTUL);
	$customerid=substr($element->RACH_BENEF,-4)*1;
	$hash=md5($nr_dok.$date.$value.$customer.$description);
 
	$q="SELECT hash FROM cashimport WHERE hash='$hash'";
	$r=mysql_query($q);
	$duplikat=mysql_num_rows($r);
 
	    if($duplikat==0)
	    {
 
	    if(!mysql_query("INSERT INTO cashimport ( date , value , customer , description , customerid , hash ) VALUES ('$date', '$value', '$customer', '$description', '$customerid', '$hash')"))
		{
		    echo "<p>ERROR: ".mysql_errno() . ": " . mysql_error(). "</p>";
	    }else{
	        $i++;    
	        $arr[$i]=array("id"=>$i,"date"=>$element->DATA_KS,"value"=>$value,"customer"=>$customer,"description"=>$description);
	        $suma_wplat=$suma_wplat+$value;			    
	        $rows_affected=$rows_affected+mysql_affected_rows();			    
	    }
 
	    }else{
	    $j++;    
	    $arr_dup[$j]=array("id"=>$j,"date"=>$element->DATA_KS,"value"=>$value,"customer"=>$customer,"description"=>$description);        
	    }		
 
 
	}
    }
}	
 
 
if($_GET['act'] == 'parsexml')
{
    $plik_tmp = $_FILES['plik']['tmp_name'];
    $plik_nazwa = $_FILES['plik']['name'];
    $plik_rozmiar = $_FILES['plik']['size'];
    $plik = $upload_dir.$plik_nazwa;
    if(is_uploaded_file($plik_tmp))
    {
	move_uploaded_file($plik_tmp,$plik);
	$blad="all ok";
	parserXML($plik);
    }	else {
	echo "Błąd plik nie został załadowany!";
	echo "<pre>";print_r($_FILES);echo "</pre>";	
    }
}else{
    $blad="no action parsexml"; //$error is reserved by lms
}
 
 
if (!file_exists($upload_dir)) {
    echo "<p>Directory $upload_dir not exists!</p>";
    if(mkdir($upload_dir, 0777, true)) {
	echo "<p>Directory $upload_dir created successfully!</p>";
    }else{
	echo "<p>Can't create directory $upload_dir!</p>";	
    }
}


if (!is_writable($file)) {
    echo "<p>The file in $file is not writable!</p>";
    if( chmod($upload_dir, 0766) ) {
	echo "<p>Permission changed to 766 successfully!</p>";
	    if( fopen($file, 'w') ) {
		echo "<p>File $file created successfully!</p>";
	    }else{
		echo "<p>Can't create file $file</p>";
	    }
    }
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);
$SMARTY->assign('rows_affected',$rows_affected);
$SMARTY->assign('error',$error);
$SMARTY->assign('arr',$arr);
$SMARTY->assign('arr_dup',$arr_dup);
$SMARTY->assign('suma_wplat',round($suma_wplat,2));
$SMARTY->display('alfa_cashimportokbank.html');
?>