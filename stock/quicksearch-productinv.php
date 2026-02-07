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
			//	if ($DB->_error)
			/*$candidates = $DB->GetAll("SELECT ".$DB->Concat('m.name', '" "', 'p.name')." AS name, p.id, p.ean, p.quantity
				FROM stck_products p
				LEFT JOIN stck_manufacturers m ON p.manufacturerid = m.id
				WHERE ".(preg_match('/^[0-9]+$/', $search) ? 'p.id = '.intval($search).' OR ' : '')."
				LOWER(".$DB->Concat('m.name',"' '",'p.name').") ?LIKE? LOWER($sql_search)
				OR LOWER(p.ean) ?LIKE? LOWER($sql_search)
				ORDER by name, id
				LIMIT 15");*/
				
				$eglible=array(); $actions=array(); $descriptions=array();
				if ($candidates)
					foreach($candidates as $idx => $row) {
						$desc = $row['name'];
						
						if ($row['serialnumber'])
							$desc = $desc." (S/N: ".$row['serialnumber'].")";

						if ($row['quantity'] > 1) {
							$row['pricebuynet'] = str_replace(',', '.', round($row['pricebuynet']/$row['quantity'], 2));
							$row['pricebuygross'] = str_replace(',', '.', round($row['pricebuygross']/$row['quantity'], 2));
						}

						$actions[$row['id']] = 'javascript:pinv(\''.$row['id'].'\',\''.$row['pricebuynet'].'\',\''.$row['pricebuygross'].'\', \''.$row['ql'].'\',\''.$row['gtu_id'].'\')';
						$eglible[$row['id']] = escape_js(($row['deleted'] ? '<font class="blend">' : '')
						.$desc.($row['deleted'] ? '</font>' : ''));

						$descriptions[$row['id']] = '('.$row['id'].') '.trans("Bought:")." ".date("d/m/Y", $row['bdate']);;

					/*	if (preg_match("~^$search\$~i",$row['id'])) {
							$descriptions[$row['id']] = $descriptions[$row['id']].escape_js(trans('Id:').' '.$row['id']);
							continue;
						}
				
						if (preg_match("~$search~i",$row['name'])) {
							//$descriptions[$row['id']] = '';
							continue;
						}
						
						if (preg_match("~$search~i",$row['ean'])) {
							$descriptions[$row['id']] = $descriptions[$row['id']].escape_js(trans('EAN:').' '.$row['ean']);
							continue;
						}

						if (preg_match("~$search~i",$row['serialnumber'])) {
							$descriptions[$row['id']] = $descriptions[$row['id']].escape_js(trans('S/N:').' '.$row['serialnuber']);
							continue;
						}*/
						//continue;
					}

			header('Content-type: text/plain');
			if ($eglible) {
				print "this.eligible = [\"".implode('","',$eglible)."\"];\n";
				print "this.descriptions = [\"".implode('","',$descriptions)."\"];\n";
				print "this.actions = [\"".implode('","',$actions)."\"];\n";
			} else {
				print "false;\n";
			}
			exit;
			}

		/*	if(is_numeric($search)) { // maybe it's product ID
				if($customerid = $DB->GetOne('SELECT id FROM stck_products WHERE id = '.$search)) {
					$target = '?m=customerinfo&id='.$customerid;
					break;
				}
			}*/
?>
