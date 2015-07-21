AUTOMAKE_OPTIONS = foreign
ACLOCAL_AMFLAGS = -I m4
bin_PROGRAMS = lmsd

SUBDIRS=modules dbdrivers

lmsd_SOURCES=util.c cron/cronparse.c cron/cron.c config/dictionary.c config/config.c lmsd.c
lmsd_CFLAGS=@LMSD_LIB_DIR@ @LMSD_DEFAULT_DRIVER@ @LMS_CONF_DIR@
lmsd_LDFLAGS=-rdynamic
