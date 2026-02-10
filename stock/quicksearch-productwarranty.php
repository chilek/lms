<?php			
			$search = str_replace(' ', '%', $search);
			$sql_search = $DB->Escape("%$search%");
			if(isset($_GET['ajax'])) {
				$candidates = $DB->GetAll("SELECT s.id, s.productid, s.serialnumber, s.pricesell, s.leavedate, s.warranty,
					".$DB->Concat('m.name',"' '",'p.name')." as name, p.ean
					FROM stck_stock s
					LEFT JOIN stck_products p ON p.id = s.productid
					LEFT JOIN stck_manufacturers m ON m.id = p.manufacturerid
					LEFT JOIN stck_warehouses w ON s.warehouseid = w.id
					WHERE
					LOWER(s.serialnumber) ?LIKE? LOWER(".$sql_search.")
					ORDER BY s.creationdate ASC, name
					LIMIT 15");
				
				$eglible=array(); $actions=array(); $descriptions=array();
				if ($candidates)
					foreach($candidates as $idx => $row) {
						$name = $row['name'];
						$name_class = '';

						if ($row['serialnumber'])
							$name .= " (S/N: ".$row['serialnumber'].")";

						$action = '?m=stckproductinfo&ssp=1&id='.$row['productid'];

						if (is_null($row['warranty']))
							$row['warranty'] = 'b/d';

						if ($row['leavedate'] < 1)
							$row['leavedate'] = 'b/d';
						else
							$row['leavedate'] = date("d/m/Y", $row['leavedate']);

						$description = '<b>'.trans("Gross:")." ".moneyf($row['pricesell'])."</b> ".trans("Sold:")." ".$row['leavedate']." ".trans("Warranty:")." ".$row['warranty'];
						$description_class = '';
						
						
						$result[$row['id']] = compact('name', 'name_class', 'description', 'description_class', 'action');
					}
				$hook_data = array(
                                	'search' => $search,
                                	'sql_search' => $sql_search,
                               		'properties' => $properties,
                        	        'session' => $SESSION,
                        	        'result' => $result
				);
				$hook_data = $LMS->executeHook('quicksearch_ajax_document', $hook_data);
				$result = $hook_data['result'];
				header('Content-type: application/json');
		 		echo json_encode(array_values($result));
				$SESSION->close();
				exit;

			}
			exit;			
?>
