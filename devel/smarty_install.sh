#!/bin/bash
#
# Smarty templates library quick installation (with sources download)
#

set -e

LIB_DIR="$(dirname $0)/../lib/"
TMP=`mktemp -d`

# checking out latest smarty version
wget -q -O $TMP/tags https://api.github.com/repos/smarty-php/smarty/tags
LATEST_VERSION=$(grep "name" $TMP/tags |head -1 |awk '{print $2;}' |sed -e 's/[",]//g')
LATEST_VERSION="v3.1.29"
URL="https://github.com/smarty-php/smarty/archive/${LATEST_VERSION}.tar.gz"

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
if [ ! -d "${LIB_DIR}/Smarty" ]; then
    mkdir -p "${LIB_DIR}/Smarty"
fi
cp -r $TMP/${SMARTY_DIR}/libs/*		${LIB_DIR}/Smarty/
cp -r $TMP/${SMARTY_DIR}/libs/plugins/*	${LIB_DIR}/Smarty/plugins/
echo "done."

case $LATEST_VERSION in
	"v3.1.27")
		patch -p0 -d ${LIB_DIR}/Smarty <$(dirname $0)/smarty-3.1.27.patch
		;;
	"v3.1.29")
		patch -p0 -d ${LIB_DIR}/Smarty <$(dirname $0)/smarty-3.1.29.patch
		;;
esac

# cleanup
echo -n "Cleaning up... "
rm -Rf $TMP
echo "done."

exit
