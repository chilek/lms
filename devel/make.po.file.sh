#!/bin/bash

# Script creates .po file for gettext

DIRS="modules lib lib/modules" # list of directories with PHP files

echo -ne "Language? [pl]: "
read LANG
echo -ne "Charset? [ISO-8859-1]: "
read CHARSET
echo -ne "Translator? [LMS Developers]: "
read TRANSLATOR
echo -ne "Replace old .po file (y/n)? [n]: "
read REPLACE

if [ -z "$LANG" ]; then
    LANG=pl
fi
if [ -z "$CHARSET" ]; then
    CHARSET=ISO-8859-1
fi
if [ -z "$TRANSLATOR" ]; then
    TRANSLATOR="LMS Developers lms.rulez.pl"
fi
if [ -z "$REPLACE" ]; then
    REPLACE=n
fi

FILES=
for DIR in $DIRS
do
    for FILE in `ls ../$DIR/*.php`
    do
	FILES="$FILES $FILE"
    done
done

if [ $REPLACE == "n" ]; then
    xgettext -o ../lib/locale/$LANG/LC_MESSAGES/lms.po \
	    -d lms \
	    -L Python \
	    --force-po --omit-header -j \
	    $FILES						
else
    xgettext -o ../lib/locale/$LANG/LC_MESSAGES/lms.po \
	    -d lms \
	    -L Python \
	    --force-po --omit-header \
	    $FILES
    echo "
# \$Id\$

msgid \"\"
msgstr \"\"
\"Project-Id-Version: LMS-International\n\"
\"Language-Team: $TRANSLATOR\n\"
\"Last-Translator: auto\n\"
\"MIME-Version: 1.0\n\"
\"Content-Type: text/plain; charset=$CHARSET\n\"
\"Content-Transfer-Encoding: 8bit\n\"
" > tmpfile
    cat ../lib/locale/$LANG/LC_MESSAGES/lms.po >> ./tmpfile 
    cp -f ./tmpfile ../lib/locale/$LANG/LC_MESSAGES/lms.po
    rm -f ./tmpfile
fi