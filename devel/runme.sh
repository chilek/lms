#!/bin/bash
#
# Skrypt s�u�y do tworzenia dokumentacji w r�nych formatach
# (html, txt) na podstawie �r�d�owych plik�w sgml
# Przed u�yciem nale�y zainstalowa� w systemie sgml-tools
# z openjade oraz elinks (tylko dla formatu txt)
#

cd doc/sgml

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
	jade -t sgml -V nochunks -d lms.dsl index.sgml > index.html
	if [ $? -ne 0 ]
	    then exit 1
	fi
	elinks -dump index.html > ../txt/manual
        if [$? -ne 0 ]
	    then
	    rm index.html
	    exit 2
	fi
	rm index.html
	exit 0
    ;;
    
    *)
	echo -e "runme.sh: Brak parametru.\nSpos�b u�ycia: runme.sh html|txt"
    ;;
esac
