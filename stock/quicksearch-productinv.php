<?php
		$search = str_replace(' ', '%', $search);
		$sql_search = $DB->Escape("%$search%");
		if(isset($_GET['ajax'])) {
			$candidates = $DB->GetAll("SELECT s.id, s.productid, s.serialnumber, s.pricebuynet, s.pricebuygross, s.bdate, s.gtu_id, p.quantity,
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
			
				$result = array();
				if ($candidates)
					foreach($candidates as $idx => $row) {
						$name = $row['name'];
						
						if ($row['serialnumber'])
							$name = $name." (S/N: ".trim($row['serialnumber']).")";

						$name_classes = array();
						$name_class = '';

						$price['pricebuynet'] = str_replace(',', '.', round($row['pricebuynet']/$row['quantity'], 2));
						$price['pricebuygross'] = str_replace(',', '.', round($row['pricebuygross']/$row['quantity'], 2));

						$action = 'javascript:pinv(\''.$row['id'].'\',\''.$price['pricebuynet'].'\',\''.$price['pricebuygross'].'\', \''.$row['ql'].'\',\''.$row['gtu_id'].'\')';
						if (preg_match("~^$search\$~i",$row['id'])) {
							$description = trans('Id:').' '.$row['id'];
							continue;
						} elseif (preg_match("~$search~i",$row['name'])) {
							$description = '';
						} elseif (preg_match("~$search~i",$row['ean'])) {
							$description = escape_js(trans('EAN:').' '.$row['ean']);
							continue;
						} else
							$description = '';
						
						$description_class = '';

						$description .= '('.$row['id'].') '.trans("Bought:")." ".date("d/m/Y", $row['bdate']);

						$result[$row['id']] = compact('name', 'name_class', 'description', 'description_class', 'action','price');

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
?>
