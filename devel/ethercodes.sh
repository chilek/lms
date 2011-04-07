#!/bin/bash
# $Id$
#
# Aktualizuje lib/ethercodes.txt
#

wget http://standards.ieee.org/develop/regauth/oui/oui.txt

if [ ! -e oui.txt ]; then
	echo Brak pliku oui.txt
	exit 1;
fi

grep "(hex)" oui.txt > temp.txt
iconv --from iso-8859-1 --to utf-8 < temp.txt > out.txt
awk '{print substr($0,1,8) ":" substr($0,19)}' < out.txt > ../lib/ethercodes.txt

rm -f temp.txt
rm -f oui.txt
rm -f out.txt

cvs commit ../lib/ethercodes.txt