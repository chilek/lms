#!/bin/bash
#
# Smarty templates library quick installation (with sources download)
#

SMARTYVER="2.6.19"

cd ../lib
# download
echo -n "Downloading Smarty sources... "
wget http://smarty.php.net/distributions/Smarty-$SMARTYVER.tar.gz
echo "done."

# extracting package
echo -n "Extracting... "
tar -xzf Smarty-$SMARTYVER.tar.gz
echo "done."

# merging
echo -n "Merging... "
mv Smarty-$SMARTYVER/libs/* Smarty/
mv Smarty-$SMARTYVER/libs/plugins/* Smarty/plugins/
echo "done."

# cleanup
echo -n "Cleaning up... " 
rm -Rf Smarty-$SMARTYVER Smarty-$SMARTYVER.tar.gz
echo "done."
cd ../devel
