#!/bin/bash
#
# $Id$
#
# Bardzo g³upi skrypt do budowania paczek z LMS'em
#

WORKDIR=`pwd`

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

echo -ne "TAG z CVS'a?: LMS_"
read CVSTAG
if [ -z "$CVSTAG" ]; then
	echo "Nie ma mocnych ;) Bez tego nie ruszymy."
	exit
fi

X=$RANDOM
mkdir -p $TEMPDIR/$X
wget --proxy=off "http://cvs.rulez.pl/viewcvs.cgi/lms/lms.tar.gz?tarball=1&only_with_tag=LMS_${CVSTAG}" -O $TEMPDIR/$X/lms.tar.gz
umask 022
cd $TEMPDIR/$X/
tar -xzf lms.tar.gz
chmod 777 lms/{templates_c,backups}
rm -Rf lms/devel
cd lms
rgrep -ir '1\.1-cvs' .|cut -d: -f1|sort|uniq|xargs perl -pi -e "s/1\.1-cvs/$LMSVER/g"
cd ..
tar -czf $WORKDIR/lms-$LMSVER.tar.gz lms
cd lms/lib
wget http://smarty.php.net/distributions/Smarty-2.5.0.tar.gz
tar -xzf Smarty-2.5.0.tar.gz
mv Smarty-2.5.0/libs Smarty
rm -Rf Smarty Smarty-2.5.0.tar.gz
cd ../..
tar -czf $WORKDIR/lms-$LMSVER+libs.tar.gz lms
cd $WORKDIR
echo -ne "Aby posprz±taæ, wykonaj (ja nie bêdê eremefowa³ sam):\nrm -Rf $TEMPDIR/$X\n"
