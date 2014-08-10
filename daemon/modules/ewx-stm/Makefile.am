if LIBSNMP
pkglib_LTLIBRARIES = ewx-stm.la
ewx_stm_la_SOURCES = ewx-stm.c
ewx_stm_la_LDFLAGS = -no-undefined -module -avoid-version -lm @SNMP_LDFLAGS@
ewx_stm_la_CFLAGS = @SNMP_CFLAGS@
endif
