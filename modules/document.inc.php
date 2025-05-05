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
        if (is_readable($doc . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $_GET['template'] . DIRECTORY_SEPARATOR . 'info.php')) {
            $doc_dir = $doc;
            $template_dir = $doc . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $_GET['template'];
            break;
        }
    }
    // read template information
    if (is_readable($file = $template_dir . DIRECTORY_SEPARATOR . 'info.php')) {
        include($file);
        if (isset($engine['vhosts']) && isset($engine['vhosts'][$_SERVER['HTTP_HOST']])) {
            $engine = array_merge($engine, $engine['vhosts'][$_SERVER['HTTP_HOST']]);
        }
        if (is_readable($file = $doc_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
            . $engine['name'] . DIRECTORY_SEPARATOR . $engine['plugin'] . '.js')) {
            header('Content-Type: text/javascript');
            echo file_get_contents($file);
        }
        echo '$("#documentpromotions").toggle(' . (empty($engine['promotion-schema-selection']) ? 'false' : 'true'). ');';
        echo '$("#document-consents").toggle(' . (empty($engine['customer-consent-selection']) ? 'false' : 'true'). ');';
    }
    die;
}

function GenerateAttachmentHTML($template_dir, $engine, $selected = null)
{
    $output = array();
    if (!empty($engine['attachments']) && is_array($engine['attachments'])) {
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

function GetPlugin($template, $customerid, $update_title, $JSResponse)
{
    global $documents_dirs;

    $result = '';

    foreach ($documents_dirs as $doc) {
        if (is_readable($doc . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $template . DIRECTORY_SEPARATOR . 'info.php')) {
            $doc_dir = $doc;
            $template_dir = $doc . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $template;
            break;
        }
    }

    // read template information
    if (is_readable($file = $template_dir . DIRECTORY_SEPARATOR . 'info.php')) {
        include($file);
        if (isset($engine['vhosts']) && isset($engine['vhosts'][$_SERVER['HTTP_HOST']])) {
            $engine = array_merge($engine, $engine['vhosts'][$_SERVER['HTTP_HOST']]);
        }
    }

    // call plugin
    if (!empty($engine['plugin'])) {
        if (is_readable($file = $doc_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
            . $engine['name'] . DIRECTORY_SEPARATOR . $engine['plugin'] . '.php')) {
            include($file);
        }
        if (is_readable($doc_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
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

    if (isset($engine['default_number_plan_id']) && (ctype_digit($engine['default_number_plan_id']) || is_int($engine['default_number_plan_id']))) {
        $JSResponse->script('$(\'[name="document[numberplanid]"]\').val(\'' . intval($engine['default_number_plan_id']) . '\')');
    } else {
        $JSResponse->script('$(\'[name="document[numberplanid]"] option[data-default]\').prop(\'selected\', true)');
    }

    $JSResponse->script('$("#documentpromotions").toggle(' . (empty($engine['promotion-schema-selection']) ? 'false' : 'true') . ')');
    $JSResponse->script('$("#document-consents").toggle(' . (empty($engine['customer-consent-selection']) ? 'false' : 'true') . ')');
}

function GetDocumentTemplates($rights, $type = null)
{
    global $documents_dirs, $DOCTYPES;

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
                if (is_readable($infofile)) {
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
                    } elseif (isset($engine)) {
                        $docengines[$dir] = $engine;
                        $intersect = $DOCTYPES;
                    }

                    $default = array();
                    if (!empty($docengines[$dir]) && !empty($engine['default'])) {
                        if (is_array($engine['default'])) {
                            $default = array_filter(
                                $engine['default'],
                                function ($defaultFlag) {
                                    return !empty($defaultFlag);
                                }
                            );
                            $default = array_intersect(array_keys($default), $intersect)r;
                            $default = array_combine($default, array_fill(0, count($default), true));
                        } else {
                            foreach ($intersect as $doctype) {
                                $default[$doctype] = true;
                            }
                        }
                    }
                    if (!empty($docengines[$dir])) {
                        $docengines[$dir]['default'] = $default;
                    }
                }
            }
        }
    }
    ob_end_clean();

    if (!empty($docengines)) {
        uasort($docengines, function ($a, $b) {
            return $a['title'] <=> $b['title'];
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
    $document['type'] = $doctype;
    $SMARTY->assign('docengines', $docengines);
    $SMARTY->assign('document', $document);
    $contents = $SMARTY->fetch('document/documenttemplateoptions.html');

    $JSResponse->assign('templ', 'innerHTML', $contents);
    if (!empty($doctype)) {
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
            if (is_readable($infofile)) {
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

    $JSResponse->script('$("#a_reference_document_limit").toggle(parseInt($(this).val()) > 0);');

    $JSResponse->script('$(\'[name="document[reference]"]\').prop("required", $(\'[name="document[templ]"] option:selected\').is("[data-refdoc-required]"));');
}

function GetCustomerConsents($template, $customerid, $JSResponse, $consents = null)
{
    global $documents_dirs, $LMS, $SMARTY, $CCONSENTS;

    $result = '';

    foreach ($documents_dirs as $doc) {
        if (is_readable($doc . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $template . DIRECTORY_SEPARATOR . 'info.php')) {
            $doc_dir = $doc;
            $template_dir = $doc . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $template;
            break;
        }
    }

    // read template information
    if (is_readable($file = $template_dir . DIRECTORY_SEPARATOR . 'info.php')) {
        include($file);
        if (isset($engine['vhosts']) && isset($engine['vhosts'][$_SERVER['HTTP_HOST']])) {
            $engine = array_merge($engine, $engine['vhosts'][$_SERVER['HTTP_HOST']]);
        }
    }

    $supported_customer_consents = $CCONSENTS;
    if (!empty($engine['supported-customer-consents'])) {
        $engine['supported-customer-consents'] = array_flip($engine['supported-customer-consents']);
        $supported_customer_consents = array_filter(
            $supported_customer_consents,
            function ($consent, $consent_id) use ($engine) {
                if (is_array($consent)) {
                    return isset($engine['supported-customer-consents'][$consent_id]);
                } else {
                    return isset($engine['supported-customer-consents'][$consent]);
                }
            },
            ARRAY_FILTER_USE_BOTH
        );
    }
    $SMARTY->assign('supported_customer_consents', $supported_customer_consents);

    $document['default-consents'] = array_filter(
        $LMS->getCustomerConsents($customerid),
        function ($consent) use ($supported_customer_consents) {
            return isset($supported_customer_consents[$consent]);
        },
        ARRAY_FILTER_USE_KEY
    );

    if (!isset($consents)) {
        $document['consents'] = $document['default-consents'];
    } else {
        $document['consents'] = array_filter(
            $consents,
            function ($checked, $consent) use ($supported_customer_consents) {
                return isset($supported_customer_consents[$consent]) && !empty($checked);
            },
            ARRAY_FILTER_USE_BOTH
        );
    }

    $SMARTY->assign('variable_prefix', 'document');
    $SMARTY->assign('variables', $document);

    $template = $SMARTY->fetch('customer/customerconsents.html');

    $JSResponse->assign('customer-consent-content', 'innerHTML', $template);
    $JSResponse->script('initMultiChecks("#customer-consent-table .lms-ui-multi-check")');
}

function CustomerChanged($doctype, $doctemplate, $customerid)
{
    $JSResponse = new XajaxResponse();

    GetPlugin($doctemplate, $customerid, false, $JSResponse);
    GetTemplates($doctype, $doctemplate, $JSResponse);
    GetReferenceDocuments($doctemplate, $customerid, $JSResponse);
    GetCustomerConsents($doctemplate, $customerid, $JSResponse);

    return $JSResponse;
}

function DocTypeChanged($doctype, $customerid)
{
    $JSResponse = new XajaxResponse();

    GetTemplates($doctype, null, $JSResponse);
    GetReferenceDocuments(null, $customerid, $JSResponse);

    $JSResponse->script('$("#documentpromotions,#document-consents").toggle(false)');

    return $JSResponse;
}

function DocTemplateChanged($doctype, $doctemplate, $customerid, $consents)
{
    $JSResponse = new XajaxResponse();

    GetPlugin($doctemplate, $customerid, true, $JSResponse);
    GetReferenceDocuments($doctemplate, $customerid, $JSResponse);
    GetCustomerConsents($doctemplate, $customerid, $JSResponse, $consents);

    return $JSResponse;
}


$LMS->InitXajax();
$LMS->RegisterXajaxFunction(array('DocTypeChanged', 'DocTemplateChanged', 'CustomerChanged'));
$SMARTY->assign('xajax', $LMS->RunXajax());
