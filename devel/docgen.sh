#!/bin/bash
#
# Script generate documentation in differenf formats
# (html, txt) from source sgml files
# Required is instalation of sgml-tools with openjade
# (lynx probably have anybody)
#

cd ../doc/sgml

case "$1" in

    'html')	####### sgml -> html #######################
        jade -t sgml -d lms.dsl index.sgml
	if [ $? -ne 0 ]
	    then exit 1
	fi
	mv ./*.html ../html/en/
	exit 0
    ;;

    'txt')	####### sgml -> text #######################
	jade -t sgml -V nochunks -d lms.dsl index.sgml > ../README.html
	if [ $? -ne 0 ]
	    then exit 1
	fi
#	elinks -dump ../README.html > ../README
	lynx -dump ../README.html -display_charset=ISO-8859-1 -raw -nolist -dont_wrap_pre > ../README
        if [ $? -ne 0 ]
	    then
	    exit 2
	fi
	exit 0
    ;;

    'all')	####### sgml -> html & txt #################
        jade -t sgml -d lms.dsl index.sgml
	if [ $? -ne 0 ]
	    then exit 1
	fi
	mv ./*.html ../html/en/

	jade -t sgml -V nochunks -d lms.dsl index.sgml > ../README.html
	if [ $? -ne 0 ]
	    then exit 1
	fi
	lynx -dump ../README.html -display_charset=ISO-8859-1 -raw -nolist -dont_wrap_pre > ../README
        if [ $? -ne 0 ]
	    then
	    exit 2
	fi
    ;;
        
    *)
	echo -e "$0: Lost option.\nUsage: docgen.sh html|txt"
    ;;
esac

