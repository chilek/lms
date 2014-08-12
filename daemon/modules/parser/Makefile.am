SUBDIRS = lib extensions

pkglib_LTLIBRARIES = parser.la
parser_la_SOURCES = parser.c lib/libtscript.a extensions/tscript_extensions.a
parser_la_LDFLAGS = -no-undefined -module -avoid-version -flm -lfl -lm
parser_la_CFLAGS = -Ilib
parser_la_LIBADD   = lib/libtscript.a extensions/libparser_extensions.a
