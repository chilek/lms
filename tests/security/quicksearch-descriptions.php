<?php

// Standalone module regression test; requires PHP with mbstring, no database.
// Run each case in its own process because quicksearch.php exits after JSON:
// php tests/security/quicksearch-descriptions.php netdevice
// php tests/security/quicksearch-descriptions.php ticket-name
// php tests/security/quicksearch-descriptions.php ticket-lastname
// php tests/security/quicksearch-descriptions.php document
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$case = $argv[1] ?? '';
$cases = array(
    'netdevice' => array('netdevice', 'serial', 'serialnumber', 'Serial number: '),
    'ticket-name' => array('ticket', 'customername', 'name', 'First/last name: '),
    'ticket-lastname' => array('ticket', 'customername', 'lastname', 'First/last name: '),
    'document' => array('document', '', 'customername', ''),
);
if (!isset($cases[$case])) {
    fwrite(STDERR, 'Choose: ' . implode(', ', array_keys($cases)) . "\n");
    exit(1);
}
list($mode, $property, $field, $prefix) = $cases[$case];

// Exercise real truncation and translation wrappers without application bootstrap.
require_once __DIR__ . '/../../lib/common.php';
require_once __DIR__ . '/stubs/quicksearch/Localisation.php';
require_once __DIR__ . '/stubs/quicksearch/ConfigHelper.php';
require_once __DIR__ . '/stubs/quicksearch/Auth.php';

const RT_NEW = 0, RT_OPEN = 1, RT_RESOLVED = 2, RT_DEAD = 3,
    RT_SCHEDULED = 4, RT_WAITING = 5, RT_VERIFIED = 7, RT_RIGHT_READ = 1;

// Expected strings are literal HTML encodings, independent of the renderer.
$values = array(
    array('abc<svg onload=alert(1)>', 'abc&lt;svg onload=alert(1)&gt;'),
    array('abc "A&B" \'C\'', 'abc &quot;A&amp;B&quot; &#039;C&#039;'),
    array('abc &lt;tag&gt;', 'abc &amp;lt;tag&amp;gt;'),
    array('abc Żółć', 'abc Żółć'),
);
if ($mode === 'document') {
    // Escaping must follow truncation, so an encoded entity is never cut in half.
    $values[] = array(str_repeat('a', 30) . '<x> more', str_repeat('a', 30) . '&lt;x...');
}
$rows = array();
$expected = array();
foreach ($values as $index => $value) {
    $row = array(
        'id' => $index + 1, 'cid' => 1, 'name' => 'Device', 'lastname' => 'Customer',
        'lastonline' => 0, 'state' => RT_NEW, 'subject' => 'Ticket', 'fullnumber' => 'DOC/1',
    );
    $row[$field] = $value[0];
    $rows[] = $row;
    $expected[] = $prefix . $value[1];
}

$DB = new class ($rows) {
    private $rows;

    public function __construct($rows)
    {
        $this->rows = $rows;
    }

    public function Escape($value)
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }

    public function GroupConcat($column, $separator)
    {
        return $column;
    }

    public function GetAll($query, $params)
    {
        return $this->rows;
    }
};
$LMS = new class {
    public function GetUserCategories($id)
    {
        return array(array('id' => 1));
    }

    public function executeHook($name, $data)
    {
        return $data;
    }
};
$SESSION = new class {
    public function close()
    {
    }
};
$_POST = array();
$_GET = array('mode' => $mode, 'what' => 'abc', 'ajax' => '1', 'properties' => array($property));

ob_start();
register_shutdown_function(function () use ($expected, $case) {
    $output = ob_get_clean();
    $error = error_get_last();
    $result = json_decode($output, true);
    if ($error !== null || !is_array($result) || array_column($result, 'description') !== $expected) {
        fwrite(STDERR, 'FAIL: ' . $case . ' descriptions or module execution' . "\n");
        exit(1);
    }
    fwrite(STDOUT, 'OK: ' . $case . ', ' . count($expected) . " descriptions\n");
});

require __DIR__ . '/../../modules/quicksearch.php';
