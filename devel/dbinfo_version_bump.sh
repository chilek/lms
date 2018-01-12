#!/bin/bash

PATH="/bin:/sbin:/usr/bin:/usr/sbin"

if [ $# -ne 1 ]; then
	echo "Target db schema version is required!"
	exit 1
fi

if ! echo $1 |grep -q "^[0-9]\{10\}$" 2>/dev/null; then
	echo "Invalid db schema version format!"
	exit 2
fi

CWD="`dirname $(readlink -f $0)`"
LMSDIR="${CWD}/.."
schemafile=${LMSDIR}/doc/lms.pgsql
currentversion=`grep "INSERT INTO dbinfo" $schemafile |sed -e "s/^.\+'\([0-9]\+\)'.\+$/\1/g"`

if [ $1 -le $currentversion ]; then
	echo "Target db schema version should be greater than current db schema version!"
	echo "Current db schema version: $currentversion"
	exit 3
fi

sed -i -e "s/^INSERT INTO dbinfo\(.\+\)'\([0-9]\+\)'\(.\+\)$/INSERT INTO dbinfo\1'$1'\3/g" ${LMSDIR}/doc/lms.{mysql,pgsql}

sed -i -e "s/^define('DBVERSION', '\([0-9]\+\)');/define('DBVERSION', '$1');/g" ${LMSDIR}/lib/LMSDB_common.class.php

for dbdriver in mysql postgres; do
	if [ ! -f "${LMSDIR}/lib/upgradedb/${dbdriver}.$1.php" ]; then
		sed -e "s/%version%/$1/g" ${CWD}/upgradedb-template.php >${LMSDIR}/lib/upgradedb/${dbdriver}.$1.php
	fi
done
