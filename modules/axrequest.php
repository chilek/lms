<?php

$action = isset($_POST['action']) ? strtolower($_POST['action']) : NULL;
$id = isset($_POST['id']) ? intval($_POST['id']) : NULL;
$idown = isset($_POST['idown']) ? intval($_POST['idown']) : NULL;

if ( is_null($action) ) { die('null'); }
$result = NULL;

switch ($action)
{

    case 'nodeaccess'	: // on / off access nodes
			    if (isset($_POST['idown'])) {
				if ($DB->Execute('UPDATE nodes SET access = ? WHERE ownerid = ? ;',array(intval($_POST['access']),$idown)))
				    $result = TRUE;
				else 
				    $result = FALSE;
			    }
			    if (isset($_POST['id'])) {
				if ($DB->Execute('UPDATE nodes SET access = ? WHERE id = ? ;',array(intval($_POST['access']),$id)))
				    $result = TRUE;
				else 
				    $result = FALSE;
			    }
			break;

    case 'warning'	: // on/off messages
			    if (isset($_POST['idown'])) {
				if ($DB->Execute('UPDATE nodes SET warning = ? WHERE ownerid = ? ;',array(intval($_POST['warning']),$idown)))
				    $result = TRUE;
				else 
				    $result = FALSE;
			    }
			    if (isset($_POST['id'])) {
				if ($DB->Execute('UPDATE nodes SET warning = ? WHERE id = ? ;',array(intval($_POST['warning']),$id)))
				    $result = TRUE;
				else 
				    $result = FALSE;
			    }
			break;

    case 'balanceok'	: 
			    $balance = $LMS->GetCustomerBalance($idown);
			    if($balance<0)
			    {
				$DB->BeginTrans();
				$DB->Execute('INSERT INTO cash (time, type, userid, value, customerid, comment) VALUES (?NOW?, 1, ?, ?, ?, ?)', 
					    array($AUTH->id,str_replace(',','.', $balance*-1),$idown,trans('Accounted')));
				$DB->Execute('UPDATE documents SET closed = 1 WHERE customerid = ? AND type IN (?, ?) AND closed = 0',
					    array($idown, DOC_INVOICE, DOC_CNOTE));
				$DB->CommitTrans();
				$result = TRUE;
			    }
			break;

} // end switch

if (is_null($result)) die('null');
elseif ($result===true) die('true');
elseif ($result===false) die('false');
else die('null');

?>