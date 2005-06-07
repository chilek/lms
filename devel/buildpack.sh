#!/bin/bash
#
# $Id$
#
# Bardzo g³upi skrypt do budowania paczek z LMS'em
#

WORKDIR=`pwd`
NOTDISTRIB="devel .project config_templates modules/confgen.php templates/confgen.html"
SMARTYVERSION=2.6.9

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

X=$RANDOM
mkdir -p $TEMPDIR/$X
wget --proxy=off "http://cvs.rulez.pl/viewcvs.cgi/lms/lms.tar.gz?tarball=1&only_with_tag=${CVSTAG}" -O $TEMPDIR/$X/lms.tar.gz
umask 022
cd $TEMPDIR/$X/
tar -xzf lms.tar.gz
chmod 777 lms/{templates_c,backups}
cd lms
rm -Rf $NOTDISTRIB
grep -air '1\.6-cvs' .|cut -d: -f1|sort|uniq|xargs perl -pi -e "s/1\.6-cvs/$LMSVER $CODENAME/g"
chmod 777 templates_c backups
cd ..
tar -czf $WORKDIR/lms-$LMSVER.tar.gz lms
cd lms/lib
wget http://smarty.php.net/distributions/Smarty-$SMARTYVERSION.tar.gz
tar -xzf Smarty-$SMARTYVERSION.tar.gz
mv Smarty-$SMARTYVERSION/libs Smarty
rm -Rf Smarty-$SMARTYVERSION Smarty-$SMARTYVERSION.tar.gz
cd ../
rm -Rf $NOTDISTRIB
cd ../
tar -czf $WORKDIR/lms-$LMSVER+libs.tar.gz lms
cd $WORKDIR
echo -ne "Aby posprz±taæ, wykonaj (ja nie bêdê eremefowa³ sam):\nrm -Rf $TEMPDIR/$X\n"
