#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

ini_set('error_reporting', E_ALL & ~E_NOTICE);

$parameters = array(
    'config-file:' => 'C:',
    'quiet' => 'q',
    'help' => 'h',
    'version' => 'v',
    'test' => 't',
    'fakedate:' => 'f:',
    'part-number:' => 'p:',
    'fakehour:' => 'g:',
    'part-size:' => 'l:',
    'interval:' => 'i:',
    'extra-file:' => 'e:',
    'backup' => 'b',
    'archive' => 'a',
    'output-directory:' => 'o:',
    'no-attachment' => 'n',
    'customerid:' => null,
    'customergroups:' => null,
);

$long_to_shorts = array();
foreach ($parameters as $long => $short) {
    $long = str_replace(':', '', $long);
    if (isset($short)) {
        $short = str_replace(':', '', $short);
    }
    $long_to_shorts[$long] = $short;
}

$options = getopt(
    implode(
        '',
        array_filter(
            array_values($parameters),
            function ($value) {
                return isset($value);
            }
        )
    ),
    array_keys($parameters)
);

foreach (array_flip(array_filter($long_to_shorts, function ($value) {
    return isset($value);
})) as $short => $long) {
    if (array_key_exists($short, $options)) {
        $options[$long] = $options[$short];
        unset($options[$short]);
    }
}

if (array_key_exists('version', $options)) {
    print <<<EOF
lms-sendinvoices.php
(C) 2001-2020 LMS Developers

EOF;
    exit(0);
}

if (array_key_exists('help', $options)) {
    print <<<EOF
lms-sendinvoices.php
(C) 2001-2020 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-t, --test                      print only invoices to send;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;
-f, --fakedate=YYYY/MM/DD       override system date;
-p, --part-number=NN            defines which part of invoices that should be sent;
-g, --fakehour=HH               override system hour; if no fakehour is present - current hour will be used;
                                (deprecated - use --part-number instead of);
-s, --part-size=NN              defines part size of invoices that should be sent
                                (can be specified as percentage value);
-i, --interval=ms               force delay interval between subsequent posts
-e, --extra-file=/tmp/file.pdf  send additional file as attachment
-b, --backup                    make financial document file backup
-a, --archive                   archive financial documents in documents directory
-o, --output-directory=/path    output directory for document backup
-n, --no-attachments            dont attach documents
    --customerid=<id>           limit invoices to specifed customer
    --customergroups=<group1,group2,...>
                                allow to specify customer groups to which notified customers
                                should be assigned

EOF;
    exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
    print <<<EOF
lms-sendinvoices.php
(C) 2001-2020 LMS Developers

EOF;
}

$backup = isset($options['backup']);
if ($backup) {
    if (isset($options['output-directory'])) {
        $output_dir = $options['output-directory'];
        if (!is_dir($output_dir)) {
            die("Output directory does not exist!" . PHP_EOL);
        }
    } else {
        $output_dir = getcwd();
    }
}

$archive = isset($options['archive']);
if ($archive && $backup) {
    die("Archive and backup modes cannot be used simultaneously!" . PHP_EOL);
}

if (array_key_exists('config-file', $options)) {
    $CONFIG_FILE = $options['config-file'];
} else {
    $CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';
}

if (!$quiet) {
    echo "Using file ".$CONFIG_FILE." as config." . PHP_EOL;
}

