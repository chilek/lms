#!/bin/bash
#
# Smarty templates library quick installation (with sources download)
#

SMARTYVER="3.1.15"

cd ../lib
# download
echo -n "Downloading Smarty sources... "
wget http://www.smarty.net/files/Smarty-$SMARTYVER.tar.gz
echo "done."

# extracting package
echo -n "Extracting... "
tar -xzf Smarty-$SMARTYVER.tar.gz
echo "done."

# merging
echo -n "Merging... "
cp -r Smarty-$SMARTYVER/libs/* Smarty/
cp -r Smarty-$SMARTYVER/libs/plugins/* Smarty/plugins/
echo "done."

# cleanup
echo -n "Cleaning up... " 
rm -Rf Smarty-$SMARTYVER Smarty-$SMARTYVER.tar.gz
echo "done."
cd ../devel
