#!/bin/bash

#
#  Script makes list of strings for translation and create strings.php file
#
#  With 'diff XX' option script will create diff-file with strings which
#  are missing (not translated) in /lib/locale/XX/strings.php
#
#  With 'validate XX' option script will validate /lib/locale/XX/strings.php
#  file and output badly formatted lines

case "$1" in

    'diff')
	if [ "$2" == "" ]
	then	
	    echo "You must specify locale. Usage: strings.sh diff <locale symbol>"
	    exit 1; 
	fi
	if [ -x ../lib/locale/$2/strings.php ]
	then
	    echo "No such file: ../lib/locale/$2/strings.php. Can't diff."
	    exit 1
	fi
	diff=1
    ;;
    'validate')
	if [ "$2" == "" ]
	then	
	    echo "You must specify locale. Usage: strings.sh validate <locale symbol>"
	    exit 1; 
	fi
	if [ -x ../lib/locale/$2/strings.php ]
	then
	    echo "No such file: ../lib/locale/$2/strings.php Can't validate."
	    exit 1
	fi
	grep '$_LANG' ../lib/locale/$2/strings.php|awk -f strings_validate.awk
	exit
    ;;
esac

echo "Parsing templates"
for FILENAME in `ls ../templates/*.html`
do
    echo -n "$FILENAME... "
    perl -lne 'print for /text="(.*?[^\\])"/g' $FILENAME >> html_strings
    perl -lne 'print for /_tip="(.*?[^\\])"/g' $FILENAME >> html_strings
    perl -lne 'print for /\{trans\("([^"]+)"\)\}/g' $FILENAME >> html_strings
    perl -lne 'print for /\{t[^}]*\}([^{]*)\{\/t}/g' $FILENAME >> html_strings
    echo "done."
done
for FILENAME in `ls ../documents/templates/default/*.html`
do
    echo -n "$FILENAME... "
    perl -lne 'print for /\{trans\("([^"]+)"\)\}/g' $FILENAME >> html_strings
    perl -lne 'print for /\{t[^}]*\}([^{]*)\{\/t}/g' $FILENAME >> html_strings
    echo "done."
done

perl -pi -e 's/\\\$/\$/g' html_strings   	# \$ -> $
perl -pi -e 's/\\/\\\\/g' html_strings   	# \ -> \\
perl -pi -e 's/\x27/\\\x27/g' html_strings	# ' -> \'

echo "Parsing modules"
for FILENAME in `ls ../modules/*.php`
do
    echo -n "$FILENAME... "
    perl -lne 'print for /trans\(\x27(.*?[^\\])\x27/g' $FILENAME >> php_strings
    echo "done."
done
for FILENAME in `ls ../lib/*.php`
do
    echo -n "$FILENAME... "
    perl -lne 'print for /trans\(\x27(.*?[^\\])\x27/g' $FILENAME >> php_strings
    echo "done."
done
for FILENAME in `ls ../documents/templates/default/*.php`
do
    echo -n "$FILENAME... "
    perl -lne 'print for /trans\(\x27(.*?[^\\])\x27/g' $FILENAME >> php_strings
    echo "done."
done

#perl -pi -e 's/\\/\\\\/g' php_strings   	# \ -> \\
#perl -pi -e 's/\x27/\\\x27/g' php_strings	# ' -> \'

echo -n "Sorting and removing duplicated lines... "
cat html_strings >> tmp_strings
cat php_strings >> tmp_strings
sort tmp_strings 2>/dev/null | uniq > strings.txt
LINESNUM=`cat strings.txt | wc -l`
echo "done. Lines: $LINESNUM"

# delete temp files
rm html_strings 2>/dev/null
rm php_strings 2>/dev/null
rm tmp_strings 2> /dev/null

echo -n "Creating strings.php file... "
cp strings.txt tmp_strings
rm strings.php 2> /dev/null
echo -e "<?php\n" >> strings.php
cat ../doc/COPYRIGHTS >> strings.php
echo "" >> strings.php
perl -lne 'print "\$_LANG[\x27$_\x27] = \x27$_\x27;"' tmp_strings >> strings.php 
echo -e "\n?>" >> strings.php
rm tmp_strings
echo "done."

if [ "$diff" = "1" ]
then
	echo -n "Creating diff... "
	# parse new and old strings.php files
	perl -ne 'print if s/\$_LANG\[\x27(.*?[^\\])\x27\].*/$1/' < strings.php > strings.new
	perl -ne 'print if s/\$_LANG\[\x27(.*?[^\\])\x27\].*/$1/' < ../lib/locale/$2/strings.php > strings.old
	# make a diff from parsed files
	# sort old strings
	sort strings.old > tmp_strings.old 
	diff tmp_strings.old strings.new > strings.diff
	rm tmp_strings.old
	# and change format of diff file
	#perl -nle 'print "\$_LANG[\x27$_\x27] = \x27$_\x27;"' < strings.diff.tmp > strings.diff
	# clean up
	#rm strings.diff.tmp 2>/dev/null
	rm strings.new 2>/dev/null
	rm strings.old 2>/dev/null
	DIFFLINESNUM=`cat strings.diff | wc -l`
	if [ "$DIFFLINESNUM" = "0" ]
	then
		rm strings.diff
	fi
	echo "done. Lines: $DIFFLINESNUM"
fi
