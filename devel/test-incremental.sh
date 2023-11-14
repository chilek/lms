#!/bin/bash
PHPCS_STANDARD="phpcs3.xml"

#Test Smarty templates
for i in `git diff --name-only HEAD HEAD~10 '*.html'`
do
    if devel/smartylint.php $i | grep -i "syntax error"
    then
        exit 1
    fi
done

#Test PHP files
if (php -l `git diff --name-only HEAD HEAD~10 '*.php'` | grep -v 'No syntax errors detected') || (vendor/bin/phpcs --standard=$PHPCS_STANDARD $i)
then
    exit 1
fi

#Test JS files
for i in `git diff --name-only HEAD HEAD~10 '*.js'`
do
    if (jshint $i)
    then
        exit 1
    fi
done
