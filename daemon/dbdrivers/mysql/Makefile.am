if MYSQL
pkglib_LTLIBRARIES = mysql.la
mysql_la_SOURCES = mysql.c
mysql_la_LDFLAGS = -no-undefined -module -avoid-version @MYSQL_LDFLAGS@
mysql_la_CPPFLAGS = @MYSQL_CPPFLAGS@
endif
