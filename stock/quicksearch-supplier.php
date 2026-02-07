<?php
	if (empty($search)) {
            die;
        }
	
	if(isset($_GET['ajax'])) { // support for AutoSuggest 

		$quicksearch_limit = intval(ConfigHelper::getConfig('phpui.quicksearch_limit', 15));

		$candidates = $DB->GetAll("SELECT id, address, post_name, post_address, deleted,
		    ".$DB->Concat('UPPER(lastname)',"' '",'name')." AS customername
			FROM customerview
			WHERE ".(preg_match('/^[0-9]+$/', $search) ? 'id = '.intval($search).' OR ' : '')."
				LOWER(".$DB->Concat('lastname',"' '",'name').") ?LIKE? LOWER($sql_search)
				OR LOWER(address) ?LIKE? LOWER($sql_search)
				OR LOWER(post_name) ?LIKE? LOWER($sql_search)
				OR LOWER(post_address) ?LIKE? LOWER($sql_search)
			ORDER by deleted, username, address
			LIMIT 15");
		
		$result = array();
		//$eglible=array(); $actions=array(); $descriptions=array();
		if ($candidates)
			foreach($candidates as $idx => $row) {
				switch ($_GET['source']) {
					case 'rne':
						$action = '?m=stckreceivenoteedit&id='.$_GET['sid'].'&sid='.$row['id'];
						break;
					default:
						$action = '?m=stckreceiveadd&sid='.$row['id'];
						break;
				}

				$name = truncate_str('(#' . $row['id'] . ') ' . $row['customername'], 50);

				$name_classes = array();
				if ($row['deleted']) {
					$name_classes[] = 'blend';
				}
				$name_class = implode(' ', $name_classes);

				/*$eglible[$row['id']] = escape_js(($row['deleted'] ? '<font class="blend">' : '')
				    .truncate_str($row['username'], 50).($row['deleted'] ? '</font>' : ''));

				if (preg_match("~^$search\$~i",$row['id'])) {
				    $descriptions[$row['id']] = escape_js(trans('Id:').' '.$row['id']);
				    continue;
				}
				if (preg_match("~$search~i",$row['username'])) {
				    $descriptions[$row['id']] = '';
				    continue;
				}
				if (preg_match("~$search~i",$row['address'])) {
				    $descriptions[$row['id']] = escape_js(trans('Address:').' '.$row['address']);
				    continue;
				}
				else if (preg_match("~$search~i",$row['post_name'])) {
				    $descriptions[$row['id']] = escape_js(trans('Name:').' '.$row['post_name']);
				    continue;
				}
				else if (preg_match("~$search~i",$row['post_address'])) {
				    $descriptions[$row['id']] = escape_js(trans('Address:').' '.$row['post_address']);
				    continue;
				}
				$descriptions[$row['id']] = '';*/
				if ((empty($properties) || isset($properties['name'])) && $customer_count[$row['customername']]) {
					$description = isset($row['address']) ? htmlspecialchars($row['address']) : '';
					if (!empty($row['post_address'])) {
						$description .= '<BR>' . htmlspecialchars($row['post_address']);
						if (!empty($row['post_name'])) {
							$description .= '<BR>' . htmlspecialchars($row['post_name']);
						}
					}
				} else if ((empty($properties) || isset($properties['id'])) && preg_match("~^$search\$~i", $row['id'])) {
					$description = trans('Address:') . ' ' . htmlspecialchars($row['address']);
					//$description = trans('Id:') . ' ' . $row['id'];
				} else if ((empty($properties) || isset($properties['name'])) && preg_match("~$search~i", $row['customername'])) {
					$description = '';
				} else if ((empty($properties) || isset($properties['altname'])) && preg_match("~$search~i", $row['altname'])) {
					$description = trans('Alternative name:') . ' ' . htmlspecialchars($row['altname']);
				} else if ((empty($properties) || isset($properties['address'])) && preg_match("~$search~i", $row['address'])) {
					$description = trans('Address:') . ' ' . htmlspecialchars($row['address']);
				} else if ((empty($properties) || isset($properties['post_name'])) && preg_match("~$search~i", $row['post_name'])) {
					$description = trans('Name:') . ' ' . htmlspecialchars($row['post_name']);
				} else if ((empty($properties) || isset($properties['post_address'])) && preg_match("~$search~i", $row['post_address'])) {
					$description = trans('Address:') . ' ' . htmlspecialchars($row['post_address']);
				} else if ((empty($properties) || isset($properties['location_name'])) && preg_match("~$search~i", $row['location_name'])) {
					$description = trans('Name:') . ' ' . htmlspecialchars($row['location_name']);
				} else if ((empty($properties) || isset($properties['location_address'])) && preg_match("~$search~i", $row['location_address'])) {
					$description = trans('Address:') . ' ' . htmlspecialchars($row['location_address']);
				} else if ((empty($properties) || isset($properties['email'])) && preg_match("~$search~i", $row['email'])) {
					
					$description = trans('E-mail:') . ' ' . $row['email'];
				} else if ((empty($properties) || isset($properties['bankaccount']))
					&& preg_match('~' . preg_replace('/[\- ]/', '', $search) . '~i', preg_replace('/[\- ]/', '', $row['bankaccount']))) {
					$description = trans('Alternative bank account:') . ' ' . format_bankaccount($row['bankaccount']);
				} else if ((empty($properties) || isset($properties['ten']))
					&& preg_match('~' . preg_replace('/[\- ]/', '', $search) . '~i', preg_replace('/[\- ]/', '', $row['ten']))) {
					$description = trans('TEN:') . ' ' . $row['ten'];
				} else if ((empty($properties) || isset($properties['ssn']))
&& preg_match('~' . preg_replace('/[\- ]/', '', $search) . '~i', preg_replace('/[\- ]/', '', $row['ssn']))) {
$description = trans('SSN:') . ' ' . $row['ssn'];
} else if ((empty($properties) || isset($properties['additional-info'])) && preg_match("~$search~i", $row['info'])) {
$description = trans('Additional information:') . ' ' . $row['info'];
} else if ((empty($properties) || isset($properties['notes'])) && preg_match("~$search~i", $row['notes'])) {
$description = trans('Notes:') . ' ' . $row['notes'];
} else if ((empty($properties) || isset($properties['documentmemo'])) && preg_match("~$search~i", $row['documentmemo'])) {
$description = trans('Document memo:') . ' ' . $row['documentmemo'];
}

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

		if(is_numeric($search)) // maybe it's customer ID
		{
			if($customerid = $DB->GetOne('SELECT id FROM customersview WHERE id = '.$search))
			{
				$target = '?m=customerinfo&id='.$customerid;
			}
		}
?>
