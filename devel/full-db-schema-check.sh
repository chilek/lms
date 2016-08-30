#!/bin/bash

PATH="/bin:/sbin:/usr/bin:/usr/sbin"
LOG_FILE="/tmp/full-db-schema-check.log"
DB_FILE="`dirname $(readlink -f $0)`/../doc/lms"

export LANG="en_US.UTF-8"

if [ $# -ne 1 ]; then
	echo "Syntax error!"
	exit 1
fi

if [ ! -f $1 ]; then
	echo "File $1 does not exist!"
	exit 2
fi

. "$1"

if [ "${DB_TYPE}" = "pgsql" ]; then
	su postgres -c "dropdb ${DB_NAME};" &>/dev/null
	su postgres -c "createdb -O ${DB_USER} ${DB_NAME};" &>/dev/null || {
		echo "Database creation error!"
		exit 3
	}

	cat ${DB_FILE}.pgsql \
		|su postgres -c "psql ${DB_NAME}" 2>&1 >/dev/null \
		|grep -v "^ERROR:\s\+current transaction is aborted" |grep "^ERROR" \
		>${LOG_FILE}

	if [ -s ${LOG_FILE} ]; then
		cat ${LOG_FILE} |mail -s "[full-db-schema-check] pgsql errors" ${MAILTO}
	fi
fi

if [ "${DB_TYPE}" = "mysql" ]; then
	mysql -u${DB_USER} -p${DB_PASSWORD} mysql -e "DROP DATABASE \`${DB_NAME}\`;" &>/dev/null
	mysql -u${DB_USER} -p${DB_PASSWORD} mysql -e "CREATE DATABASE \`${DB_NAME}\`;" &>/dev/null || {
		echo "Database creation error!"
		exit 4
	}

	cat ${DB_FILE}.mysql \
		|mysql -u${DB_USER} -p${DB_PASSWORD} ${DB_NAME} 2>&1 >/dev/null \
		|grep "^ERROR" >${LOG_FILE}

	if [ -s ${LOG_FILE} ]; then
		cat ${LOG_FILE} |mail -s "[full-db-schema-check] mysql errors" ${MAILTO}
	fi
fi

rm -f ${LOG_FILE}
