<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// Standalone regression test: php document-template-paths.php <empty-scratch-directory>
// No application bootstrap, database, or external dependencies are required.

if (empty($argv[1]) || !is_dir($argv[1]) || count(scandir($argv[1])) !== 2) {
    throw new RuntimeException('Pass an existing empty scratch directory.');
}

set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

require_once __DIR__ . '/stubs/documents/DocumentTemplateTestSmarty.php';
require_once __DIR__ . '/stubs/documents/DocumentTemplateTestLMS.php';
require_once __DIR__ . '/stubs/documents/XajaxResponse.php';

function checkDocumentTemplate($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = realpath($argv[1]);
$documents_dirs = array($root . '/documents');
$SMARTY = new DocumentTemplateTestSmarty();
$LMS = new DocumentTemplateTestLMS();
$CCONSENTS = array();
$_GET = array();
$_SERVER['HTTP_HOST'] = 'example.invalid';

mkdir($root . '/documents/templates/valid-name_1', 0700, true);
mkdir($root . '/outside', 0700);
file_put_contents(
    $root . '/documents/templates/valid-name_1/info.php',
    '<?php $engine = array("title" => "Valid template");'
);
file_put_contents(
    $root . '/outside/info.php',
    '<?php throw new RuntimeException("Included a file outside the template directory");'
);

require __DIR__ . '/../../modules/document.inc.php';

$directory = GetDocumentTemplateDirectory('valid-name_1', $doc_dir);
checkDocumentTemplate($directory === $root . '/documents/templates/valid-name_1', 'Valid template rejected');
checkDocumentTemplate($doc_dir === $root . '/documents', 'Document root was not returned');

$response = new XajaxResponse();
DocTemplateChanged(0, 'valid-name_1', 1, null);
GetPlugin('valid-name_1', 1, true, $response);
checkDocumentTemplate($response->assigned['title']['value'] === 'Valid template', 'Valid template not loaded');

foreach (array(null, '', array('valid-name_1'), '../outside', '../../outside', '..\\outside',
    '/outside', 'valid-name_1/../../outside', "valid-name_1\n", "valid-name_1\0", 'missing') as $name) {
    $doc_dir = 'stale';
    checkDocumentTemplate(GetDocumentTemplateDirectory($name, $doc_dir) === null, 'Invalid template accepted');
    checkDocumentTemplate($doc_dir === null, 'Stale document root retained');
    // Exercise the registered AJAX entry point, not only the resolver.
    $response = DocTemplateChanged(0, $name, 1, null);
    checkDocumentTemplate($response->assigned['plugin']['innerHTML'] === '', 'Plugin was not reset');
    checkDocumentTemplate($response->assigned['title']['value'] === '', 'Title was not reset');
    checkDocumentTemplate(isset($response->assigned['referencedocument']), 'Reference UI was not updated');
    checkDocumentTemplate(isset($response->assigned['customer-consent-content']), 'Consent UI was not updated');
}

unlink($root . '/outside/info.php');
unlink($root . '/documents/templates/valid-name_1/info.php');
rmdir($root . '/outside');
rmdir($root . '/documents/templates/valid-name_1');
rmdir($root . '/documents/templates');
rmdir($root . '/documents');

echo "Document template path checks passed.\n";
