#!/bin/bash
#Script for testing

PHPCS_STANDARD="phpcs3.xml"

#Test Smarty templates
if find templates -type f -iname '*.html' | xargs devel/smartylint.php |grep -i "syntax error"; then exit 1; fi

#Test PHP files
if find . -name "*.php" ! -path "./vendor/*" -print0 | xargs -0 -n 1 -P 8 php -l | grep -v "No syntax errors detected"; then exit 1; fi
./vendor/bin/phpcs --standard=$PHPCS_STANDARD .

#Test JS files
jshint .
