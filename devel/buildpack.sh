#!/bin/bash
#
# $Id$
#
# Bardzo g�upi skrypt do budowania paczek z LMS'em
#

WORKDIR=`pwd`
NOTDISTRIB="devel .project modules/core modules/mailing modules/auth modules/traffic modules/users lib/ExecStack.class.php"
SMARTYVER="2.6.14"

echo -ne "Katalog tmp? [$TMPDIR]: "
read TEMPDIR
if [ -z "$TEMPDIR" ]; then
	if [ -z "$TMPDIR" ]; then
		echo "Musz� mie� jaki� katalog tymczasowy..."
		exit
	else
		TEMPDIR=$TMPDIR
	fi
fi

echo -ne "Dobra, a kt�ra wersja LMS?: "
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
chmod 777 lms/{templates_c,backups,documents}
touch `find . -type d`    
cd lms
rm -Rf $NOTDISTRIB
grep -air '1\.9-cvs' .|cut -d: -f1|sort|uniq|xargs perl -pi -e "s/1\.9-cvs/$LMSVER $CODENAME/g"
chmod 777 templates_c backups documents
cd ..
tar -czf $WORKDIR/lms-$LMSVER.tar.gz lms
cd lms/lib
wget http://smarty.php.net/distributions/Smarty-$SMARTYVER.tar.gz
tar -xzf Smarty-$SMARTYVER.tar.gz
mv Smarty-$SMARTYVER/libs Smarty
rm -Rf Smarty-$SMARTYVER Smarty-$SMARTYVER.tar.gz
cd ../
rm -Rf $NOTDISTRIB
cd ../
tar -czf $WORKDIR/lms-$LMSVER+libs.tar.gz lms
cd $WORKDIR
echo -ne "Aby posprz�ta�, wykonaj (ja nie b�d� eremefowa� sam):\nrm -Rf $TEMPDIR/$X\n"
