<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

// support for dynamic loading of plugin javascript code
if (isset($_GET['template'])) {
    foreach ($documents_dirs as $doc) {
        if (file_exists($doc . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $_GET['template'])) {
            $doc_dir = $doc;
            $template_dir = $doc . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $_GET['template'];
            break;
        }
    }
    // read template information
    if (file_exists($file = $template_dir . DIRECTORY_SEPARATOR . 'info.php')) {
        include($file);
        if (file_exists($file = $doc_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
            . $engine['name'] . DIRECTORY_SEPARATOR . $engine['plugin'] . '.js')) {
            header('Content-Type: text/javascript');
            echo file_get_contents($file);
        }
    }
    die;
}

function GenerateAttachmentHTML($template_dir, $engine, $selected)
{
    $output = array();
    if (isset($engine['attachments']) && !empty($engine['attachments']) && is_array($engine['attachments'])) {
        foreach ($engine['attachments'] as $label => $file) {
            if ($file[0] != DIRECTORY_SEPARATOR) {
                $file = $template_dir . DIRECTORY_SEPARATOR . $file;
            }
            if (is_readable($file)) {
                $output[] = '<label>'
                . '<input type="checkbox" value="1" name="document[attachments][' . $label . ']"'
                    . (isset($selected[$label]) ? ' checked' : '') . '>'
                . $label
                . '</label>';
            }
        }
    }
    return implode('<br>', $output);
}

function GetPlugin($template, $customer, $update_title, $JSResponse)
{
    global $documents_dirs;
    
    $result = '';

    foreach ($documents_dirs as $doc) {
        if (file_exists($doc . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $template)) {
            $doc_dir = $doc;
            $template_dir = $doc . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $template;
            break;
        }
    }

    // read template information
    if (file_exists($file = $template_dir . DIRECTORY_SEPARATOR . 'info.php')) {
        include($file);
    }

    // call plugin
    if (!empty($engine['plugin'])) {
        if (file_exists($file = $doc_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
            . $engine['name'] . DIRECTORY_SEPARATOR . $engine['plugin'] . '.php')) {
            include($file);
        }
        if (file_exists($doc_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
            . $engine['name'] . DIRECTORY_SEPARATOR . $engine['plugin'] . '.js')) {
            $JSResponse->removeScript($_SERVER['REQUEST_URI'] . '&template=' . $template);
            $JSResponse->includeScript($_SERVER['REQUEST_URI'] . '&template=' . $template);
        }
    }

    $attachment_content = GenerateAttachmentHTML($template_dir, $engine, array());
    $JSResponse->assign('attachment-cell', 'innerHTML', $attachment_content);
    if (empty($attachment_content)) {
        $JSResponse->script('$("#attachment-row").hide()');
    } else {
        $JSResponse->script('$("#attachment-row").show()');
    }

    $JSResponse->assign('plugin', 'innerHTML', $result);
    if ($update_title) {
        $JSResponse->assign('title', 'value', isset($engine['form_title']) ? $engine['form_title'] : $engine['title']);
    }
}

function GetDocumentTemplates($rights, $type = null)
{
    global $documents_dirs;

    $docengines = array();

    if (!$type) {
        $types = $rights;
    } elseif (in_array($type, $rights)) {
        $types = array($type);
    } else {
        return null;
    }

    ob_start();
    foreach ($documents_dirs as $doc_dir) {
        if ($dirs = getdir($doc_dir . DIRECTORY_SEPARATOR . 'templates', '^[a-z0-9_-]+$')) {
            foreach ($dirs as $dir) {
                $infofile = $doc_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
                . $dir . DIRECTORY_SEPARATOR . 'info.php';
                if (file_exists($infofile)) {
                    unset($engine);
                    include($infofile);
                    if (isset($engine['type'])) {
                        if (!is_array($engine['type'])) {
                            $engine['type'] = array($engine['type']);
                        }
                        $intersect = array_intersect($engine['type'], $types);
                        if (!empty($intersect)) {
                            $docengines[$dir] = $engine;
                        }
                    } else {
                        $docengines[$dir] = $engine;
                    }
                }
            }
        }
    }
    ob_end_clean();

    if (!empty($docengines)) {
        ksort($docengines);
    }

    return $docengines;
}

function GetTemplates($doctype, $doctemplate, $JSResponse)
{
    global $SMARTY;

    $DB = LMSDB::getInstance();
    $rights = $DB->GetCol('SELECT doctype FROM docrights WHERE userid = ? AND (rights & 2) = 2', array(Auth::GetCurrentUser()));
    $docengines = GetDocumentTemplates($rights, $doctype);
    $SMARTY->assign('docengines', $docengines);
    $SMARTY->assign('doctemplate', $doctemplate);
    $contents = $SMARTY->fetch('document/documenttemplateoptions.html');

    $JSResponse->assign('templ', 'innerHTML', $contents);
    if (!empty($doctype)) {
        $JSResponse->call('show_templates');
    }
}

