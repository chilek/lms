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
						$desc = $row['name'];
						
						if ($row['serialnumber'])
							$desc = $desc." (S/N: ".$row['serialnumber'].")";

						//$actions[$row['id']] = 'javascript:pinv(\''.$row['id'].'\',\''.$row['pricebuynet'].'\',\''.$row['pricebuygross'].'\')';
						$actions[$row['id']] = '?m=stckproductinfo&id='.$row['productid'];
						$eglible[$row['id']] = escape_js(($row['deleted'] ? '<font class="blend">' : '')
						.truncate_str($desc, 100).($row['deleted'] ? '</font>' : ''));

						$descriptions[$row['id']] = '<b>'.trans("Gross:")." ".$row['pricebuygross']."</b> ".trans("Bought:")." ".date("d/m/Y", $row['bdate']);;
					}

				header('Content-type: text/plain');
				if ($eglible) {
					print "this.eligible = [\"".implode('","',$eglible)."\"];\n";
					print "this.descriptions = [\"".implode('","',$descriptions)."\"];\n";
					print "this.actions = [\"".implode('","',$actions)."\"];\n";
				} else {
					print "false;\n";
				}


			}
			exit;
?>
