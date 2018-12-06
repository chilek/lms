#!/bin/bash
#Skrypt is exporting invoices as PDF files to external FTP server (prefered use as crontab job), script defaults choose invoices from current month.
#Skrypt exportuje faktury jako pliki PDF na zewnetrzny FTP (do wrzucenia w crontaba), skrypt domyslnie zrzuca wszystkie faktury z obecnego miesiaca.

URL='https://adreslms.pl';

DB='nazwabazylms';
DBLOGIN='logindobazy'
DBPASSWD='haslodobazy'

LMSLOGIN='loginuzytkownikalms';
LMSPASSWORD='haslolms';

EXPORTDIR='/tmp/Faktury_export';
EXPORTFILELIST="$EXPORTDIR/export_file_list.csv";

INVOICESFROM=`date -d $(date +%Y%m01) +%s`
INVOICESTO="999999999999999999"

SQL="SELECT id FROM documents WHERE type=1 AND cdate>$INVOICESFROM AND cdate<$INVOICESTO";

FTPLOGIN='testtest'
FTPPASSWD='test123'
FTPHOST='ftp://archiwumfaktur.pl'

if [ ! -d $EXPORTDIR ]
then
	mkdir $EXPORTDIR
fi

function get_invoices_list {
	mysql --defaults-file=/etc/mysql/debian.cnf -e "$SQL" -N $DB | sed 's/\t/,/g' > $EXPORTFILELIST
	echo 'Ilość faktur do exportu:' `wc -l $EXPORTFILELIST | cut -f1 -d" "`
}

function generate_invoices {
	while read ID
	do
		wget "$URL/?m=invoice&override=1&loginform[login]=$LMSLOGIN&loginform[pwd]=$LMSPASSWORD&id=$ID" -O $EXPORTDIR/Faktura_$ID.pdf;
	done < $EXPORTFILELIST
	echo 'Ilość faktur wygenerowanych:' `ls -1 $EXPORTDIR/*.pdf | wc -l`
}

function upload_invoices {
	lftp -u$FTPLOGIN --password "$FTPPASSWD" $FTPHOST -e "lcd $EXPORTDIR; mput *.pdf; exit;";
	if [ $? -eq 0 ]
	then
		rm $EXPORTDIR/*.pdf;
	fi
}

get_invoices_list;
generate_invoices;
upload_invoices;

rm $EXPORTFILELIST;
