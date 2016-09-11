#!/bin/bash
# $Id$
#
# Aktualizuje lib/ethercodes.txt
#

cd "$(dirname $(readlink -f $0))"

wget http://standards.ieee.org/develop/regauth/oui/oui.txt

if [ ! -e oui.txt ]; then
	echo Brak pliku oui.txt
	exit 1
fi

grep "(hex)" oui.txt > temp.txt
iconv --from iso-8859-1 --to utf-8 < temp.txt > out.txt
#awk '{print $1 ":" $3}' < out.txt > ../lib/ethercodes.txt
awk '{printf("%s:", $1, ":"); for (i=3; i<=NF; i++) printf((i == 3 ? "%s" : " %s"), $i); printf("\n"); }' <out.txt >../lib/ethercodes.txt

rm -f temp.txt
rm -f oui.txt
rm -f out.txt

git commit -a -m "bumped ethercodes.txt"
