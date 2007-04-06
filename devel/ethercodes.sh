#!/bin/bash
# $Id$
#
# Aktualizuje lib/ethercodes.txt
#

wget http://standards.ieee.org/regauth/oui/oui.txt

if [ ! -e oui.txt ]; then
	echo Brak pliku oui.txt
	exit 1;
fi

grep "(hex)" oui.txt > temp.txt
awk '{print substr($0,1,8) ":" substr($0,19)}' < temp.txt > ../lib/ethercodes.txt

rm -f temp.txt
rm -f oui.txt

cvs commit ../lib/ethercodes.txt