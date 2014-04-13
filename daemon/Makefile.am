AUTOMAKE_OPTIONS = foreign
ACLOCAL_AMFLAGS = -I m4
bin_PROGRAMS = lmsd

SUBDIRS=modules

lmsd_SOURCES=util.c cron/cronparse.c cron/cron.c config/dictionary.c config/config.c lmsd.c dbdrivers/@DBDRIVER@/db.c
