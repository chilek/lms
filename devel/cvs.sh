#!/bin/bash
# $Id$
#
# Pokazuje przez less'a diffy wszystkich plików które zmienione s± i je commituje
#
# 211Added comment11

export CVSEDITOR="vim"
rm -v `find .|grep bak$`
rm -v `find .|grep "~$"`
for i in `cvs up|grep ^M|cut -d\  -f2-`
do
	cvs diff -u $i|less
	cvs ci $i
done
