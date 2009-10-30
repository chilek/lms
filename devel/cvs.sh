#!/bin/bash
# $Id$
#
# Pokazuje przez less'a diffy wszystkich plików które zmienione s± i je commituje
#
# Added comment

export CVSEDITOR="vim"
rm -v `find .|grep bak$`
rm -v `find .|grep "~$"`
for i in `cvs up|grep ^M|cut -d\  -f2-`
do
	cvs diff -u $i|less
#	cvs ci $i
done
