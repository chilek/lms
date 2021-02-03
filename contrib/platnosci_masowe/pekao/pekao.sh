#!/bin/bash

# Skrypt importuje za pierwszym razem wszystkie raporty, przy kolejnych tylko nowe

basedir=/home/pekao/
company=firma
pass=haslo
curl='curl -s -XPOST --key $basedir/$company.key --cert $basedir/$company.crt https://www.cm.pekao.com.pl/dokumenty/remote/get.hdb'

# Sciezka /HOME/A/ zawiera pierwsza litere nazwy firmy, nalezy ja zmodyfikowac odpowiednio

for csv in `$curl -d "PATH=/HOME/A/$company/&PASS=$pass" | sed 's/\r$//' | sort `; do
  if [[ $csv == *".csv"* ]]; then
    if [[ ! -f $basedir$csv ]]; then
      echo Pobieram $csv
      mkdir -p `dirname $basedir$csv`
      $curl -d "PATH=$csv&PASS=$pass" -o $basedir$csv
    fi
  fi
done
