#!/bin/bash
# Dump the subject of all messages in the folder.
mailsrv='mail.server.pl';
login='login@domain.pl';
pass='veryhardpasswds';
flw='/tmp/imap_checkmail';
lmspath='/var/www/html/lms'

id=1;

while true;
do
        echo "Message ${id}" 1>/dev/null
        curl -s --insecure --url "imaps://$mailsrv/INBOX/;UID=${id}" --user "$login:$pass" -o "$flw" || exit

	if [ -e $flw ]
	then
		cat "$flw" | $lmspath/bin/lms-rtparser.php -s;
		rm $flw;
		curl --insecure --url "imaps://$mailsrv/INBOX" --user "$login:$pass" -X "MOVE $id Trash" || exit
	fi

        id=`expr $id + 1`
done
