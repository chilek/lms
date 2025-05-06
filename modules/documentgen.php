<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

$layout['pagetitle'] = trans('Documents Generator');

if (isset($_POST['document'])) {
    $document = $_POST['document'];

    if (!$document['type']) {
        $error['type'] = trans('Document type is required!');
    }

    if (!$document['title']) {
        $error['title'] = trans('Document title is required!');
    }

    if ($document['number'] == '') {
        $tmp = $LMS->GetNewDocumentNumber(array(
            'doctype' => $document['type'],
            'planid' => $document['numberplanid'],
        ));
        $document['number'] = $tmp ?: 0;
    } elseif (!preg_match('/^[0-9]+$/', $document['number'])) {
        $error['number'] = trans('Document number must be an integer!');
    } elseif ($LMS->DocumentExists(array(
            'number' => $document['number'],
            'doctype' => $document['type'],
            'planid' => $document['numberplanid'],
        ))) {
        $error['number'] = trans('Document with specified number exists!');
    }

    $allow_past_date = ConfigHelper::checkConfig('documents.allow_past_date', true);
    if (!$allow_past_date) {
        $today = strtotime('today');
    }

    if (empty($document['fromdate'])) {
        $document['fromdate'] = 0;
    } elseif (!preg_match('/^[0-9]+$/', $document['fromdate'])) {
        $error['fromdate'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
    } elseif (!$allow_past_date && $document['fromdate'] < $today) {
        die('From date can not be earlier than current date!');
    }

    if (empty($document['todate'])) {
        $document['todate'] = 0;
    } elseif (!preg_match('/^[0-9]+$/', $document['todate'])) {
        $error['todate'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
    } elseif (!$allow_past_date && $document['todate'] < $today) {
        die('To date can not be earlier than current date!');
    } else {
        $document['todate'] = strtotime('tomorrow', $document['todate']) - 1;
    }

    if (!empty($document['todate']) && $document['fromdate'] > $document['todate']) {
        $error['todate'] = trans('Start date can\'t be greater than end date!');
    }

    $state = isset($_POST['filter']) ? $_POST['filter'] : null;
    $network = $_POST['network'];

    if (isset($_POST['customergroup'])) {
        if (is_array($_POST['customergroup'])) {
            $customergroup = $_POST['customergroup'];
        } else {
            $customergroup = array($_POST['customergroup']);
        }
    } else {
        $customergroup = array();
    }
    $customergroupsqlskey = 'OR';

    $search = array();
    if (isset($document['ctype']) && strlen($document['ctype'])) {
        $search['type'] = $document['ctype'];
    }

    switch ($state) {
        case CSTATUS_DISCONNECTED:
        case 51:
        case 52:
        case CSTATUS_CONNECTED:
        case 0:
            $customerlist = $LMS->GetCustomerList(compact('state', 'network', 'customergroup', 'customergroupsqlskey', 'search'));
            break;
        case CSTATUS_INTERESTED:
        case CSTATUS_WAITING:
            $customerlist = $LMS->GetCustomerList(compact('state', 'search'));
            break;
        case -1:
            if ($customerlist = $LMS->GetCustomerList(compact('customergroup', 'customergroupsqlskey', 'search'))) {
                foreach ($customerlist as $idx => $row) {
                    if (!$row['account']) {
                        $ncustomerlist[] = $row;
                    }
                }

                $customerlist = $ncustomerlist;
            }
            break;
    }

    if (!isset($customerlist) || $customerlist['total'] == 0) {
        $error['customer'] = trans('Customers list is empty!');
    }

    $SMARTY->assign(array(
        'filter' => $state,
        'network' => $network,
        'customergroup' => $customergroup,
    ));

    $result = handle_file_uploads('attachments', $error);
    extract($result);
    $SMARTY->assign('fileupload', $fileupload);

    if ($document['templ']) {
        foreach ($documents_dirs as $doc) {
            if (is_readable($doc . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $document['templ'] . DIRECTORY_SEPARATOR . 'info.php')) {
                $doc_dir = $doc;
                $template_dir = $doc . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $document['templ'];
                break;
            }
        }
    }

    $globalfiles = array();
    if (!empty($attachments)) {
        foreach ($attachments as $attachment) {
            $attachment['tmpname'] = $tmppath . DIRECTORY_SEPARATOR . $attachment['name'];
            $attachment['md5sum'] = md5_file($attachment['tmpname']);
            $attachment['attachmenttype'] = 0;
            $globalfiles[] = $attachment;
        }
    }
    if (!empty($document['attachments'])) {
        foreach ($document['attachments'] as $attachment => $value) {
            if (isset($engine['attachments'][$attachment])) {
                $filename = $engine['attachments'][$attachment];
            } else {
                foreach ($engine['attachments'] as $idx => $file) {
                    if ($file['label'] == $attachment) {
                        break;
                    }
                }
                $filename = $engine['attachments'][$idx]['name'];
            }
            if ($filename[0] != DIRECTORY_SEPARATOR) {
                $filename = $template_dir . DIRECTORY_SEPARATOR . $filename;
            }
            $globalfiles[] = array(
                'tmpname' => null,
                'name' => $filename,
                'type' => mime_content_type($filename),
                'md5sum' => md5_file($filename),
                'attachmenttype' => 0,
            );
        }
    }

    if (empty($globalfiles) && empty($document['templ'])) {
        $error['files'] = trans('You must to specify file for upload or select document template!');
    }

    if (!$error) {
        foreach ($globalfiles as &$file) {
            $file['path'] = DOC_DIR . DIRECTORY_SEPARATOR . substr($file['md5sum'], 0, 2);
            $file['newfile'] = $file['path'] . DIRECTORY_SEPARATOR . $file['md5sum'];

            // If we have a file with specified md5sum, we assume
            // it's here because of some error. We can replace it with
            // the new document file
            // why? document attachment can be shared between different documents.
            // we should rather use the other message digest in such case!
            $filename = empty($file['tmpname']) ? $file['name'] : $file['tmpname'];
            if ($LMS->DocumentAttachmentExists($file['md5sum'])
                && (filesize($file['newfile']) != filesize($filename)
                    || hash_file('sha256', $file['newfile']) != hash_file('sha256', $filename))) {
                $error['files'] = trans('Specified file exists in database!');
                break;
            }
        }
        unset($file);
    }

    if (!$error) {
        $header = '';
        $time = time();
        $gencount = 0;
        $genresult = '<H1>' . $layout['pagetitle'] . '</H1>';

        $numtemplate = $DB->GetOne('SELECT template FROM numberplans WHERE id = ?', array($document['numberplanid']));
        // number template has got %C special symbol which means customer id substitution
        $customernumtemplate = preg_match('/%[0-9]*C/', $numtemplate);

        if ($document['templ']) {
            // read template information
            include($template_dir . DIRECTORY_SEPARATOR . 'info.php');

            if (isset($engine['vhosts']) && isset($engine['vhosts'][$_SERVER['HTTP_HOST']])) {
                $engine = array_merge($engine, $engine['vhosts'][$_SERVER['HTTP_HOST']]);
            }
        }

        foreach ($customerlist as $idx => $gencust) {
            if (!preg_match('/^[0-9]+$/', $idx) || !is_array($gencust) || empty($gencust)) {
                continue;
            }

            $error = null;
            $document['customerid'] = $gencust['id'];
            $gencount++;
            $output = null; // delete output
            $genresult .= $gencount . '. ' . $gencust['customername'] . ': ';

            $files = array();
            unset($docfile);

            if ($customernumtemplate) {
                $document['number'] = $LMS->GetNewDocumentNumber(array(
                    'doctype' => $document['type'],
                    'planid' => $document['numberplanid'],
                    'customerid' => $document['customerid'],
                ));
            }

            $fullnumber = docnumber(array(
                'number' => $document['number'],
                'template' => $numtemplate,
                'cdate' => $time,
                'customerid' => $document['customerid'],
            ));

            $document['template'] = $numtemplate;
            $document['nr'] = $document['fullnumber'] = $fullnumber;

            $customer = $LMS->GetCustomer($document['customerid']);
            $division = $LMS->GetDivision($customer['divisionid']);

            if ($document['templ']) {
                $SMARTY->assign(array(
                    'customer' => $customer,
                    'customerinfo' => $customer,
                    'division' => $division,
                    'document' => $document,
                    'engine' => $engine,
                ));

                ConfigHelper::setFilter($customer['divisionid'], Auth::GetCurrentUser());

                $company_logo = ConfigHelper::getConfig('documents.company_logo', '', true);
                if (strlen($company_logo) && strpos($company_logo, DIRECTORY_SEPARATOR) !== 0) {
                    $company_logo = SYS_DIR . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . $company_logo;
                }

                $company_logo_width = intval(ConfigHelper::getConfig('documents.company_logo_width', '150'));

                $project_logo = ConfigHelper::getConfig('documents.project_logo', '', true);
                if (strlen($project_logo) && strpos($project_logo, DIRECTORY_SEPARATOR) !== 0) {
                    $project_logo = SYS_DIR . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . $project_logo;
                }

                $date_format = ConfigHelper::getConfig('documents.date_format', 'd.m.Y');

                $default_header = ConfigHelper::getConfig('documents.default_header', '', true);
                if (strlen($default_header) && is_readable($default_header)) {
                    $header = $SMARTY->fetch($default_header);
                } else {
                    $header = '';
                }

                $default_footer = ConfigHelper::getConfig('documents.default_footer', '', true);
                if (strlen($default_footer) && is_readable($default_footer)) {
                    $footer = $SMARTY->fetch($default_footer);
                } else {
                    $footer = '';
                }

                $SMARTY->assign(compact('company_logo', 'company_logo_width', 'project_logo', 'date_format', 'header', 'footer'));

                // run template engine
                if (is_readable($doc_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
                    . $engine['engine'] . DIRECTORY_SEPARATOR . 'engine.php')) {
                    $SMARTY->AddTemplateDir(
                        array(
                            'documentgen' => $doc_dir . DIRECTORY_SEPARATOR . 'templates'
                                . DIRECTORY_SEPARATOR . $engine['name']
                        )
                    );
                    include($doc_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
                        . $engine['engine'] . DIRECTORY_SEPARATOR . 'engine.php');
                } else {
                    include(DOC_DIR . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'default'
                         . DIRECTORY_SEPARATOR . 'engine.php');
                    $SMARTY->AddTemplateDir(
                        array(
                            'documentgen' => DOC_DIR . DIRECTORY_SEPARATOR . 'templates'
                                . DIRECTORY_SEPARATOR . 'default'
                        )
                    );
                }

                if ($output) {
                    $file = tempnam(DOC_DIR, 'tmp.file');
                    $fh = fopen($file, 'w');
                    fwrite($fh, $output);
                    fclose($fh);

                    $md5sum = md5_file($file);
                    $path = DOC_DIR . DIRECTORY_SEPARATOR . substr($md5sum, 0, 2);
                    $docfile = array(
                        'md5sum' => $md5sum,
                        'type' => $engine['content_type'],
                        'name' => $engine['output'],
                        'tmpname' => $file,
                        'attachmenttype' => 1,
                        'path' => $path,
                        'newfile' => $path . DIRECTORY_SEPARATOR . $md5sum,
                    );
                    $files[] = $docfile;
                } else {
                    $error = trans('Problem during file generation!');
                }
            }

            if (!$error) {
                $files = array_merge($files, $globalfiles);
                foreach ($files as $file) {
                    @mkdir($file['path'], 0700);
                    if (empty($file['tmpname'])) {
                        if (!@copy($file['name'], $file['newfile'])) {
                            $error['files'] = trans('Can\'t save file in "$a" directory!', $file['path']);
                            break;
                        }
                    } elseif (!file_exists($file['newfile']) && !@rename($file['tmpname'], $file['newfile'])) {
                        $error = trans('Can\'t save file in "$a" directory!', $file['path']);
                        break;
                    }
                }
            }

            if ($error) {
                $genresult .= '<span class="alert">' . $error . '</span><br>';
                continue;
            }

            $DB->BeginTrans();

            $DB->Execute('INSERT INTO documents (type, number, numberplanid, cdate, customerid, userid, divisionid, name, address, zip, city, ten, ssn, closed,
					div_name, div_shortname, div_address, div_city, div_zip, div_countryid, div_ten, div_regon,
					div_account, div_inv_header, div_inv_footer, div_inv_author, div_inv_cplace, fullnumber, template)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($document['type'],
                    $document['number'],
                    empty($document['numberplanid']) ? null : $document['numberplanid'],
                    $time,
                    $document['customerid'],
                    Auth::GetCurrentUser(),
                    $gencust['divisionid'] ?: null,
                    $gencust['customername'],
                    $gencust['address'] ?: '',
                    $gencust['zip'] ?: '',
                    $gencust['city'] ?: '',
                    $gencust['ten'] ?: '',
                    $gencust['ssn'] ?: '',
                    !empty($document['closed']) ? 1 : 0,
                    ($division['name'] ?: ''),
                    ($division['shortname'] ?: ''),
                    ($division['address'] ?: ''),
                    ($division['city'] ?: ''),
                    ($division['zip'] ?: ''),
                    ($division['countryid'] ?: null),
                    ($division['ten'] ?: ''),
                    ($division['regon'] ?: ''),
                    ($division['account'] ?: ''),
                    ($division['inv_header'] ?: ''),
                    ($division['inv_footer'] ?: ''),
                    ($division['inv_author'] ?: ''),
                    ($division['inv_cplace'] ?: ''),
                    $fullnumber,
                    empty($document['templ']) ? null : $document['templ'],
            ));

            $docid = $DB->GetLastInsertID('documents');

            $DB->Execute('INSERT INTO documentcontents (docid, title, fromdate, todate, description)
				VALUES (?, ?, ?, ?, ?)', array($docid,
                    $document['title'],
                    $document['fromdate'],
                    $document['todate'],
                    $document['description']
            ));

            foreach ($files as $file) {
                $DB->Execute('INSERT INTO documentattachments (docid, filename, contenttype, md5sum, type)
					VALUES (?, ?, ?, ?, ?)', array($docid,
                        basename($file['name']),
                        $file['type'],
                        $file['md5sum'],
                        $file['attachmenttype'],
                ));
            }

            $DB->CommitTrans();

            $genresult .= $fullnumber . '.<br>';
            if (!$customernumtemplate) {
                $document['number']++;
            }

            if (isset($_GET['print']) && isset($docfile) && $docfile['contenttype'] == 'text/html') {
                print $output;
                print '<DIV style="page-break-after: always;"></DIV>';
                flush();
            }
        }

        if (!isset($_GET['print'])) {
            $SMARTY->display('header.html');
            print $genresult;
            $SMARTY->display('footer.html');
        }

        // deletes uploaded files
        if (!empty($attachments) && !empty($tmppath)) {
            rrmdir($tmppath);
        }

        die;
    } else {
        if ($document['templ']) {
            foreach ($documents_dirs as $doc) {
                if (is_readable($doc . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $document['templ'] . DIRECTORY_SEPARATOR . 'info.php')) {
                    $doc_dir = $doc;
                    $template_dir = $doc . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $document['templ'];
                    break;
                }
            }

            $result = '';
            $script_result = '';

            // read template information
            include($template_dir . DIRECTORY_SEPARATOR . 'info.php');
            if (isset($engine['vhosts']) && isset($engine['vhosts'][$_SERVER['HTTP_HOST']])) {
                $engine = array_merge($engine, $engine['vhosts'][$_SERVER['HTTP_HOST']]);
            }

            // set some variables
            $SMARTY->assign('document', $document);

            // call plugin
            if (!empty($engine['plugin'])) {
                if (is_readable($doc_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
                    . $engine['name'] . DIRECTORY_SEPARATOR . $engine['plugin'] . '.php')) {
                    include($doc_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $engine['name']
                        . DIRECTORY_SEPARATOR . $engine['plugin'] . '.php');
                }
                if (is_readable($doc_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
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
                $document['attachments'] ?? array()
            ));
        }
    }
} else {
    $document['type'] = '';

    $default_type = ConfigHelper::getConfig('documents.default_type', '', true);
    if (strlen($default_type)) {
        $doctype_aliases_flipped = array_flip($DOCTYPE_ALIASES);
        if (ctype_digit($default_type)) {
            if (isset($DOCTYPE_ALIASES[$default_type])) {
                $document['type'] = $default_type;
            }
        } else {
            if (isset($doctype_aliases_flipped[$default_type])) {
                $document['type'] = $doctype_aliases_flipped[$default_type];
            }
        }
    }
}

