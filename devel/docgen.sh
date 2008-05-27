#!/bin/bash
#
# Script generates documentation in different formats
# (html, txt) from source sgml files
#
# Programs required:
# -sgml-tools
# -openjade
# -lynx

cd ../doc/sgml

generate_html()
{
	jade -t sgml -d lms.dsl index.sgml
	if [ $? -ne 0 ]
		then exit 1
	fi

	mv ./*.html ../html/en/
}

generate_txt()
{
	jade -t sgml -V nochunks -d lms.dsl index.sgml > ../README.html
	if [ $? -ne 0 ]
		then exit 1
	fi
	
	lynx -dump ../README.html -display_charset=ISO-8859-1 -raw -nolist -dont_wrap_pre > ../README
	if [ $? -ne 0 ]
		then exit 2
	fi
	rm ../README.html
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
	echo -e "$0: Lost option.\nUsage: docgen.sh html|txt|all"
    ;;
esac

