#!/bin/bash
#
# Skrypt s³u¿y do tworzenia dokumentacji w ró¿nych formatach
# (html, txt) na podstawie ¼ród³owych plików sgml
#
# Wymagane programy:
# -sgml-tools
# -openjade
# -lynx
# -iconv

cd ../doc/sgml/pl

generate_html()
{
	jade -t sgml -d lms.dsl index.sgml
	if [ $? -ne 0 ]
		then exit 1
	fi

	mv ./*.html ../../html/pl/

	for i in ../../html/pl/*.html
	do
		iconv --from latin2 --to utf-8 < $i > $i.tmp
		mv -f $i.tmp $i
	done
}

generate_txt()
{
	jade -t sgml -V nochunks -d lms.dsl index.sgml > ../../README_pl.html.tmp
	if [ $? -ne 0 ]
		then exit 1
	fi
	iconv --from ISO-8859-2 --to utf-8 < ../../README_pl.html.tmp > ../../README_pl.html

	lynx -dump -display_charset=utf-8 -raw -nolist -dont_wrap_pre ../../README_pl.html > ../../README.pl
	if [ $? -ne 0 ]
		then exit 2
	fi

#	mv -f ../../README_pl.tmp ../../README.pl
	rm ../../README_pl.html.tmp
	rm ../../README_pl.html
}

case "$1" in

    'html')	####### sgml -> html #######################
	generate_html
    ;;

    'txt')	####### sgml -> text #######################
	generate_txt
    ;;

    'all')	####### sgml -> html & txt #################
	generate_html
	generate_txt
    ;;
        
    *)
	echo -e "$0: Brak parametru.\nSposób u¿ycia: docgen.sh html|txt"
    ;;
esac

