<?php

include 'lms_stub.php';
$FILE_NAME = 'Zal_6D_01022016.csv';

if( ($file = fopen($FILE_NAME, "r")) !== FALSE ) {
	$result = array();
	$file2 = fopen('result.php',"w");

	while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {

		$name             = $data[0];
		$row                = explode(',', str_replace(' ', '' ,$data[1]));
		$purchase_price = str_replace(',', '.', $data[2]);
		$sale_price        = str_replace(',', '.', $data[4]);

		$result[$name]['purchase_price'] = $purchase_price;
		$result[$name]['sale_price'] = $sale_price;
	
		if (!isset($result[$name]['prefixes']))
			$result[$name]['prefixes'] = array($row[0]);
		else
			array_push($result[$name]['prefixes'], $row[0]);

		$counter = count($row);
		if( $counter != 1 )
			for($i=1; $i<$counter; ++$i) {
				$prefix = substr($row[0], 0, -strlen($row[$i])) . $row[$i];
				
				if (!in_array($prefix, $result[$name]['prefixes']))
					array_push($result[$name]['prefixes'], $prefix);
				//else
					//fwrite($file2,"DUPLICATE PREFIX $name,". $row[0] . ',' . $prefix. "\n");
			}
	}
	fclose($file);

	$prefixHelperArray = $DB->GetAllByKey("SELECT id, prefix FROM voip_prefix", "prefix");
	$groupHelperArray = $DB->GetAllByKey("SELECT id, name FROM voip_prefix_group", "name");

	$voip_prefix_INSERT = "INSERT INTO voip_prefix (prefix, description) VALUES ";
	$voip_prefix_UPDATE = "INSERT INTO voip_prefix (prefix, description) VALUES ";
	//$voip_prefix_group                   = "INSERT INTO voip_prefix_group (name, description) VALUES ";
	//$voip_prefix_group_assignments = "INSERT INTO voip_prefix_group_assignments (prefixid, groupid) VALUES ";

	foreach ($result as $k=>$v) {
		
		// TABLE `voip_prefix`
		foreach ($v['prefixes'] as $singlePrefix) {
			
			if (!isset($prefixHelperArray[$singlePrefix]))
				$voip_prefix_INSERT .= "('$singlePrefix',''),";
		}

		// TABLE `voip_prefix_group` 
		//$voip_prefix_group .= "('$k',''),";
		
		// TABLE `voip_prefix_group_assignments`
		//foreach ($v['prefixes'] as $singlePrefix)
		//	$voip_prefix .= "('$singlePrefix',''),";
	}

	$voip_prefix_INSERT = rtrim($voip_prefix_INSERT, ",") . ';';

	//$file = fopen('result.php',"w");

	//fwrite($file2, $voip_prefix_INSERT);
	fwrite($file2, print_r($result, true));

	fclose($file2);

	//print_r($voip_prefix_group);
}
?>

















