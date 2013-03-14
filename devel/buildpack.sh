#!/bin/bash
#
# $Id$
#
# Bardzo g³upi skrypt do budowania paczek z LMS'em
#

WORKDIR=`pwd`
NOTDISTRIB="devel .project modules/core modules/mailing modules/auth modules/traffic modules/users lib/ExecStack.class.php"
SMARTYVER="3.1.13"

echo -ne "Katalog tmp? [$TMPDIR]: "
read TEMPDIR
if [ -z "$TEMPDIR" ]; then
	if [ -z "$TMPDIR" ]; then
		echo "You need some temp directory..."
		exit
	else
		TEMPDIR=$TMPDIR
	fi
fi

echo -ne "LMS version?: "
read LMSVER
if [ -z "$LMSVER" ]; then
	echo "No way, we can't go without this."
	exit
fi

echo -ne "GIT Tag?: "
read CVSTAG
if [ -z "$CVSTAG" ]; then
	echo "No way, we can't go without this."
	exit
fi

echo -ne "Codename?: "
read CODENAME
if [ -z "$CODENAME" ]; then
	echo "No way, we can't go without this."
	exit
fi

# pobieramy LMSa
X=$RANDOM
mkdir -p $TEMPDIR/$X
wget --proxy=off "https://github.com/lmsgit/lms/tarball/${CVSTAG}" -O $TEMPDIR/$X/lms.tar.gz
umask 022
cd $TEMPDIR/$X/
# ropakowujemy
tar -xzf lms.tar.gz
rm -f lms.tar.gz
mv lmsgit* lms
cd lms
# usuwamy deweloperski stuff
rm -Rf $NOTDISTRIB
# podmieniamy numerki wersji
grep -air '1\.11-cvs' .|cut -d: -f1|sort|uniq|xargs perl -pi -e "s/1\.11-cvs/$LMSVER $CODENAME/g"
chmod 777 templates_c backups documents userpanel/templates_c
cd lib
#pobieramy Smarty i wlaczamy do paczki LMSa
wget http://www.smarty.net/files/Smarty-$SMARTYVER.tar.gz
tar -xzf Smarty-$SMARTYVER.tar.gz
mv Smarty-$SMARTYVER/libs/* Smarty/
mv Smarty-$SMARTYVER/libs/plugins/* Smarty/plugins/
rm -Rf Smarty-$SMARTYVER Smarty-$SMARTYVER.tar.gz

# cleanup
cd ../..
tar -czf lms-$LMSVER.tar.gz lms
cd ..
echo -ne "Do clenup (I'll don't do this):\nrm -Rf $TEMPDIR/$X\n"
