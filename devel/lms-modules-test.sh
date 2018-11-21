#!/bin/bash
#
file='lms-links-test.modules';
tmpfile='lms-links-temp'
usr='lmsuser';
pwd='lmspassword';
url='http://adresip'

grep 'link' ../lib/menu.php | grep -v ui_lang | cut -d"'" -f4 | sort | uniq > $file

for i in `cat $file`
do
	fullurl="$url/$i&override=1&loginform[login]=${usr}&loginform[pwd]=${pwd}";
#	echo $fullurl;
	links -dump $fullurl > $tmpfile;
done

rm $file;
rm $tmpfile;
