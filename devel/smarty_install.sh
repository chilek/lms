#!/bin/bash
#
# Smarty templates library quick installation (with sources download)
#

URL="http://www.smarty.net/files/Smarty-stable.tar.gz"

set -e

LIB_DIR="`dirname $0`/../lib/"
TMP=`mktemp -d`

# download
echo -n "Downloading Smarty sources... "
wget -q -O $TMP/Smarty.tar.gz "$URL"
echo "done."

# extracting package
echo -n "Extracting... "
tar -C $TMP -xzf $TMP/Smarty.tar.gz
echo "done."

# merging
echo -n "Merging... "
VER=`find $TMP -maxdepth 1 -mindepth 1 -type d |perl -p -i -e 's/^.*(\d\.\d\.\d+)$/$1/g'`
cp -r $TMP/Smarty-$VER/libs/*         $LIB_DIR/Smarty/
cp -r $TMP/Smarty-$VER/libs/plugins/* $LIB_DIR/Smarty/plugins/
echo "done."


# cleanup
echo -n "Cleaning up... " 
rm -Rf $TMP/Smarty-$VER $TMP/Smarty.tar.gz
rmdir $TMP
echo "done."

exit
