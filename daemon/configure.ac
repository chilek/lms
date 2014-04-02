#                                               -*- Autoconf -*-
# Process this file with autoconf to produce a configure script.

AC_PREREQ([2.67])
AC_INIT([lmsd], [1.11], [http://bts.lms.org.pl/])
AC_CONFIG_MACRO_DIR([m4])
AC_CONFIG_SRCDIR([lmsd.c])
AM_INIT_AUTOMAKE(lmsd, 2.6)
AC_CONFIG_HEADERS([config.h])
AC_CANONICAL_HOST
AC_PREFIX_DEFAULT([/usr/local/lmsd])

AC_CONFIG_FILES(Makefile
                modules/Makefile
                modules/parser/Makefile
                modules/parser/lib/Makefile
                modules/parser/extensions/Makefile
                modules/cutoff/Makefile
                modules/dhcp/Makefile
                modules/dns/Makefile
                modules/ethers/Makefile
                modules/ewx-pt/Makefile
                modules/ewx-stm-channels/Makefile
                modules/ewx-stm/Makefile
                modules/ggnotify/Makefile
                modules/hostfile/Makefile
                modules/notify/Makefile
                modules/oident/Makefile
                modules/payments/Makefile
                modules/pinger/Makefile
                modules/system/Makefile
                modules/tc/Makefile
                modules/tc-new/Makefile
                modules/traffic/Makefile
                )

# Checks for programs.
AC_PROG_CXX

AC_CHECK_PROG([FLEX], [flex lex], [flex])
AM_MISSING_PROG([LEX], [$FLEX])
AM_PROG_LEX

AC_CHECK_PROG([YACC], [yacc bison], [yacc])
AM_MISSING_PROG([YACC], [$YACC])
AC_PROG_YACC

AC_PROG_CC
AC_PROG_LIBTOOL

# Checks for libraries.
#AC_CHECK_LIB([pq], [PQconnectdb], [have_pgsql=yes])
#AC_CHECK_LIB([mysqlclient], [mysql_real_connect],
#  [have_mysql=yes
#   LIBS="-lmysqlclient $LIBS"])


# Checks for header files.
AC_CHECK_HEADERS([arpa/inet.h fcntl.h limits.h locale.h netinet/in.h stdlib.h string.h sys/ioctl.h sys/socket.h sys/time.h syslog.h unistd.h])

# Checks for typedefs, structures, and compiler characteristics.
AC_HEADER_STDBOOL
AC_TYPE_SIZE_T
AC_TYPE_SSIZE_T

# Checks for library functions.
AC_FUNC_FORK
AC_FUNC_MALLOC
AC_FUNC_MKTIME
AC_FUNC_REALLOC
AC_CHECK_FUNCS([getdelim gethostname gettimeofday inet_ntoa memset pow regcomp select setlocale socket strchr strdup strerror strstr strtol strtoul])

LOCATE_MYSQL
LOCATE_POSTGRESQL
LOCATE_SNMP

AS_IF([test "x$with_mysql" = "xyes" && test "x$with_pgsql" = "xyes"], AC_MSG_ERROR([You can use mysql or postgresql. You can not use both]),
      [test "x$with_mysql" = "xyes" ], [SETUP_MYSQL],
      [test "x$with_pgsql" = "xyes" ], [SETUP_POSTGRESQL],
      [test "x$have_mysql" = "xyes" ], [SETUP_MYSQL],
      [test "x$have_pgsql" = "xyes" ], [SETUP_POSTGRESQL],
      AC_MSG_ERROR([Database include files not found. Try to use --with-mysql or --with-pgsql])
)

SETUP_SNMP
SETUP_LIBGADU

case "$host_os" in
        linux*)
                OS="linux"
                LIBS+=" -ldl"
                CFLAGS+=" -fPIC -DGNU"
                ;;
        CYGWIN* | Cygwin* | cygwin* )
                OS="cygwin"
                LIBS+=" -ldl -lz"
#                LMSDBIN="lmsd.exe"
                ;;
        FreeBSD )
                OS="freebsd"
                CFLAGS+=" -fPIC"
                ;;
        *)
                ;;
esac

AM_CONDITIONAL([BUILD_PINGER], [test x$OS = xlinux])


AC_ARG_ENABLE([debug0],
    AS_HELP_STRING([--enable-debug0], [SQL query logging (disabled)]))
AC_ARG_ENABLE([debug1],
    AS_HELP_STRING([--enable-debug1], [events logging (disabled)]))
AC_ARG_WITH(lmslibdir,
    [AC_HELP_STRING([--with-lmslibdir=DIR], [sets location of target LMS modules (PREFIX/lms/lib)])],
    [lmslibdir="$withval"],
    [lmslibdir=$ac_default_prefix/lib/lms]
)
AC_SUBST(lmslibdir)

CFLAGS="$CFLAGS -DLMS_LIB_DIR=\\\"$lmslibdir\\\""

AC_MSG_NOTICE([**********************************])
AC_MSG_NOTICE([Host os: $host_os])


AC_OUTPUT
