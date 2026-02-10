<?php			
			$search = str_replace(' ', '%', $search);
			$sql_search = $DB->Escape("%$search%");
			if(isset($_GET['ajax'])) {
				$candidates = $DB->GetAll("SELECT s.id, s.productid, s.serialnumber, s.pricebuynet, s.pricebuygross, s.bdate,
					".$DB->Concat('m.name',"' '",'p.name')." as name, p.ean
					FROM stck_stock s
					LEFT JOIN stck_products p ON p.id = s.productid
					LEFT JOIN stck_manufacturers m ON m.id = p.manufacturerid
					LEFT JOIN stck_warehouses w ON s.warehouseid = w.id
					WHERE s.sold = 0 AND
					(".(preg_match('/^[0-9]+$/', $search) ? 's.productid = '.intval($search).' OR ' : '')."
					LOWER(".$DB->Concat('m.name',"' '",'p.name').") ?LIKE? LOWER(".$sql_search.")
					OR LOWER(p.ean) ?LIKE? LOWER(".$sql_search.")
					OR LOWER(s.serialnumber) ?LIKE? LOWER(".$sql_search."))
					AND w.commerce = 1
					ORDER BY s.creationdate ASC, name
					LIMIT 15");
				
				$eglible=array(); $actions=array(); $descriptions=array();
				if ($candidates)
					foreach($candidates as $idx => $row) {
						$name = $row['name'];
						$name_classes = array();
                                                $name_class = '';

						if ($row['serialnumber'])
							$description = "(S/N: ".escape_js($row['serialnumber']).")";
						else
							$description = '';

						$description_class = '';

						//$actions[$row['id']] = 'javascript:pinv(\''.$row['id'].'\',\''.$row['pricebuynet'].'\',\''.$row['pricebuygross'].'\')';
						$action = '?m=stckproductinfo&id='.$row['productid'];
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
