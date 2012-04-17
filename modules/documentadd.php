<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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

$_DOC_DIR = DOC_DIR;

function plugin($template, $customer) {
	global $_DOC_DIR;

	$result = '';

	// xajax response object, can be used in the plugin
	$JSResponse = new xajaxResponse();

	// read template information
	if (file_exists($file = DOC_DIR . '/templates/' . $template . '/info.php'))
		include($file);
	// call plugin
	if (!empty($engine['plugin']) && file_exists($file = DOC_DIR . '/templates/' . $engine['name'] . '/' . $engine['plugin'] . '.php'))
		include($file);

	$JSResponse->assign('plugin', 'innerHTML', $result);
	return $JSResponse;
}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction('plugin');
$SMARTY->assign('xajax', $LMS->RunXajax());

if (isset($_POST['document'])) {
	$document = $_POST['document'];

	$oldfromdate = $document['fromdate'];
	$oldtodate = $document['todate'];

	if (!($document['title'] || $document['description'] || $document['type'])) {
		$SESSION->redirect('?' . $SESSION->get('backto'));
	}

	$document['customerid'] = isset($_POST['customerid']) ? intval($_POST['customerid']) : intval($_POST['customer']);

	if (!$LMS->CustomerExists(intval($document['customerid'])))
		$error['customer'] = trans('Customer not selected!');

	if (!$document['type'])
		$error['type'] = trans('Document type is required!');

	if (!$document['title'])
		$error['title'] = trans('Document title is required!');


	// check if selected customer can use selected numberplan
	if ($document['numberplanid'] && $document['customerid']
			&& !$DB->GetOne('SELECT 1 FROM numberplanassignments
	                WHERE planid = ? AND divisionid IN (SELECT divisionid
				FROM customers WHERE id = ?)', array($document['numberplanid'], $document['customerid']))) {
		$error['number'] = trans('Selected numbering plan doesn\'t match customer\'s division!');
	}
	// check number
	elseif ($document['number'] == '') {
		$tmp = $LMS->GetNewDocumentNumber($document['type'], $document['numberplanid']);
		$document['number'] = $tmp ? $tmp : 0;
		$autonumber = true;
	} elseif (!preg_match('/^[0-9]+$/', $document['number']))
		$error['number'] = trans('Document number must be an integer!');
	elseif ($LMS->DocumentExists($document['number'], $document['type'], $document['numberplanid']))
		$error['number'] = trans('Document with specified number exists!');

	if ($document['fromdate']) {
		$date = explode('/', $document['fromdate']);
		if (checkdate($date[1], $date[2], $date[0]))
			$document['fromdate'] = mktime(0, 0, 0, $date[1], $date[2], $date[0]);
		else
			$error['fromdate'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
	}
	else
		$document['fromdate'] = 0;

	if ($document['todate']) {
		$date = explode('/', $document['todate']);
		if (checkdate($date[1], $date[2], $date[0]))
			$document['todate'] = mktime(23, 59, 59, $date[1], $date[2], $date[0]);
		else
			$error['todate'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
	}
	else
		$document['todate'] = 0;

	if ($document['fromdate'] > $document['todate'] && $document['todate'] != 0)
		$error['todate'] = trans('Start date can\'t be greater than end date!');

	if ($filename = $_FILES['file']['name']) {
		if (is_uploaded_file($_FILES['file']['tmp_name']) && $_FILES['file']['size']) {
			$file = $_FILES['file']['tmp_name'];
			$document['md5sum'] = md5_file($file);
			$document['contenttype'] = $_FILES['file']['type'];
			$document['filename'] = $filename;
		}
		else // upload errors
			switch ($_FILES['file']['error']) {
				case 1:
				case 2: $error['file'] = trans('File is too large.');
					break;
				case 3: $error['file'] = trans('File upload has finished prematurely.');
					break;
				case 4: $error['file'] = trans('Path to file was not specified.');
					break;
				default: $error['file'] = trans('Problem during file upload.');
					break;
			}
	} elseif ($document['templ']) {
		$result = '';
		// read template information
		include(DOC_DIR . '/templates/' . $document['templ'] . '/info.php');
		// set some variables (needed in e.g. plugin)
		$SMARTY->assignByRef('document', $document);
		// call plugin
		if (!empty($engine['plugin']) && file_exists(DOC_DIR . '/templates/' . $engine['name'] . '/' . $engine['plugin'] . '.php'))
			include(DOC_DIR . '/templates/' . $engine['name'] . '/' . $engine['plugin'] . '.php');
		// get plugin content
		$SMARTY->assign('plugin_result', $result);

		// run template engine
		if (file_exists(DOC_DIR . '/templates/' . $engine['engine'] . '/engine.php'))
			require_once(DOC_DIR . '/templates/' . $engine['engine'] . '/engine.php');
		else
			require_once(DOC_DIR . '/templates/default/engine.php');

		if (!empty($output)) {
			$file = DOC_DIR . '/tmp.file';
			$fh = fopen($file, 'w');
			fwrite($fh, $output);
			fclose($fh);

			$document['md5sum'] = md5_file($file);
			$document['contenttype'] = $engine['content_type'];
			$document['filename'] = $engine['output'];
		} else if (empty($error))
			$error['template'] = trans('Problem during file generation!');
	}
	else
		$error['file'] = trans('You must to specify file for upload or select document template!');

	if (!$error) {
		if ($DB->GetOne('SELECT docid FROM documentcontents WHERE md5sum = ?', array($document['md5sum']))
		) {
			$error['file'] = trans('Specified file exists in database!');
		} else {
			$path = DOC_DIR . '/' . substr($document['md5sum'], 0, 2);
			@mkdir($path, 0700);
			$newfile = $path . '/' . $document['md5sum'];

			// If we have a file with specified md5sum, we assume
			// it's here because of some error. We can replace it with
			// the new document file
			if (file_exists($newfile)) {
				@unlink($newfile);
			}
			if (!@rename($file, $newfile))
				$error['file'] = trans('Can\'t save file in "$a" directory!', $path);
		}
	}

	if (!$error) {
		$customer = $LMS->GetCustomer($document['customerid']);
		$time = time();

		$DB->BeginTrans();

		$DB->Execute('INSERT INTO documents (type, number, numberplanid, cdate, 
			customerid, userid, name, address, zip, city, ten, ssn, divisionid, closed)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($document['type'],
				$document['number'],
				$document['numberplanid'],
				$time,
				$document['customerid'],
				$AUTH->id,
				trim($customer['lastname'] . ' ' . $customer['name']),
				$customer['address'] ? $customer['address'] : '',
				$customer['zip'] ? $customer['zip'] : '',
				$customer['city'] ? $customer['city'] : '',
				$customer['ten'] ? $customer['ten'] : '',
				$customer['ssn'] ? $customer['ssn'] : '',
				$customer['divisionid'],
				isset($document['closed']) ? 1 : 0
		));

		$docid = $DB->GetLastInsertID('documents');

		$DB->Execute('INSERT INTO documentcontents (docid, title, fromdate, todate, filename, contenttype, md5sum, description)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?)', array($docid,
				$document['title'],
				$document['fromdate'],
				$document['todate'],
				$document['filename'],
				$document['contenttype'],
				$document['md5sum'],
				$document['description']
		));

		// template post-action
		if (!empty($engine['post-action']) && file_exists(DOC_DIR . '/templates/' . $engine['name'] . '/' . $engine['post-action'] . '.php'))
			include(DOC_DIR . '/templates/' . $engine['name'] . '/' . $engine['post-action'] . '.php');

		$DB->CommitTrans();

		if (!isset($document['reuse'])) {
			if (isset($_GET['print']))
				$SESSION->save('documentprint', $docid);

			$SESSION->redirect('?m=documentlist&c=' . $document['customerid']);
		}

		unset($document['title']);
		unset($document['number']);
		unset($document['description']);
		unset($document['fromdate']);
		unset($document['todate']);
	}
	else {
		$document['fromdate'] = $oldfromdate;
		$document['todate'] = $oldtodate;
		if (isset($autonumber))
			$document['number'] = '';
	}
}
else {
	$document['customerid'] = isset($_GET['cid']) ? $_GET['cid'] : '';
	$document['type'] = isset($_GET['type']) ? $_GET['type'] : '';
}

$rights = $DB->GetCol('SELECT doctype FROM docrights WHERE userid = ? AND (rights & 2) = 2', array($AUTH->id));

if (!$rights) {
	$SMARTY->display('noaccess.html');
	die;
}

$allnumberplans = array();
$numberplans = array();

if ($templist = $LMS->GetNumberPlans())
	foreach ($templist as $item)
		if ($item['doctype'] < 0)
			$allnumberplans[] = $item;

if (isset($document['numberplanid'])) {
	foreach ($allnumberplans as $plan)
		if ($plan['doctype'] == $document['numberplanid'])
			$numberplans[] = $plan;
}

if ($dirs = getdir(DOC_DIR . '/templates', '^[a-z0-9_-]+$'))
	foreach ($dirs as $dir) {
		$infofile = DOC_DIR . '/templates/' . $dir . '/info.php';
		if (file_exists($infofile)) {
			unset($engine);
			include($infofile);
			$docengines[$dir] = $engine;
		}
	}

if ($docengines)
	ksort($docengines);

$layout['pagetitle'] = trans('New Document');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if (!isset($CONFIG['phpui']['big_networks']) || !chkconfig($CONFIG['phpui']['big_networks'])) {
	$SMARTY->assign('customers', $LMS->GetCustomerNames());
}

$SMARTY->assign('error', $error);
$SMARTY->assign('numberplans', $numberplans);
$SMARTY->assign('docrights', $rights);
$SMARTY->assign('allnumberplans', $allnumberplans);
$SMARTY->assign('docengines', $docengines);
$SMARTY->assign('document', $document);

$SMARTY->display('documentadd.html');
?>
