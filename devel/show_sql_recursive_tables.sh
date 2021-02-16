#!/bin/bash

PATH="/bin:/sbin:/usr/bin:/usr/sbin"

CWD=$(dirname $(readlink -f $0))

gawk '
	/^CREATE[[:blank:]]+TABLE[[:blank:]]+/ {
		table=gensub("^CREATE[[:blank:]]+TABLE[[:blank:]]+([[:alnum:]]+).+", "\\1", "g");
	}
	/REFERENCES/ {
		if (table) {
			referenced_table=gensub("^.+REFERENCES[[:blank:]]+([[:alnum:]]+)[[:blank:]]+.+", "\\1", "g");
			if (table == referenced_table) {
				print table;
			}
		}
	}
' ${CWD}/../doc/lms.pgsql \
	|xargs |sed -e "s/ /', '/g" -e "s/^/'/g" -e "s/$/'/g" \
	|fold -w 80 -s |sed -e "s/\s\+$//g"


#	|sed -e "s/^CREATE\s\+TABLE\s\+//g" -e "s/\s\+(//g" |xargs \
#	|sed -e "s/ /', '/g" -e "s/^/'/g" -e "s/$/'/g" \
#	|fold -w 80 -s |sed -e "s/\s\+$//g"
