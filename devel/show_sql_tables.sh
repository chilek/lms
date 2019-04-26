#!/bin/bash

PATH="/bin:/sbin:/usr/bin:/usr/sbin"

CWD=$(dirname $(readlink -f $0))

if [ $# -eq 0 -o "$1" = "-1" ]; then
	grep "^CREATE\s\+TABLE" ${CWD}/../doc/lms.pgsql \
		|sed -e "s/^CREATE\s\+TABLE\s\+//g" -e "s/\s\+(//g" |xargs \
		|sed -e "s/ /', '/g" -e "s/^/'/g" -e "s/$/'/g" \
		|fold -w 80 -s |sed -e "s/\s\+$//g"
elif [ $# -eq 1 -a "$1" = "-2" ]; then
	grep "^CREATE\s\+TABLE" ${CWD}/../doc/lms.pgsql \
		|sed -e "s/^CREATE\s\+TABLE\s\+/DROP TABLE IF EXISTS /g" -e "s/\s\+(/;/g" \
		|tac
fi
