#!/bin/bash
lmscashimportscript='/var/www/html/lms/bin/lms-cashimport.php'
host='ftp://fajnybank.pl';
login='uzytkownik';
pass='bardzotajnehaslo';
date=`date +%F`;
import_dir=""
backup_dir="/importy";
backup_subdirname="ftpimport-$date"
trap 'if [ -n "$import_dir" ] && [ -d "$import_dir" ]; then rm -rf "$import_dir"; fi' EXIT

function check_dependencies {
	if ! which find > /dev/null;
	then
		echo 'Brak pakietu findutils';
		exit 1;
	fi
	if ! which lftp > /dev/null;
	then
		echo 'Brak pakietu lftp';
		exit 1;
	fi

	if [ -n "$backup_dir" ]
	then
		mkdir -p "$backup_dir"
	else
		echo "Uzupełnij zmienna backup_dir!"
		exit 1;
	fi

	import_dir=$(mktemp -d /tmp/import-XXXXXX) || exit 1
}

function get_payments {
	echo "Pobieram raporty z Banku";
	lftp "ftp://$login:$pass@${host#ftp://}" -e "mget -O '$import_dir' *.txt && mrm *.txt; bye"

	files_count=`find "$import_dir" -type f | wc -l`

	if [ "$files_count" -ne 0 ]
	then
		echo "Pobrano $files_count raport(y)";
		mv "$import_dir" "$backup_dir"/"$backup_subdirname"
	else
		rm -r "$import_dir"
	fi
}

function import_payments {
	echo "Wykonuję import plikow do bazy";
	find "$backup_dir" -type f -name '*txt' -exec "$lmscashimportscript" --import-file {} \;
	find "$backup_dir" -type f -name '*txt' -exec mv {} {}_imported \;
}

check_dependencies;
get_payments;
import_payments;
