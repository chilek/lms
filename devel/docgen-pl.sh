#!/bin/bash
#
# Skrypt s³u¿y do tworzenia dokumentacji w ró¿nych formatach
# (html, txt) na podstawie ¼ród³owych plików sgml
# Przed u¿yciem nale¿y zainstalowaæ w systemie sgml-tools
# z openjade (lynx'a pewnie ka¿dy ma)
#

cd ../doc/sgml/pl

case "$1" in

    'html')	####### sgml -> html #######################
        jade -t sgml -d lms.dsl index.sgml
	if [ $? -ne 0 ]
	    then exit 1
	fi
	mv ./*.html ../../html/pl/
	exit 0
    ;;

    'txt')	####### sgml -> text #######################
	jade -t sgml -V nochunks -d lms.dsl index.sgml > ../../README_pl.html
	if [ $? -ne 0 ]
	    then exit 1
	fi
#	elinks -dump ../../README.html > ../../README
	lynx -dump ../../README_pl.html -display_charset=ISO-8859-2 -raw -nolist -dont_wrap_pre > ../../README_pl
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
	mv ./*.html ../../html/pl/

	jade -t sgml -V nochunks -d lms.dsl index.sgml > ../../README_pl.html
	if [ $? -ne 0 ]
	    then exit 1
	fi
	lynx -dump ../../README_pl.html -display_charset=ISO-8859-2 -raw -nolist -dont_wrap_pre > ../../README_pl
        if [ $? -ne 0 ]
	    then
	    exit 2
	fi
    ;;
        
    *)
	echo -e "$0: Brak parametru.\nSposób u¿ycia: docgen.sh html|txt"
    ;;
esac

