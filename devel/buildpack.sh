#!/bin/bash
#
# $Id$
#
# Bardzo g³upi skrypt do budowania paczek z LMS'em
#

WORKDIR=`pwd`
NOTDISTRIB="devel .project modules/core modules/mailing modules/auth modules/traffic modules/users lib/ExecStack.class.php"
SMARTYVER="2.6.16"

echo -ne "Katalog tmp? [$TMPDIR]: "
read TEMPDIR
if [ -z "$TEMPDIR" ]; then
	if [ -z "$TMPDIR" ]; then
		echo "Muszê mieæ jaki¶ katalog tymczasowy..."
		exit
	else
		TEMPDIR=$TMPDIR
	fi
fi

echo -ne "Dobra, a która wersja LMS?: "
read LMSVER
if [ -z "$LMSVER" ]; then
	echo "Nie ma mocnych ;) Bez tego nie ruszymy."
	exit
fi

echo -ne "TAG z CVS'a?: "
read CVSTAG
if [ -z "$CVSTAG" ]; then
	echo "Nie ma mocnych ;) Bez tego nie ruszymy."
	exit
fi

echo -ne "Nazwa kodowa?: "
read CODENAME
if [ -z "$CODENAME" ]; then
	echo "Nie ma mocnych ;) Bez tego nie ruszymy."
	exit
fi

# pobieramy LMSa
X=$RANDOM
mkdir -p $TEMPDIR/$X
wget --proxy=off "http://cvs.lms.org.pl/viewcvs.cgi/lms/lms.tar.gz?tarball=1&only_with_tag=${CVSTAG}" -O $TEMPDIR/$X/lms.tar.gz
umask 022
cd $TEMPDIR/$X/
# ropakowujemy
tar -xzf lms.tar.gz
# ustawiamy prawa do plikÃ³w/katalogÃ³w...
chmod 777 lms/{templates_c,backups,documents}
# i datÄ™, zepsutÄ… przez ViewCVS
touch `find . -type d`    
cd lms
# usuwamy deweloperski stuff
rm -Rf $NOTDISTRIB
# podmieniamy numerki wersji
grep -air '1\.9-cvs' .|cut -d: -f1|sort|uniq|xargs perl -pi -e "s/1\.9-cvs/$LMSVER $CODENAME/g"
chmod 777 templates_c backups documents
cd lib
#pobieramy Smarty i wlaczamy do paczki LMSa
wget http://smarty.php.net/distributions/Smarty-$SMARTYVER.tar.gz
tar -xzf Smarty-$SMARTYVER.tar.gz
mv Smarty-$SMARTYVER/libs/* Smarty/
mv Smarty-$SMARTYVER/libs/plugins/* Smarty/plugins/
rm -Rf Smarty-$SMARTYVER Smarty-$SMARTYVER.tar.gz
cd ../../
tar -czf $WORKDIR/lms-$LMSVER.tar.gz lms
cd $WORKDIR
echo -ne "Aby posprz±taæ, wykonaj (ja nie bêdê eremefowa³ sam):\nrm -Rf $TEMPDIR/$X\n"
