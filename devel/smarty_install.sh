#!/bin/bash
#
# Smarty templates library quick installation (with sources download)
#

SMARTYVER="3.1.17"

set -e

LIB_DIR="`dirname $0`/../lib/"
TMP=`mktemp -d`

# download
echo -n "Downloading Smarty sources... "
wget -q -O $TMP/Smarty-$SMARTYVER.tar.gz  http://www.smarty.net/files/Smarty-$SMARTYVER.tar.gz
echo "done."

# extracting package
echo -n "Extracting... "
tar -C $TMP -xzf $TMP/Smarty-$SMARTYVER.tar.gz
echo "done."

# merging
echo -n "Merging... "
cp -r $TMP/Smarty-$SMARTYVER/libs/*         $LIB_DIR/Smarty/
cp -r $TMP/Smarty-$SMARTYVER/libs/plugins/* $LIB_DIR/Smarty/plugins/
echo "done."


# cleanup
echo -n "Cleaning up... " 
rm -Rf $TMP/Smarty-$SMARTYVER $TMP/Smarty-$SMARTYVER.tar.gz
rmdir $TMP
echo "done."
cd ../devel

exit;
