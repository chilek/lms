if PGSQL
pkglib_LTLIBRARIES = pgsql.la
pgsql_la_SOURCES = pgsql.c
pgsql_la_LDFLAGS = -no-undefined -module -avoid-version @PGSQL_LDFLAGS@
pgsql_la_CPPFLAGS = @PGSQL_CPPFLAGS@
endif
