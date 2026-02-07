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
					$desc = $row['name'];

						if ($row['serialnumber'])
							$desc = $desc." (S/N: ".$row['serialnumber'].")";

						$actions[$row['id']] = '?m=stckproductinfo&id='.$row['productid'];
						$eglible[$row['id']] = escape_js(($row['deleted'] ? '<font class="blend">' : '')
						.truncate_str($desc, 100).($row['deleted'] ? '</font>' : ''));

						if (is_null($row['warranty']))
							$row['warranty'] = 'b/d';

						if ($row['leavedate'] < 1)
							$row['leavedate'] = 'b/d';
						else
							$row['leavedate'] = date("d/m/Y", $row['leavedate']);

						$descriptions[$row['id']] = '<b>'.trans("Gross:")." ".moneyf($row['pricesell'])."</b> ".trans("Sold:")." ".$row['leavedate']." ".trans("Warranty:")." ".$row['warranty'];
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
