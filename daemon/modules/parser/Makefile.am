SUBDIRS = lib extensions

pkglib_LTLIBRARIES = parser.la
parser_la_SOURCES = parser.c lib/libtscript.a extensions/tscript_extensions.a
parser_la_LDFLAGS = -no-undefined -module -avoid-version -flm -lfl
parser_la_CFLAGS = -Ilib
