#!/bin/bash

#
#  Script makes list of strings for translation
#

echo "Parsing templates"
for FILENAME in `ls ../templates/*.html`
do
    echo -n "$FILENAME... "
#    cat ../templates/$FILENAME | sed -r -e 's/(\/t\})/\1\n/g' | sed -r -n -e 's/.*\{t[^}]*\}([^{]*)\{\/t}.*/\1/gp' -e 's/.*text=\"([^"]*)\".*/\1/gp' >> tmp_strings
    cat $FILENAME | perl -pe 's/(\/t\})/$1\n/g' | perl -ne 'print if s/.*\{t[^}]*\}([^{]*)\{\/t}.*/$1/ or s/.*text=\"(.*?[^\\])\".*/$1/' >> tmp_strings
    echo "done."
done

echo "Parsing modules"
for FILENAME in `ls ../modules/*.php`
do
    echo -n "$FILENAME... "
    cat $FILENAME | grep trans | perl -ne 'print if s/.*trans\(\x27(.*?[^\\])\x27.*/$1/' >> tmp_strings
    echo "done."
done
for FILENAME in `ls ../lib/*.php | grep -v CVS`
do
    echo -n "$FILENAME... "
    cat $FILENAME | grep trans | perl -ne 'print if s/.*trans\(\x27(.*?[^\\])\x27.*/$1/' >> tmp_strings
    echo "done."
done

echo -n "Sorting and removing duplicated lines..."
sort tmp_strings | uniq > strings
echo "done."

echo -n "Finished. Lines number: "
cat strings | wc -l
rm tmp_strings