$SMARTY->setDefaultResourceType('extendsall');

$SESSION->add_history_entry();

$rights = $DB->GetCol('SELECT doctype FROM docrights WHERE userid = ? AND (rights & 2) = 2', array(Auth::GetCurrentUser()));

if (!$rights) {
    access_denied();
}

if (!isset($document['numberplanid'])) {
    $document['numberplanid'] = $DB->GetOne('SELECT id FROM numberplans WHERE doctype<0 AND isdefault=1 LIMIT 1');
}

$allnumberplans = array();
$numberplans = array();

if ($templist = $LMS->GetNumberPlans(array())) {
    foreach ($templist as $item) {
        if ($item['doctype'] < 0) {
            $allnumberplans[] = $item;
        }
    }
}

if (isset($document['type'])) {
    foreach ($allnumberplans as $plan) {
        if ($plan['doctype'] == $document['type']) {
            $numberplans[] = $plan;
        }
    }
}

$SMARTY->assign('numberplans', $numberplans);
$SMARTY->assign('planDocumentType', $document['type'] ?? null);

$docengines = GetDocumentTemplates($rights, $document['type'] ?? null);

if (isset($document['type']) && !empty($docengines)) {
    $defaultDocEngine = array_filter(
        $docengines,
        function ($engine) use ($document) {
            return !empty($engine['default'][$document['type']]);
        }
    );
    $defaultDocEngine = reset($defaultDocEngine);
    if (!empty($defaultDocEngine)) {
        $SMARTY->assign('defaultDocEngine', $defaultDocEngine);
    }
}

$SMARTY->assign('networks', $LMS->GetNetworks());
$SMARTY->assign('customergroups', $LMS->CustomergroupGetAll());
$SMARTY->assign('error', $error);
$SMARTY->assign('docrights', $rights);
$SMARTY->assign('allnumberplans', $allnumberplans);
$SMARTY->assign('docengines', $docengines);
$SMARTY->assign('document', $document);
$SMARTY->display('document/documentgen.html');
