#!/bin/bash

PATH="/bin:/sbin:/usr/bin:/usr/sbin"
TMP_DIR="/tmp"
LOG_FILE="${TMP_DIR}/lesscpy.log"
MAILTO=""

which lesscpy &>/dev/null || {
	echo "Lesscpy utility not found"
	exit 1
}

CWD=`dirname $(readlink -f $0)`

lesscpy -o ${CWD}/../img ${CWD}/../img >/dev/null 2>${LOG_FILE}
if [ -s ${LOG_FILE} ]; then
	if [ ${MAILTO} ]; then
		cat ${LOG_FILE} |mail -s "[lesscpy] compilation errors" ${MAILTO}
	else
		cat ${LOG_FILE}
	fi
	rm -f ${LOG_FILE}
	exit 2
fi

rm -f ${LOG_FILE}
