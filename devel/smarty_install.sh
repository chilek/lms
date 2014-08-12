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

SMARTY_DIR=$(find $TMP -maxdepth 1 -mindepth 1 -type d -exec basename {} \;)
cp -r $TMP/${SMARTY_DIR}/libs/*		${LIB_DIR}/Smarty/
cp -r $TMP/${SMARTY_DIR}/libs/plugins/*	${LIB_DIR}/Smarty/plugins/
echo "done."


# cleanup
echo -n "Cleaning up... "
rm -Rf $TMP
echo "done."

exit
