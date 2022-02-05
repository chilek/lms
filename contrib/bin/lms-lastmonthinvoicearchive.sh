#!/bin/bash
#Script archives invoices from last month - it's ready to put into crontab (just fill COMPANYNAME,MAILTO,LMSDIR variables)
#Skrypt archiwizuje faktury z poprzedniego miesiąca - gotowy do wrzucenia do cron'a (zaraz po ustawieniu zmiennych COMPANYNAME,MAILTO,LMSDIR)

COMPANYNAME='FIRMA1'
MAILTO=''
LMSDIR='/var/www/html/lms/'

LASTDAYOFPREVIOUSMONTH=`date -d "$(date +%Y-%m-01) -1 day" +%d`
PREVIOUSMONTH=`date -d "$(date +%Y-%m-01) -1 day" +%m`
SELECTEDYEAR=`date -d "$(date +%Y-%m-01) -1 day" +%Y`
LOGFILE="/tmp/log-`date +%s`"

for i in `seq 1 ${LASTDAYOFPREVIOUSMONTH}`
do
	${LMSDIR}/bin/lms-sendinvoices.php --archive --fakedate="${SELECTEDYEAR}/${PREVIOUSMONTH}/${i}" &>>${LOGFILE}
done

if [ -s ${LOGFILE} ] && [ -n ${MAILTO} ]
then
	( cat ${LOGFILE} | mail -s "[${COMPANYNAME}] Raport archiwizacji faktur" ${MAILTO} ) && rm -f ${LOGFILE}
fi
