<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2021 LMS Developers
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
        if (isset($engine['vhosts']) && isset($engine['vhosts'][$_SERVER['HTTP_HOST']])) {
            $engine = array_merge($engine, $engine['vhosts'][$_SERVER['HTTP_HOST']]);
        }
        if (file_exists($file = $doc_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
            . $engine['name'] . DIRECTORY_SEPARATOR . $engine['plugin'] . '.js')) {
            header('Content-Type: text/javascript');
            echo file_get_contents($file);
        }
        echo '$("#documentpromotions").toggle(' . (empty($engine['promotion-schema-selection']) ? 'false' : 'true'). ');';
    }
    die;
}

function GenerateAttachmentHTML($template_dir, $engine, $selected = null)
{
    $output = array();
    if (isset($engine['attachments']) && !empty($engine['attachments']) && is_array($engine['attachments'])) {
        foreach ($engine['attachments'] as $idx => $file) {
            if (is_array($file)) {
                $file['checked'] = isset($selected) ? isset($selected[$file['label']]) : $file['checked'];
            } else {
                $file = array(
                    'name' => $file,
                    'label' => $idx,
                    'checked' => isset($selected[$idx]),
                );
            }
            if ($file['name'] != DIRECTORY_SEPARATOR) {
                $file['name'] = $template_dir . DIRECTORY_SEPARATOR . $file['name'];
            }
            if (is_readable($file['name'])) {
                $output[] = '<label>'
                . '<input type="checkbox" value="1" name="document[attachments][' . htmlspecialchars($file['label']) . ']"'
                    . ($file['checked'] ? ' checked' : '') . '>'
                . $file['label']
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
        if (isset($engine['vhosts']) && isset($engine['vhosts'][$_SERVER['HTTP_HOST']])) {
            $engine = array_merge($engine, $engine['vhosts'][$_SERVER['HTTP_HOST']]);
        }
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

    $attachment_content = GenerateAttachmentHTML($template_dir, $engine);
    $JSResponse->assign('attachment-cell', 'innerHTML', $attachment_content);
    if (empty($attachment_content)) {
        $JSResponse->script('$("#attachment-row").hide()');
    } else {
        $JSResponse->script('$("#attachment-row").show()');
    }

    $JSResponse->assign('plugin', 'innerHTML', $result);
    if ($update_title) {
        $JSResponse->assign('title', 'value', $engine['form_title'] ?? $engine['title']);
    }

    $JSResponse->script('$("#documentpromotions").toggle(' . (empty($engine['promotion-schema-selection']) ? 'false' : 'true') . ')');
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
                    if (isset($engine['vhosts']) && isset($engine['vhosts'][$_SERVER['HTTP_HOST']])) {
                        $engine = array_merge($engine, $engine['vhosts'][$_SERVER['HTTP_HOST']]);
                    }
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
        uasort($docengines, function ($a, $b) {
            if ($a['title'] == $b['title']) {
                return 0;
            }
            return $a['title'] < $b['title'] ? -1 : 1;
        });
    }

    return $docengines;
}

function GetTemplates($doctype, $doctemplate, $JSResponse)
{
    global $SMARTY;

    $DB = LMSDB::getInstance();
    $rights = $DB->GetCol(
        'SELECT doctype
        FROM docrights
        WHERE userid = ?
            AND (rights & ?) > 0',
        array(
            Auth::GetCurrentUser(),
            DOCRIGHT_CREATE,
        )
    );
    $docengines = GetDocumentTemplates($rights, $doctype);
    $document['templ'] = $doctemplate;
    $SMARTY->assign('docengines', $docengines);
    $SMARTY->assign('document', $document);
    $contents = $SMARTY->fetch('document/documenttemplateoptions.html');

    $JSResponse->assign('templ', 'innerHTML', $contents);
    if (isset($doctype) && !empty($doctype)) {
        $JSResponse->call('enable_templates');
    } else {
        $JSResponse->call('disable_templates');
    }
}

function GetDocumentNumberPlans($doctype, $customerid = null)
{
    $db = LMSDB::getInstance();
    $lms = LMS::getInstance();

    if (!empty($doctype)) {
        $args = array(
            'doctype' => $doctype,
        );
        if (!empty($customerid)) {
            $args['customerid'] = array(
                'customerid' => $customerid,
            );
            $divisionid = $db->GetOne('SELECT divisionid FROM customers WHERE id = ?', array($customerid));
            if (!empty($divisionid)) {
                $args['division'] = $divisionid;
            }
        }
        $numberplans = $lms->GetNumberPlans($args);
        if (empty($numberplans)) {
            $numberplans = array();
        }
    } else {
        $numberplans = array();
    }

    return $numberplans;
}

function GetReferenceDocuments($doctemplate, $customerid, $JSResponse)
{
    global $SMARTY, $LMS, $documents_dirs;

    $SMARTY->assign('cid', $customerid);
    $SMARTY->assign('document', array('reference' => ''));

    $references = $LMS->GetDocuments($customerid, null, isset($doctemplate));

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
                } else {
                    foreach ($references as $idx => $reference) {
                        if ($reference['doctype'] >= 0) {
                            unset($references[$idx]);
                        }
                    }
                }

                break;
            }
        }
        ob_end_clean();
    }

    if (!empty($references)) {
        $references = array_reverse($references);
    }

    $SMARTY->assign('references', $references);

    $template = $SMARTY->fetch('document/documentreference.html');

    $JSResponse->assign('referencedocument', 'innerHTML', $template);

    $JSResponse->script('$(\'[name="document[reference]"]\').change(function() {'
        . ' if (parseInt($(this).val())) { $("#a_reference_document_limit").show(); }'
        . ' else { $("#a_reference_document_limit").hide(); }'
        . '}).trigger("change")');

    $JSResponse->script('$(\'[name="document[reference]"]\').prop("required", $(\'[name="document[templ]"] option:selected\').is("[data-refdoc-required]"));');
}

function CustomerChanged($doctype, $doctemplate, $customerid)
{
    $JSResponse = new XajaxResponse();

    GetPlugin($doctemplate, $customerid, false, $JSResponse);
    GetTemplates($doctype, $doctemplate, $JSResponse);
    GetReferenceDocuments($doctemplate, $customerid, $JSResponse);

    return $JSResponse;
}

function DocTypeChanged($doctype, $customerid)
{
    $JSResponse = new XajaxResponse();

    GetTemplates($doctype, null, $JSResponse);
    GetReferenceDocuments(null, $customerid, $JSResponse);

    $JSResponse->script('$("#documentpromotions").toggle(false)');

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
$LMS->RegisterXajaxFunction(array('DocTypeChanged', 'DocTemplateChanged', 'CustomerChanged'));
$SMARTY->assign('xajax', $LMS->RunXajax());
