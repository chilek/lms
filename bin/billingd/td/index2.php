<?php

include 'lms_stub.php';

$FILE_NAME = 'Zal_6D_01022016.csv';
$resultArray = array();
$maxPrefixLength = array( 0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>0, 9=>0 );
$groups = array();

if( ($file = fopen($FILE_NAME, "r")) !== FALSE ) {
	
	while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
		//echo $data[0], "<br>";
		
		
		
		$data[2] = str_replace(',', '.', $data[2]);
		$data[4] = str_replace(',', '.', $data[4]);
		$row      = str_replace(' ', '' ,$data[1]);
		$row      = explode(',', $row);
	

		$resultArray[] = $row[0];
		$groups[$data[0]][$row[0]] = $row[0];
		
		if ($maxPrefixLength[$row[0][0]] < strlen($row[0]))	
			$maxPrefixLength[$row[0][0]] = strlen($row[0]);	
		
		$counter = count($row);
		if( $counter != 1 )
			for($i=1; $i<$counter; ++$i) {
				$key = substr($row[0], 0, -strlen($row[$i])) . $row[$i];
				$resultArray[] = $row[0];
				$groups[$data[0]][$row[0]] = $row[0];
				
				if ($maxPrefixLength[$key[0]] < strlen($key))	
					$maxPrefixLength[$key[0]] = strlen($key);	
			}
	}
	fclose($file);
}

sort($resultArray);

$prefixString = "INSERT INTO voip_prefix (prefix, description) VALUES ";
foreach ($resultArray as $k)
	$prefixString .= "('$k',''),";
$prefixString = rtrim($prefixString, ",") . ';';	

$groupString = "INSERT INTO voip_prefix_group (name, description) VALUES ";
foreach ($groups as $k=>$v)
	$groupString .= "('$k',''),";
$groupString = rtrim($groupString, ",") . ';';	

$groupStringAss = "INSERT INTO voip_prefix_group_assignments (prefixid, groupid) VALUES ";
foreach ($groups as $k=>$v)
{
	foreach($v as $singlePrefix) {
		
		//$groupID = $DB->GetRow("SELECT id FROM voip_prefix_group WHERE name like '$k'");
		//$prefixID = $DB->GetRow("SELECT id FROM voip_prefix WHERE prefix like '$singlePrefix'");
		//$groupStringAss .= '(' . $prefixID['id'] . ',' . $groupID['id'] . '),';
	}
}
	
$groupStringAss = rtrim($groupStringAss, ",") . ';';





$groupTariff = "INSERT INTO voip_tariff (prefixid, groupid, tariffid, price, unitsize) VALUES ";
foreach ($groups as $k=>$v)
{
	foreach($v as $singlePrefix) {	
		$groupID = $DB->GetRow("SELECT id FROM voip_prefix_group WHERE name like '$k'");
		$groupTariff .= "(NULL, " . $groupID['id'] . ", 1, 0.22, 60),";	
	}
}
$groupTariff = rtrim($groupTariff, ",") . ';';


//print_r($groups);

 $file = fopen('result.php',"w");
// fwrite($file, "$prefixString");
// fwrite($file, "\n\n\n");
// fwrite($file, "$groupString");
// fwrite($file, "\n\n\n");
// fwrite($file, "$groupStringAss");
 fwrite($file, "$groupTariff");

 fclose($file);

?>






















