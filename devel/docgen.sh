#!/bin/bash
#
# Skrypt s�u�y do tworzenia dokumentacji w r�nych formatach
# (html, txt) na podstawie �r�d�owych plik�w sgml
# Przed u�yciem nale�y zainstalowa� w systemie sgml-tools
# z openjade oraz elinks (tylko dla formatu txt)
#

cd ../doc/sgml

case "$1" in

    'html')	####### sgml -> html #######################
        jade -t sgml -d lms.dsl index.sgml
	if [ $? -ne 0 ]
	    then exit 1
	fi
	mv ./*.html ../html/
	exit 0
    ;;

    'txt')	####### sgml -> text #######################
	jade -t sgml -V nochunks -d lms.dsl index.sgml > ../README.html
	if [ $? -ne 0 ]
	    then exit 1
	fi
#	elinks -dump ../README.html > ../README
	lynx -dump ../README.html -display_charset=ISO-8859-2 -raw -nolist -dont_wrap_pre > ../README
        if [ $? -ne 0 ]
	    then
	    exit 2
	fi
	exit 0
    ;;
    
    *)
	echo -e "$0: Brak parametru.\nSpos�b u�ycia: runme.sh html|txt"
    ;;
esac