function GetDocumentNumberPlans($doctype, $customerid = null)
{
    global $LMS;

    $DB = LMSDB::getInstance();

    if (!empty($doctype)) {
        $args = array(
            'doctype' => $doctype,
        );
        if (!empty($customerid)) {
            $args['customerid'] = array(
                'customerid' => $customerid,
            );
            $divisionid = $DB->GetOne('SELECT divisionid FROM customers WHERE id = ?', array($customerid));
            if (!empty($divisionid)) {
                $args['division'] = $divisionid;
            }
        }
        $numberplans = $LMS->GetNumberPlans($args);
        if (empty($numberplans)) {
            $numberplans = array();
        }
    } else {
        $numberplans = array();
    }

    return $numberplans;
}

function _GetNumberPlans($doctype, $numberplanid, $customerid, $JSResponse)
{
    global $SMARTY;

    $numberplans = GetDocumentNumberPlans($doctype, $customerid);

    $SMARTY->assign('numberplans', $numberplans);
    $SMARTY->assign('numberplanid', $numberplanid);
    $SMARTY->assign('customerid', $customerid);
    $contents = $SMARTY->fetch('document/documentnumberplans.html');

    $JSResponse->assign('numberplans', 'innerHTML', $contents);
    $JSResponse->assign('numberplans', 'style', empty($numberplans) ? 'display: none' : 'display: inline');
}

function GetNumberPlans($doctype, $numberplanid, $customerid)
{
    $JSResponse = new XajaxResponse();

    _GetNumberPlans($doctype, $numberplanid, $customerid, $JSResponse);

    return $JSResponse;
}

function GetReferenceDocuments($doctemplate, $customerid, $JSResponse)
{
    global $SMARTY, $LMS, $documents_dirs;

    $SMARTY->assign('cid', $customerid);
    $SMARTY->assign('document', array('reference' => ''));

    $references = $LMS->GetDocuments($customerid);

    if (!empty($doctemplate)) {
        ob_start();
        foreach ($documents_dirs as $doc_dir) {
            $infofile = $doc_dir . '/templates/' . $doctemplate . '/info.php';
            if (file_exists($infofile)) {
                include($infofile);
                if (isset($engine['reference_templates'])) {
                    if (is_array($engine['reference_templates'])) {
                        $reference_templates = $engine['reference_templates'];
                    } else {
                        $reference_templates = array($engine['reference_templates']);
                    }
                    foreach ($references as $idx => $reference) {
                        if (!empty($reference['doctemplate']) && !in_array($reference['doctemplate'], $reference_templates)) {
                            unset($references[$idx]);
                        }
                    }
                }
                if (isset($engine['reference_types'])) {
                    if (is_array($engine['reference_types'])) {
                        $reference_types = $engine['reference_types'];
                    } else {
                        $reference_types = array($engine['reference_types']);
                    }
                    foreach ($references as $idx => $reference) {
                        if (!in_array($reference['doctype'], $reference_types)) {
                            unset($references[$idx]);
                        }
                    }
                }
                break;
            }
        }
        ob_end_clean();
    }

    $SMARTY->assign('references', $references);

    $template = $SMARTY->fetch('document/documentreference.html');

    $JSResponse->assign('referencedocument', 'innerHTML', $template);

    $JSResponse->script('$(\'[name="document[reference]"]\').change(function() {'
        . ' if (parseInt($(this).val())) { $("#a_reference_document_limit").show(); }'
        . ' else { $("#a_reference_document_limit").hide(); }'
        . '}).trigger("change")');
}

function CustomerChanged($doctype, $doctemplate, $numberplanid, $customerid)
{
    $JSResponse = new XajaxResponse();

    GetPlugin($doctemplate, $customerid, false, $JSResponse);
    _GetNumberPlans($doctype, $numberplanid, $customerid, $JSResponse);
    GetTemplates($doctype, $doctemplate, $JSResponse);
    GetReferenceDocuments($doctemplate, $customerid, $JSResponse);

    return $JSResponse;
}

function DocTypeChanged($doctype, $numberplanid, $customerid)
{
    $JSResponse = new XajaxResponse();

    _GetNumberPlans($doctype, $numberplanid, $customerid, $JSResponse);
    GetTemplates($doctype, null, $JSResponse);
    GetReferenceDocuments(null, $customerid, $JSResponse);

    return $JSResponse;
}

function DocTemplateChanged($doctype, $doctemplate, $customerid)
{
    $JSResponse = new XajaxResponse();

    GetPlugin($doctemplate, $customerid, true, $JSResponse);
    GetReferenceDocuments($doctemplate, $customerid, $JSResponse);

    return $JSResponse;
}


$LMS->InitXajax();
$LMS->RegisterXajaxFunction(array('GetNumberPlans', 'DocTypeChanged', 'DocTemplateChanged', 'CustomerChanged'));
$SMARTY->assign('xajax', $LMS->RunXajax());
