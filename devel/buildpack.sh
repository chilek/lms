#!/bin/bash
#
# $Id$
#
# Bardzo g�upi skrypt do budowania paczek z LMS'em
#

WORKDIR=`pwd` 
NOTDISTRIB="devel .project modules/core modules/mailing modules/auth modules/traffic modules/users lib/ExecStack.class.php"
SMARTYVER="2.6.26"

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

echo -ne "CVS Tag?: "
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
wget --proxy=off "http://cvs.lms.org.pl/viewvc/lms.tar.gz?view=tar&pathrev=${CVSTAG}" -O $TEMPDIR/$X/lms.tar.gz
umask 022
cd $TEMPDIR/$X/
# ropakowujemy
tar -xzf lms.tar.gz
# ustawiamy prawa do plików/katalogów...
chmod 777 lms/{templates_c,backups,documents}
# i datę, zepsutą przez ViewCVS
touch `find . -type d`    
cd lms
# usuwamy deweloperski stuff
rm -Rf $NOTDISTRIB
# podmieniamy numerki wersji
grep -air '1\.10-cvs' .|cut -d: -f1|sort|uniq|xargs perl -pi -e "s/1\.10-cvs/$LMSVER $CODENAME/g"
chmod 777 templates_c backups documents
cd lib
#pobieramy Smarty i wlaczamy do paczki LMSa
wget http://www.smarty.net/distributions/Smarty-$SMARTYVER.tar.gz
tar -xzf Smarty-$SMARTYVER.tar.gz
mv Smarty-$SMARTYVER/libs/* Smarty/
mv Smarty-$SMARTYVER/libs/plugins/* Smarty/plugins/
rm -Rf Smarty-$SMARTYVER Smarty-$SMARTYVER.tar.gz
cd ../../
tar -czf $WORKDIR/lms-$LMSVER.tar.gz lms
cd $WORKDIR
echo -ne "Do clenup (I'll don't do this):\nrm -Rf $TEMPDIR/$X\n"

