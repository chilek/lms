#!/bin/bash
#Konwersja sgml -> html
jade -t sgml -d lms.dsl index.sgml
#Przeniesienie do katalogu z dokumentacj±
mv ./*.html ../html/