if (!is_readable($CONFIG_FILE)) {
    die("Unable to read configuration file [".$CONFIG_FILE."]!" . PHP_EOL);
}

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['doc_dir'] = (!isset($CONFIG['directories']['doc_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'documents' : $CONFIG['directories']['doc_dir']);
$CONFIG['directories']['smarty_compile_dir'] = (!isset($CONFIG['directories']['smarty_compile_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'templates_c' : $CONFIG['directories']['smarty_compile_dir']);
$CONFIG['directories']['smarty_templates_dir'] = (!isset($CONFIG['directories']['smarty_templates_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'templates' : $CONFIG['directories']['smarty_templates_dir']);
$CONFIG['directories']['plugin_dir'] = (!isset($CONFIG['directories']['plugin_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'plugins' : $CONFIG['directories']['plugin_dir']);
$CONFIG['directories']['plugins_dir'] = $CONFIG['directories']['plugin_dir'];

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('DOC_DIR', $CONFIG['directories']['doc_dir']);
define('SMARTY_COMPILE_DIR', $CONFIG['directories']['smarty_compile_dir']);
define('SMARTY_TEMPLATES_DIR', $CONFIG['directories']['smarty_templates_dir']);
define('PLUGIN_DIR', $CONFIG['directories']['plugin_dir']);
define('PLUGINS_DIR', $CONFIG['directories']['plugin_dir']);

define('K_TCPDF_EXTERNAL_CONFIG', true);

// Load autoloader
$composer_autoload_path = SYS_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
    require_once $composer_autoload_path;
} else {
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More informations at https://getcomposer.org/" . PHP_EOL);
}

// Init database

$DB = null;

try {
    $DB = LMSDB::getInstance();
} catch (Exception $ex) {
    trigger_error($ex->getMessage(), E_USER_WARNING);
    // can't working without database
    die("Fatal error: cannot connect to database!" . PHP_EOL);
}

$no_attachments = isset($options['no-attachments']);

if (!$no_attachments) {
    // Initialize templates engine (must be before locale settings)
    $SMARTY = new LMSSmarty;

    // test for proper version of Smarty

    if (defined('Smarty::SMARTY_VERSION')) {
        $ver_chunks = preg_split('/[- ]/', preg_replace('/^smarty-/i', '', Smarty::SMARTY_VERSION), -1, PREG_SPLIT_NO_EMPTY);
    } else {
        $ver_chunks = null;
    }
    if (count($ver_chunks) < 1 || version_compare('3.1', $ver_chunks[0]) > 0) {
        die('Wrong version of Smarty engine! We support only Smarty-3.x greater than 3.1.' . PHP_EOL);
    }

    define('SMARTY_VERSION', $ver_chunks[0]);

    // add LMS's custom plugins directory
    $SMARTY->addPluginsDir(LIB_DIR . DIRECTORY_SEPARATOR . 'SmartyPlugins');
}

// Include required files (including sequence is important)

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

$SYSLOG = SYSLOG::getInstance();

if (!$no_attachments) {
    // Set some template and layout variables

    $SMARTY->setTemplateDir(null);
    $custom_templates_dir = ConfigHelper::getConfig('phpui.custom_templates_dir');
    if (!empty($custom_templates_dir) && file_exists(SMARTY_TEMPLATES_DIR . DIRECTORY_SEPARATOR . $custom_templates_dir)
        && !is_file(SMARTY_TEMPLATES_DIR . DIRECTORY_SEPARATOR . $custom_templates_dir)) {
        $SMARTY->AddTemplateDir(SMARTY_TEMPLATES_DIR . DIRECTORY_SEPARATOR . $custom_templates_dir);
    }
    $SMARTY->AddTemplateDir(
        array(
            SMARTY_TEMPLATES_DIR . DIRECTORY_SEPARATOR . 'default',
            SMARTY_TEMPLATES_DIR,
        )
    );
    $SMARTY->setCompileDir(SMARTY_COMPILE_DIR);

    $SMARTY->assignByRef('layout', $layout);
}

$invoice_filename = ConfigHelper::getConfig('sendinvoices.invoice_filename', 'invoice_%docid');
$dnote_filename = ConfigHelper::getConfig('sendinvoices.debitnote_filename', 'dnote_%docid');

if ($backup || $archive) {
    $part_size = 0;
} else {
    // now it's time for script settings
    $smtp_options = array(
        'host' => ConfigHelper::getConfig('sendinvoices.smtp_host'),
        'port' => ConfigHelper::getConfig('sendinvoices.smtp_port'),
        'user' => ConfigHelper::getConfig('sendinvoices.smtp_username', ConfigHelper::getConfig('sendinvoices.smtp_user')),
        'pass' => ConfigHelper::getConfig('sendinvoices.smtp_password', ConfigHelper::getConfig('sendinvoices.smtp_pass')),
        'auth' => ConfigHelper::getConfig('sendinvoices.smtp_auth_type', ConfigHelper::getConfig('sendinvoices.smtp_auth')),
        'ssl_verify_peer' => ConfigHelper::checkValue(ConfigHelper::getConfig('sendinvoices.smtp_ssl_verify_peer', true)),
        'ssl_verify_peer_name' => ConfigHelper::checkValue(ConfigHelper::getConfig('sendinvoices.smtp_ssl_verify_peer_name', true)),
        'ssl_allow_self_signed' => ConfigHelper::checkConfig('sendinvoices.smtp_ssl_allow_self_signed'),
    );

    $customergroups = ConfigHelper::getConfig('sendinvoices.customergroups', '', true);
    $debug_email = ConfigHelper::getConfig('sendinvoices.debug_email', '', true);
    $sender_name = ConfigHelper::getConfig('sendinvoices.sender_name', '', true);
    $sender_email = ConfigHelper::getConfig('sendinvoices.sender_email', '', true);
    $mail_subject = ConfigHelper::getConfig('sendinvoices.mail_subject', 'Invoice No. %invoice');
    $mail_body = ConfigHelper::getConfig('sendinvoices.mail_body', ConfigHelper::getConfig('mail.sendinvoice_mail_body'));
    $mail_format = ConfigHelper::getConfig('sendinvoices.mail_format', 'text');
    $notify_email = ConfigHelper::getConfig('sendinvoices.notify_email', '', true);
    $reply_email = ConfigHelper::getConfig('sendinvoices.reply_email', '', true);
    $add_message = ConfigHelper::checkConfig('sendinvoices.add_message');
    $message_attachments = ConfigHelper::checkConfig('sendinvoices.message_attachments');
    $aggregate_documents = ConfigHelper::checkConfig('sendinvoices.aggregate_documents');
    $dsn_email = ConfigHelper::getConfig('sendinvoices.dsn_email', '', true);
    $mdn_email = ConfigHelper::getConfig('sendinvoices.mdn_email', '', true);
    $part_size = isset($options['part-size']) ? $options['part-size'] : ConfigHelper::getConfig('sendinvoices.limit', '0');

    if (isset($options['interval'])) {
        $interval = $options['interval'];
    } else {
        $interval = ConfigHelper::getConfig('sendinvoices.interval', 0);
    }
    if ($interval == 'random') {
        $interval = -1;
    } else {
        $interval = intval($interval);
    }


    if (empty($sender_email)) {
        die("Fatal error: sender_email unset! Can't continue, exiting." . PHP_EOL);
    }

    $smtp_auth = empty($smtp_auth) ? ConfigHelper::getConfig('mail.smtp_auth_type') : $smtp_auth;
    if (!empty($smtp_auth) && !preg_match('/^LOGIN|PLAIN|CRAM-MD5|NTLM$/i', $smtp_auth)) {
        die("Fatal error: smtp_auth setting not supported! Can't continue, exiting." . PHP_EOL);
    }

    $part_number = isset($options['part-number']) ? $options['part-number'] : (isset($options['fakehour']) ? $options['fakehour'] : null);
    if (isset($part_number)) {
        $part_number = intval($part_number);
    } else {
        $part_number = intval(date('H', time()));
    }

    $extrafile = (array_key_exists('extra-file', $options) ? $options['extra-file'] : null);
    if ($extrafile && !is_readable($extrafile)) {
        die("Unable to read additional file [$extrafile]!" . PHP_EOL);
    }
}

$fakedate = isset($options['fakedate']) ? $options['fakedate'] : null;
$customerid = isset($options['customerid']) && intval($options['customerid']) ? $options['customerid'] : null;

function localtime2($fakedate)
{
    if (!empty($fakedate)) {
        $date = explode("/", $fakedate);
        return mktime(0, 0, 0, intval($date[1]), intval($date[2]), intval($date[0]));
    } else {
        return time();
    }
}

$timeoffset = date('Z');
$currtime = localtime2($fakedate) + $timeoffset;
$daystart = (intval($currtime / 86400) * 86400) - $timeoffset;
$dayend = $daystart + 86399;

if ($backup || $archive) {
    $groupnames = '';
} else {
// prepare customergroups in sql query
    if (isset($options['customergroups'])) {
        $customergroups = $options['customergroups'];
    }
    if (!empty($customergroups)) {
        $ORs = preg_split("/([\s]+|[\s]*,[\s]*)/", mb_strtoupper($customergroups), -1, PREG_SPLIT_NO_EMPTY);
        $customergroup_ORs = array();
        foreach ($ORs as $OR) {
            $ANDs = preg_split("/([\s]*\+[\s]*)/", $OR, -1, PREG_SPLIT_NO_EMPTY);
            $customergroup_ANDs_regular = array();
            $customergroup_ANDs_inversed = array();
            foreach ($ANDs as $AND) {
                if (strpos($AND, '!') === false) {
                    $customergroup_ANDs_regular[] = $AND;
                } else {
                    $customergroup_ANDs_inversed[] = substr($AND, 1);
                }
            }
            $customergroup_ORs[] = '('
                . (empty($customergroup_ANDs_regular) ? '1 = 1' : "EXISTS (SELECT COUNT(*) FROM customergroups
                JOIN customerassignments ON customerassignments.customergroupid = customergroups.id
                WHERE customerassignments.customerid = c.id
                AND UPPER(customergroups.name) IN ('" . implode("', '", $customergroup_ANDs_regular) . "')
                HAVING COUNT(*) = " . count($customergroup_ANDs_regular) . ')')
                . (empty($customergroup_ANDs_inversed) ? '' : " AND NOT EXISTS (SELECT COUNT(*) FROM customergroups
                JOIN customerassignments ON customerassignments.customergroupid = customergroups.id
                WHERE customerassignments.customerid = c.id
                AND UPPER(customergroups.name) IN ('" . implode("', '", $customergroup_ANDs_inversed) . "')
                HAVING COUNT(*) = 0)")
                . ')';
        }
        $customergroups = ' AND (' . implode(' OR ', $customergroup_ORs) . ')';
    }

    $test = array_key_exists('test', $options);
    if ($test) {
        echo "WARNING! You are using test mode." . PHP_EOL;
    }

    if (!empty($part_size) && preg_match('/^[0-9]+$/', $part_size)) {
        $part_offset = $part_number * $part_size;
    }
}

// Initialize Session, Auth and LMS classes

$SYSLOG = null;
$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

$plugin_manager = new LMSPluginManager();
$LMS->setPluginManager($plugin_manager);

if (!$no_attachments) {
    $plugin_manager->executeHook('smarty_initialized', $SMARTY);
}

if ($backup || $archive) {
    $args = array(DOC_INVOICE, DOC_INVOICE_PRO, DOC_CNOTE, DOC_DNOTE);
} else {
    $args = array(CONTACT_EMAIL | CONTACT_INVOICES | CONTACT_DISABLED,
        CONTACT_EMAIL | CONTACT_INVOICES, DOC_INVOICE, DOC_INVOICE_PRO, DOC_CNOTE, DOC_DNOTE);

    if (!empty($part_size) && preg_match('/^(?<percent>[0-9]+)%$/', $part_size, $m)) {
        $percent = intval($m['percent']);
        if ($percent < 1 || $percent > 99) {
            $part_size = 0;
        } else {
            $count = intval($DB->GetOne("SELECT COUNT(*)
				FROM documents d
				LEFT JOIN customeraddressview c ON c.id = d.customerid
				JOIN (SELECT customerid, " . $DB->GroupConcat('contact') . " AS email
					FROM customercontacts
					WHERE (type & ?) = ?
					GROUP BY customerid
				) m ON m.customerid = c.id
				WHERE " . ($customerid ? 'c.id = ' . $customerid . ' AND ' : '') . "c.deleted = 0 AND d.cancelled = 0 AND d.type IN (?, ?, ?, ?) AND c.invoicenotice = 1
					AND d.cdate >= $daystart AND d.cdate <= $dayend"
                . ($customergroups ?: ''), $args));
            if (empty($count)) {
                die;
            }

            $part_size = floor(($percent * $count) / 100);
            $part_offset = $part_number * $part_size;
            if ($part_offset >= $count) {
                die;
            }
        }
    }
}

$ignore_send_date = ConfigHelper::checkConfig('sendinvoices.ignore_send_date');

$query = "SELECT d.id, d.number, d.cdate, d.name, d.customerid, d.type AS doctype, d.archived, d.senddate, n.template" . ($backup || $archive ? '' : ', m.email') . "
		FROM documents d
		LEFT JOIN customeraddressview c ON c.id = d.customerid"
        . ($backup || $archive ? '' : " JOIN (SELECT customerid, " . $DB->GroupConcat('contact') . " AS email
				FROM customercontacts WHERE (type & ?) = ? GROUP BY customerid) m ON m.customerid = c.id")
        . " LEFT JOIN numberplans n ON n.id = d.numberplanid 
		WHERE " . ($customerid ? 'c.id = ' . $customerid . ' AND ' : '') . "c.deleted = 0 AND d.cancelled = 0 AND d.type IN (?, ?, ?, ?)" . ($backup || $archive ? '' : " AND c.invoicenotice = 1")
            . ($archive ? " AND d.archived = 0" : '') . "
			AND d.cdate >= $daystart AND d.cdate <= $dayend"
            . (!empty($groupnames) ? $customergroups : "")
        . " ORDER BY d.number" . (!empty($part_size) ? " LIMIT $part_size OFFSET $part_offset" : '');
$docs = $DB->GetAll($query, $args);

if (!empty($docs)) {
    if ($backup) {
        foreach ($docs as $doc) {
            $doc['invoice_filename'] = $invoice_filename;
            $doc['dnote_filename'] = $dnote_filename;
            $document = $LMS->GetTradeDocument($doc);
            if (!$quiet) {
                echo "Document " . $document['filename'] . " backed up." . PHP_EOL;
            }
            if (!$test) {
                $fh = fopen($output_dir . DIRECTORY_SEPARATOR . $document['filename'], 'w');
                fwrite($fh, $document['data'], strlen($document['data']));
                fclose($fh);
            }
        }
    } elseif ($archive) {
        foreach ($docs as $doc) {
            $result = $LMS->ArchiveTradeDocument($doc['id']);
            if (!$quiet && isset($result['ok'])) {
                if ($result['ok']) {
                    echo "Document ID: " . $doc['id'] . " archived with name " . $result['filename'] . "." . PHP_EOL;
                } else {
                    echo $result['error'] . PHP_EOL;
                }
            }
        }
    } else {
        $docs_to_send = array();
        foreach ($docs as $doc) {
            if ($ignore_send_date || empty($doc['senddate'])) {
                $docs_to_send[] = $doc;
            }
        }
        if (empty($docs_to_send)) {
            die;
        }
        $docs = $docs_to_send;
        $which = 0;
        $tmp = explode(',', ConfigHelper::getConfig('invoices.default_printpage'));
        foreach ($tmp as $t) {
            if (trim($t) == 'original') {
                $which |= DOC_ENTITY_ORIGINAL;
            } elseif (trim($t) == 'copy') {
                $which |= DOC_ENTITY_COPY;
            } elseif (trim($t) == 'duplicate') {
                $which |= DOC_ENTITY_DUPLICATE;
            }
        }

        if (!$which) {
            $which = DOC_ENTITY_ORIGINAL;
        }

        $duplicate_date = 0;

        $LMS->SendInvoices($docs, 'backend', compact(
            'SMARTY',
            'invoice_filename',
            'dnote_filename',
            'debug_email',
            'mail_body',
            'mail_subject',
            'mail_format',
            'currtime',
            'sender_email',
            'sender_name',
            'extrafile',
            'dsn_email',
            'reply_email',
            'mdn_email',
            'notify_email',
            'quiet',
            'test',
            'add_message',
            'message_attachments',
            'aggregate_documents',
            'interval',
            'no_attachments',
            'which',
            'duplicate_date',
            'smtp_options'
        ));
    }
}
