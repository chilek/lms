#!/bin/bash
jade -t sgml -d lms.dsl index.sgml
mv ./*.html ../html/