<?php
	if (empty($search))
		die;

	if(isset($_GET['ajax'])) {

		$quicksearch_limit = intval(ConfigHelper::getConfig('phpui.quicksearch_limit', 15));

		$candidates = $DB->GetAll("SELECT ".$DB->Concat('m.name', '" "', 'p.name')." AS name, p.id, p.ean, p.quantity, p.gtu_id
			FROM stck_products p
			LEFT JOIN stck_manufacturers m ON p.manufacturerid =  m.id
			WHERE ".(preg_match('/^[0-9]+$/', $search) ? 'p.id = '.intval($search).' OR ' : '')."
			LOWER(".$DB->Concat('m.name',"' '",'p.name').") ?LIKE? LOWER($sql_search)
			OR LOWER(p.ean) ?LIKE? LOWER($sql_search)
			ORDER by name, id
			LIMIT 15");

		$result = array();
		//$eglible=array(); $actions=array(); $descriptions=array();
		if ($candidates)
			 foreach($candidates as $idx => $row) {
				$action = 'javascript:stckrnpadd(\''.$row['id'].'\',\''.$row['gtu_id'].'\')';
				$name = truncate_str($row['name'], 150);
				
				$name_classes = array();

                                $name_class = '';

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
	/*	
		if(is_numeric($search)) { // maybe it's product ID
			if($customerid = $DB->GetOne('SELECT id FROM stck_products WHERE id = '.$search)) {
				$target = '?m=customerinfo&id='.$customerid;
			}
		}*/
?>
