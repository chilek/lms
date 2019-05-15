#!/usr/bin/env php
<?php

if ($argc != 2) {
    die($argv[0] . ': syntax error!' . PHP_EOL);
}

if (!file_exists($argv[1])) {
    die($argv[0] . ': unable to open ' . $argv[1] . ' file!' . PHP_EOL);
}

$dirname = dirname(__FILE__);

$files = glob($dirname . '/../doc/configuration-variables/*');
foreach ($files as $file) {
    if (is_file($file)) {
        unlink($file);
    }
}

$lines = file($argv[1]);

include($dirname . '/../contrib/initLMS.php');

$parser = new Parsedown();

$variable = null;
$buffer = '';
foreach ($lines as $line) {
    if (preg_match('/^##\s+(?<variable>.+)\r?\n/', $line, $m)) {
        if ($variable && $buffer) {
            $buffer = $parser->Text($buffer);
            file_put_contents($dirname . '/../doc/configuration-variables/' . $variable, $buffer);
        }
        $variable = $m['variable'];
        $buffer = '';
    } elseif (preg_match('/^\*\*\*/', $line)) {
        if ($variable && $buffer) {
            $buffer = $parser->Text($buffer);
            file_put_contents($dirname . '/../doc/configuration-variables/' . $variable, $buffer);
        }
        $variable = null;
        $buffer = '';
    } elseif ($variable) {
        $buffer .= $line;
    }
}
if ($variable && $buffer) {
    $buffer = $parser->Text($buffer);
    file_put_contents($dirname . '/../doc/configuration-variables/' . $variable, $buffer);
}
