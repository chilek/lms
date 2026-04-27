#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2026 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 */

use Lms\KSeF\KSeF;
use Lms\KSeF\KSeFConfig;
use Lms\KSeF\KSeFRepository;
use Lms\KSeF\KSeFSubmissionService;
use Lms\KSeF\N1ebieskiKSeFGateway;

$script_parameters = [
    'send' => null,
    'sync' => null,
    'test' => 't',
    'section:' => 's:',
    'division:' => null,
    'customerid:' => null,
];

$script_help = <<<EOF
    --send                      send eligible sales invoices to KSeF;
    --sync                      synchronize pending KSeF invoice statuses and UPO files;
-t, --test                      dry run; print candidate counts only;
-s, --section=<section-name>    configuration section name, default: ksef;
    --division=<shortname>      limit sending candidates to selected division;
    --customerid=<id>           limit sending candidates to selected customer;
EOF;

require_once('script-options.php');

$send = isset($options['send']);
$sync = isset($options['sync']);

if (!$send && !$sync) {
    die('Use --send and/or --sync.' . PHP_EOL);
}

$SYSLOG = SYSLOG::getInstance();
$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

$plugin_manager = LMSPluginManager::getInstance();
$LMS->setPluginManager($plugin_manager);

$section = isset($options['section']) && preg_match('/^[a-z0-9-_]+$/i', $options['section'])
    ? $options['section']
    : 'ksef';
$repository = new KSeFRepository($DB);

$divisionId = null;
if (!empty($options['division'])) {
    $divisionId = $LMS->getDivisionIdByShortName($options['division']);
    if (empty($divisionId)) {
        die('Unknown division: ' . $options['division'] . PHP_EOL);
    }
    ConfigHelper::setFilter($divisionId);
}
$customerId = isset($options['customerid']) ? intval($options['customerid']) : null;
$configProvider = function (?int $selectedDivisionId = null) use ($section, $options) {
    if ($selectedDivisionId !== null) {
        ConfigHelper::setFilter($selectedDivisionId);
    }

    return KSeFConfig::fromConfigHelper($section, !isset($options['test']));
};
$config = KSeFConfig::fromConfigHelper($section, false);

if (isset($options['test'])) {
    if ($send) {
        $eligible = $repository->getEligibleInvoices($config->getMaxDocuments(), $divisionId, $customerId);
        echo 'KSeF send candidates: ' . count($eligible) . PHP_EOL;
    }
    if ($sync) {
        $pending = $repository->getPendingDocuments($config->getMaxDocuments(), $divisionId, $customerId);
        echo 'KSeF pending documents: ' . count($pending) . PHP_EOL;
    }
    exit(0);
}

$gateway = new N1ebieskiKSeFGateway();
$ksef = new KSeF($DB, $LMS);
$service = new KSeFSubmissionService(
    $repository,
    $gateway,
    function (array $invoice) use ($LMS, $ksef) {
        $invoiceContent = $LMS->GetInvoiceContent((int) $invoice['id']);
        if (empty($invoiceContent)) {
            return ['error' => 'Invoice not found.'];
        }

        return $ksef->getInvoiceXml($invoiceContent);
    },
    $configProvider
);

if ($send) {
    $result = $service->send($config, $divisionId, $customerId);
    echo 'KSeF submitted: ' . $result['submitted'] . ', skipped: ' . $result['skipped'] . PHP_EOL;
    foreach ($result['errors'] as $error) {
        echo 'Document ' . $error['docid'] . ': ' . $error['error'] . PHP_EOL;
    }
}

if ($sync) {
    $result = $service->sync($config, $divisionId, $customerId);
    echo 'KSeF status updates: ' . $result['updated'] . PHP_EOL;
    foreach ($result['errors'] as $error) {
        echo 'KSeF document ' . $error['id'] . ': ' . $error['error'] . PHP_EOL;
    }
}
