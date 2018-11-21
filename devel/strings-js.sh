#!/bin/bash

if [ ! -d ../img/locale ]; then
	mkdir -p ../img/locale
fi

for file in $(find ../lib/locale -type f -name 'strings.php'); do
	lang=$(echo ${file} |gawk -F'/' '{print $(NF-1);}')
	grep -v '\(^<\|>$\)' $file >../img/locale/${lang}.js
done
