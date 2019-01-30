#!/bin/bash

cwd=$(dirname $(readlink -f $0))

if [ ! -d ${cwd}/../js/locale ]; then
	mkdir -p ${cwd}/../js/locale
fi

for file in $(find ${cwd}/../lib/locale -type f -name 'strings.php'); do
	lang=$(echo ${file} |gawk -F'/' '{print $(NF-1);}')
	grep -v '\(^<\|>$\)' $file >${cwd}/../js/locale/${lang}.js
done
