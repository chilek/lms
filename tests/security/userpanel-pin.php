<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// Standalone regression test: php tests/security/userpanel-pin.php
// Avoid the constructor, database, session handling and application bootstrap.
require_once __DIR__ . '/../../userpanel/lib/Session.class.php';

class ConfigHelper
{
    public static function checkConfig($name)
    {
        if ($name !== 'userpanel.pin_validation') {
            throw new RuntimeException('Unexpected configuration lookup: ' . $name);
        }
        return true;
    }
}

$reflection = new ReflectionClass('Session');
$session = $reflection->newInstanceWithoutConstructor();
$checkPIN = $reflection->getMethod('checkPIN');
$checkPIN->setAccessible(true);
$validPIN = $reflection->getMethod('validPIN');
$validPIN->setAccessible(true);

$properties = array();
foreach (array('passwd', 'unsecure_pin_validity', 'pin_allowed_characters', 'unsecure_pin_allowed_characters') as $name) {
    $properties[$name] = $reflection->getProperty($name);
    $properties[$name]->setAccessible(true);
}
$properties['unsecure_pin_validity']->setValue($session, 0);

$hash = password_hash('001234', PASSWORD_BCRYPT);
if (!is_string($hash)) {
    throw new RuntimeException('Unable to generate password hash for regression test');
}

// Each submitted PIN passes the configured character policy first.
$cases = array(
    array('exact plaintext PIN', '001234', '001234', '0123456789', true),
    array('missing leading zeros', '001234', '1234', '0123456789', false),
    array('extra leading zeros', '1234', '001234', '0123456789', false),
    array('incorrect plaintext PIN', '001234', '001235', '0123456789', false),
    array('numeric exponent notation', '1234', '1234e0', '0123456789e', false),
    array('distinct zero exponents', '0e1234', '0e5678', '0123456789e', false),
    array('exact MD5 PIN', md5('001234'), '001234', '0123456789', true),
    array('uppercase MD5 hash', strtoupper(md5('001234')), '001234', '0123456789', true),
    array('incorrect MD5 PIN', md5('001234'), '1234', '0123456789', false),
    array('exact password_hash PIN', $hash, '001234', '0123456789', true),
    array('incorrect password_hash PIN', $hash, '1234', '0123456789', false),
);

foreach ($cases as $case) {
    list($label, $stored, $submitted, $characters, $expected) = $case;
    $properties['passwd']->setValue($session, $submitted);
    $properties['pin_allowed_characters']->setValue($session, $characters);
    $properties['unsecure_pin_allowed_characters']->setValue($session, $characters);
    if (!$validPIN->invoke($session, $stored)) {
        throw new RuntimeException($label . ': fixture rejected by character policy');
    }
    if ($checkPIN->invoke($session, $stored, time()) !== $expected) {
        throw new RuntimeException($label . ': unexpected authentication result');
    }
}

echo 'OK: ' . count($cases) . " userpanel PIN regression cases\n";
