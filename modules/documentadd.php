<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

check_file_uploads();

$SMARTY->setDefaultResourceType('file');

include(MODULES_DIR . DIRECTORY_SEPARATOR . 'document.inc.php');

if (isset($_POST['document'])) {
    $document = $_POST['document'];

    $oldfromdate = $document['fromdate'];
    $oldtodate = $document['todate'];

    $document['customerid'] = isset($_POST['customerid']) ? intval($_POST['customerid']) : intval($_POST['customer']);

    if (!$LMS->CustomerExists(intval($document['customerid']))) {
        $error['customer'] = trans('Customer not selected!');
        $error['customerid'] = trans('Customer not selected!');
    }

    if (!$document['type']) {
        $error['type'] = trans('Document type is required!');
    }

    if (!$document['title']) {
        $error['title'] = trans('Document title is required!');
    }

    // check if selected customer can use selected numberplan
    if ($document['numberplanid'] && $document['customerid']
            && !$DB->GetOne('SELECT 1 FROM numberplanassignments
	                WHERE planid = ? AND divisionid IN (SELECT divisionid
				FROM customers WHERE id = ?)', array($document['numberplanid'], $document['customerid']))) {
        $error['number'] = trans('Selected numbering plan doesn\'t match customer\'s division!');
    } elseif ($document['number'] == '') {
    // check number
        $tmp = $LMS->GetNewDocumentNumber(array(
            'doctype' => $document['type'],
            'planid' => $document['numberplanid'],
            'customerid' => $document['customerid'],
        ));
        $document['number'] = $tmp ? $tmp : 0;
        $autonumber = true;
    } elseif (!preg_match('/^[0-9]+$/', $document['number'])) {
        $error['number'] = trans('Document number must be an integer!');
    } elseif ($LMS->DocumentExists(array(
            'number' => $document['number'],
            'doctype' => $document['type'],
            'planid' => $document['numberplanid'],
            'customerid' => $document['customerid'],
        ))) {
        $error['number'] = trans('Document with specified number exists!');
    }

    if ($document['fromdate']) {
        $date = explode('/', $document['fromdate']);
        if (checkdate($date[1], $date[2], $date[0])) {
            $document['fromdate'] = mktime(0, 0, 0, $date[1], $date[2], $date[0]);
        } else {
            $error['fromdate'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
        }
    } else {
        $document['fromdate'] = 0;
    }

    if ($document['todate']) {
        $date = explode('/', $document['todate']);
        if (checkdate($date[1], $date[2], $date[0])) {
            $document['todate'] = mktime(23, 59, 59, $date[1], $date[2], $date[0]);
        } else {
            $error['todate'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
        }
    } else {
        $document['todate'] = 0;
    }

    if ($document['fromdate'] > $document['todate'] && $document['todate'] != 0) {
        $error['todate'] = trans('Start date can\'t be greater than end date!');
    }

    // validate tariff selection list when promotions are active only
    if (isset($document['assignment']) && !empty($document['assignment']['schemaid'])) {
        // validate selected promotion schema properties
        $selected_assignment = $document['assignment'];
        $selected_assignment['datefrom'] = $oldfromdate;
        $selected_assignment['dateto'] = $oldtodate;

        $result = $LMS->ValidateAssignment($selected_assignment);
        extract($result);

        if (!$LMS->CheckSchemaModifiedValues($a)) {
            $error['promotion-select'] = trans('Illegal promotion schema period value modification!');
        }
    } else {
        $selected_assignment = null;
    }

    $files = array();

    if (!isset($_GET['ajax'])) {
        if ($document['reference']) {
            $document['reference'] = $DB->GetRow('SELECT id, type, fullnumber, cdate FROM documents
				WHERE id = ?', array($document['reference']));
        }
        $SMARTY->assignByRef('document', $document);

        if ($document['templ']) {
            foreach ($documents_dirs as $doc) {
                if (file_exists($doc . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $document['templ'])) {
                    $doc_dir = $doc;
                    $template_dir = $doc . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $document['templ'];
                    break;
                }
            }

            $result = '';
            $script_result = '';

            // read template information
            include($template_dir . DIRECTORY_SEPARATOR . 'info.php');

            // call plugin
            if (!empty($engine['plugin'])) {
                if (file_exists($doc_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
                    . $engine['name'] . DIRECTORY_SEPARATOR . $engine['plugin'] . '.php')) {
                    include($doc_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $engine['name']
                        . DIRECTORY_SEPARATOR . $engine['plugin'] . '.php');
                }
                if (file_exists($doc_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
                    . $engine['name'] . DIRECTORY_SEPARATOR . $engine['plugin'] . '.js')) {
                    $script_result = '<script src="' . $_SERVER['REQUEST_URI'] . '&template=' . $engine['name'] . '"></script>';
                }
            }
            // get plugin content
            $SMARTY->assign('plugin_result', $result);
            $SMARTY->assign('script_result', $script_result);
            $SMARTY->assign('attachment_result', GenerateAttachmentHTML(
                $template_dir,
                $engine,
                isset($document['attachments']) ? $document['attachments'] : array()
            ));

            // run template engine
            if (file_exists($doc_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
                . $engine['engine'] . DIRECTORY_SEPARATOR . 'engine.php')) {
                require_once($doc_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
                    . $engine['engine'] . DIRECTORY_SEPARATOR . 'engine.php');
            } else {
                require_once(DOC_DIR . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
                    . 'default' . DIRECTORY_SEPARATOR . 'engine.php');
            }

            if (!empty($output)) {
                $file = DOC_DIR . DIRECTORY_SEPARATOR . 'tmp.file';
                $fh = fopen($file, 'w');
                fwrite($fh, $output);
                fclose($fh);

                $files[] = array(
                    'md5sum' => md5_file($file),
                    'type' => $engine['content_type'],
                    'name' => $engine['output'],
                    'tmpname' => $file,
                    'main' => true,
                );
            } else if (empty($error)) {
                $error['templ'] = trans('Problem during file generation!');
            }
        }
    }

    $result = handle_file_uploads('attachments', $error);
    extract($result);
    $SMARTY->assign('fileupload', $fileupload);

    if (!empty($attachments)) {
        foreach ($attachments as $attachment) {
            $attachment['tmpname'] = $tmppath . DIRECTORY_SEPARATOR . $attachment['name'];
            $attachment['md5sum'] = md5_file($attachment['tmpname']);
            $attachment['main'] = false;
            $files[] = $attachment;
        }
    }
    if (isset($document['attachments']) && !empty($document['attachments'])) {
        foreach ($document['attachments'] as $attachment => $value) {
            $filename = $engine['attachments'][$attachment];
            if ($filename[0] != DIRECTORY_SEPARATOR) {
                $filename = $template_dir . DIRECTORY_SEPARATOR . $filename;
            }
            $files[] = array(
            'tmpname' => null,
            'name' => $filename,
            'type' => mime_content_type($filename),
            'md5sum' => md5_file($filename),
            'main' => false,
            );
        }
    }

    if (empty($files) && empty($document['templ'])) {
        $error['files'] = trans('You must to specify file for upload or select document template!');
    }

    if (!$error) {
        $error = $LMS->AddDocumentFileAttachments($files);
        if (empty($error) && !empty($tmppath)) {
            rrmdir($tmppath);
        }
    }

    if (!$error) {
        $customer = $LMS->GetCustomer($document['customerid']);
        $time = time();

        $DB->BeginTrans();

        $division = $LMS->GetDivision($customer['divisionid']);

        $fullnumber = docnumber(array(
            'number' => $document['number'],
            'template' => $DB->GetOne('SELECT template FROM numberplans WHERE id = ?', array($document['numberplanid'])),
            'cdate' => $time,
            'customerid' => $document['customerid'],
        ));

        // if document will not be closed now we should store commit flags in documents table
        // to allow restore commit flags later during document close process
        if (isset($document['closed']) || !isset($selected_assignment)) {
            $commit_flags = 0;
        } else {
            $commit_flags = $selected_assignment['existing_assignments']['operation'];
            if ($commit_flags && isset($selected_assignment['existing_assignments']['reference_document_limit'])) {
                $commit_flags += 16;
            }
        }

        $DB->Execute(
            'INSERT INTO documents (type, number, numberplanid, cdate, sdate, cuserid,
			customerid, userid, name, address, zip, city, ten, ssn, divisionid, 
			div_name, div_shortname, div_address, div_city, div_zip, div_countryid, div_ten, div_regon,
			div_account, div_inv_header, div_inv_footer, div_inv_author, div_inv_cplace, closed, fullnumber,
			reference, template, commitflags)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            array($document['type'],
                $document['number'],
                empty($document['numberplanid']) ? null : $document['numberplanid'],
                $time,
                isset($document['closed']) ? $time : 0,
                isset($document['closed']) ? Auth::GetCurrentUser() : null,
                $document['customerid'],
                Auth::GetCurrentUser(),
                trim($customer['lastname'] . ' ' . $customer['name']),
                $customer['address'] ? $customer['address'] : '',
                $customer['zip'] ? $customer['zip'] : '',
                $customer['city'] ? $customer['city'] : '',
                $customer['ten'] ? $customer['ten'] : '',
                $customer['ssn'] ? $customer['ssn'] : '',
                $customer['divisionid'] ? $customer['divisionid'] : null,
                ($division['name'] ? $division['name'] : ''),
                ($division['shortname'] ? $division['shortname'] : ''),
                ($division['address'] ? $division['address'] : ''),
                ($division['city'] ? $division['city'] : ''),
                ($division['zip'] ? $division['zip'] : ''),
                ($division['countryid'] ? $division['countryid'] : null),
                ($division['ten'] ? $division['ten'] : ''),
                ($division['regon'] ? $division['regon'] : ''),
                ($division['account'] ? $division['account'] : ''),
                ($division['inv_header'] ? $division['inv_header'] : ''),
                ($division['inv_footer'] ? $division['inv_footer'] : ''),
                ($division['inv_author'] ? $division['inv_author'] : ''),
                ($division['inv_cplace'] ? $division['inv_cplace'] : ''),
                isset($document['closed']) ? 1 : 0,
                $fullnumber,
                !isset($document['reference']) || empty($document['reference']) ? null : $document['reference']['id'],
                empty($document['templ']) ? null : $document['templ'],
                $commit_flags,
            )
        );

        $docid = $DB->GetLastInsertID('documents');

        $DB->Execute('INSERT INTO documentcontents (docid, title, fromdate, todate, description)
			VALUES (?, ?, ?, ?, ?)', array($docid,
                $document['title'],
                $document['fromdate'],
                $document['todate'],
                $document['description']
        ));

        foreach ($files as $file) {
            $DB->Execute('INSERT INTO documentattachments (docid, filename, contenttype, md5sum, main)
				VALUES (?, ?, ?, ?, ?)', array($docid,
                    basename($file['name']),
                    $file['type'],
                    $file['md5sum'],
                    $file['main'] ? 1 : 0,
            ));
        }

        // template post-action
        if (!empty($engine['post-action']) && file_exists($doc_dir . DIRECTORY_SEPARATOR . 'templates'
            . DIRECTORY_SEPARATOR . $engine['name'] . DIRECTORY_SEPARATOR . $engine['post-action'] . '.php')) {
            include($doc_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $engine['name']
                . DIRECTORY_SEPARATOR . $engine['post-action'] . '.php');
        }

        if (isset($selected_assignment)) {
            $selected_assignment['docid'] = $docid;
            $selected_assignment['customerid'] = $document['customerid'];
            $selected_assignment['reference'] = isset($document['reference']['id']) ? $document['reference']['id'] : null;
            if (empty($from)) {
                list ($year, $month, $day) = explode('/', date('Y/m/d'));
                $selected_assignment['datefrom'] = mktime(0, 0, 0, $month, $day, $year);
            } else {
                $selected_assignment['datefrom'] = $from;
            }
            $selected_assignment['dateto'] = $to;

            if (isset($document['closed'])) {
                $LMS->UpdateExistingAssignments($selected_assignment);
            }

            if ($selected_assignment['schemaid']) {
                $schemaid = $selected_assignment['schemaid'];

                // create assignments basing on selected promotion schema
                $selected_assignment['period'] = $period;
                $selected_assignment['at'] = $at;
                $selected_assignment['commited'] = isset($document['closed']) ? 1 : 0;

                if (is_array($selected_assignment['stariffid'][$schemaid])) {
                    $modifiedvalues = $selected_assignment['values'][$schemaid];
                    $counts = $selected_assignment['counts'][$schemaid];
                    $copy_a = $selected_assignment;
                    $snodes = $selected_assignment['snodes'][$schemaid];
                    $sphones = $selected_assignment['sphones'][$schemaid];

                    foreach ($selected_assignment['stariffid'][$schemaid] as $label => $v) {
                        if (!$v) {
                            continue;
                        }

                        $copy_a['promotiontariffid'] = $v;
                        $copy_a['modifiedvalues'] = isset($modifiedvalues[$label][$v]) ? $modifiedvalues[$label][$v] : array();
                        $copy_a['count'] = $counts[$label];
                        $copy_a['nodes'] = $snodes[$label];
                        $copy_a['phones'] = $sphones[$label];
                        $tariffid = $LMS->AddAssignment($copy_a);
                    }
                }
            }
        }

        $DB->CommitTrans();

        if (!isset($document['reuse'])) {
            if (isset($_GET['print'])) {
                $SESSION->save('documentprint', $docid);
            }

            $SESSION->redirect('?m=documentlist&c=' . $document['customerid']);
        }

        unset($document['title']);
        unset($document['number']);
        unset($document['description']);
        unset($document['fromdate']);
        unset($document['todate']);
    } else {
        $document['fromdate'] = $oldfromdate;
        $document['todate'] = $oldtodate;
        if (isset($autonumber)) {
            $document['number'] = '';
        }
    }
} else {
    $document['customerid'] = isset($_GET['cid']) ? $_GET['cid'] : '';
    $document['type'] = isset($_GET['type']) ? $_GET['type'] : '';

    $default_assignment_invoice = ConfigHelper::getConfig('phpui.default_assignment_invoice');
    if (!empty($default_assignment_invoice)) {
        if (preg_match('/^[0-9]+$/', $default_assignment_invoice)) {
            $document['assignment']['invoice'] = $default_assignment_invoice;
        } elseif (ConfigHelper::checkValue($default_assignment_invoice)) {
            $document['assignment']['invoice'] = DOC_INVOICE;
        }
    }
    $default_assignment_settlement = ConfigHelper::getConfig('phpui.default_assignment_settlement');
    if (!empty($default_assignment_settlement)) {
        if (preg_match('/^[0-9]+$/', $default_assignment_settlement)) {
            $document['assignment']['settlement'] = $default_assignment_settlement;
        } elseif (ConfigHelper::checkValue($default_assignment_settlement)) {
            $document['assignment']['settlement'] = 1;
        }
    }
    $default_assignment_period = ConfigHelper::getConfig('phpui.default_assignment_period');
    if (!empty($default_assignment_period)) {
        $document['assignment']['period'] = $default_assignment_period;
    }
    $default_assignment_at = ConfigHelper::getConfig('phpui.default_assignment_at');
    if (!empty($default_assignment_at)) {
        $document['assignment']['at'] = $default_assignment_at;
    }
}

$SMARTY->setDefaultResourceType('extendsall');

$rights = $DB->GetCol(
    'SELECT doctype FROM docrights WHERE userid = ? AND (rights & ?) > 0',
    array(Auth::GetCurrentUser(), DOCRIGHT_CREATE)
);

if (!$rights) {
    $SMARTY->display('noaccess.html');
    die;
}

if (isset($document['type'])) {
    $customerid = isset($document['customerid']) ? $document['customerid'] : null;
    $numberplans = GetDocumentNumberPlans($document['type'], $customerid);
} else {
    $numberplans = array();
}
$SMARTY->assign('numberplans', $numberplans);

$docengines = GetDocumentTemplates($rights, isset($document['type']) ? $document['type'] : null);

$references = empty($document['customerid']) ? null : $LMS->GetDocuments($document['customerid']);
$SMARTY->assign('references', $references);

$layout['pagetitle'] = trans('New Document');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if (!ConfigHelper::checkConfig('phpui.big_networks')) {
    $SMARTY->assign('customers', $LMS->GetCustomerNames());
}

// +++ promotion support
if (isset($document['customerid'])) {
    $promotions = $LMS->GetPromotions();
    $numberplans = $LMS->GetNumberPlans(array(
        'doctype' => DOC_INVOICE,
        'cdate' => null,
        'division' => empty($document['customerid']) ? null : $LMS->GetCustomerDivision($document['customerid']),
        'next' => false,
    ));
} else {
    $promotions = $numberplans = null;
}

$SMARTY->assign('promotions', $promotions);
$SMARTY->assign('tariffs', $LMS->GetTariffs());
$SMARTY->assign('numberplanlist', $numberplans);
// --- promotion support

$SMARTY->assign('error', $error);
$SMARTY->assign('docrights', $rights);
$SMARTY->assign('docengines', $docengines);
$SMARTY->assign('document', $document);
$SMARTY->display('document/documentadd.html');
