<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

function select_producer($id)
{
    $JSResponse = new xajaxResponse();
    $models = LMSDB::getInstance()->GetAll('
        SELECT id, name
        FROM netdevicemodels 
        WHERE netdeviceproducerid = ?
        ORDER BY name', 
        array($id)
    );
    $JSResponse->call('update_models', (array)$models);
    return $JSResponse;
}

if (isset($_GET['ajax']) && isset($_GET['what'])) {
	header('Content-type: text/plain');
	$search = urldecode(trim($_GET['what']));
	if (!strlen($search)) {
		print "false;";
		die;
	}
    $list = $DB->GetAll('SELECT id, name
        FROM netdeviceproducers
        WHERE name ?LIKE? ' . $DB->Escape("%$search%") . '
            OR alternative_name ?LIKE? ' . $DB->Escape("%$search%") . '
        ORDER BY name
        LIMIT 10'
    );

	$result = array();
	if ($list)
	    foreach ($list as $idx => $row) {
	    	$name = $row['name'];
	    	$name_class = '';
	    	$description = $description_class = '';
    		$action = sprintf("javascript: search_producer(%d)", $row['id']);

			$result[$row['id']] = compact('name', 'name_class', 'description', 'description_class', 'action');
		}
    header('Content-Type: application/json');
    if (!empty($result))
        echo json_encode(array_values($result));
	die;
}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction('select_producer');
$SMARTY->assign('xajax', $LMS->RunXajax());

$layout['pagetitle'] = trans('Select model');

$netdevmodelid = isset($_GET['netdevmodelid']) ? intval($_GET['netdevmodelid']) : null;
$producer = isset($_GET['producer']) ? $_GET['producer'] : null;
$model = isset($_GET['model']) ? $_GET['model'] : null;

$producers = $DB->GetAll('
    SELECT id, name
    FROM netdeviceproducers 
    ORDER BY name'
);

$data = array();
$models = array();
if ($netdevmodelid) {
    $data = $DB->GetRow('
        SELECT m.id AS modelid, p.id AS producerid
        FROM netdeviceproducers p
        JOIN netdevicemodels m ON p.id = m.netdeviceproducerid
        WHERE m.id = ?',
        array($netdevmodelid)
    );
} else {
    if ($model) {
        $data = $DB->GetRow('
            SELECT m.id AS modelid, p.id AS producerid
            FROM netdeviceproducers p
            JOIN netdevicemodels m ON p.id = m.netdeviceproducerid
            WHERE m.name = ? OR m.alternative_name = ?',
            array($model, $model)
        );
    }
    if (empty($data) && $producer) {
        $data = $DB->GetRow('
            SELECT p.id AS producerid
            FROM netdeviceproducers p
            WHERE p.name = ? OR p.alternative_name = ?',
            array($producer, $producer)
        );
    }
}

if (isset($data['producerid'])) {
    $models = $DB->GetAll(
        'SELECT id, name
        FROM netdevicemodels
        WHERE netdeviceproducerid = ?',
        array($data['producerid'])
    );
}

$data['varname'] = $_GET['name'];
$data['formname'] = $_GET['form'];

$SMARTY->assign('data', $data);
$SMARTY->assign('producers', $producers);
$SMARTY->assign('models', $models);
$SMARTY->display('choose/choosenetdevmodel.html');
?>

