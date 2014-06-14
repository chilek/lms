#                                               -*- Autoconf -*-
# Process this file with autoconf to produce a configure script.

AC_PREREQ([2.67])
AC_INIT([lmsd], [1.11-git], [http://bts.lms.org.pl/])
AC_CONFIG_MACRO_DIR([m4])
AC_CONFIG_SRCDIR([lmsd.c])
AM_INIT_AUTOMAKE(lmsd, 2.6)
AC_CONFIG_HEADERS([config.h])
AC_CANONICAL_HOST
AC_PREFIX_DEFAULT([/usr/local/lmsd])

AC_CONFIG_FILES(Makefile
                dbdrivers/Makefile
                dbdrivers/mysql/Makefile
                dbdrivers/pgsql/Makefile
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

# check lex
AC_PROG_LEX
if test x"$LEX" = "x:"; then
    AC_CHECK_PROG([FLEX_EXISTS], [flex], [yes], [no])
    if test x"$FLEX_EXISTS" != xyes; then
        AC_MSG_ERROR([[flex not found. Please install it.]])
    fi
fi

# check bison
AC_PROG_YACC
if test x"$YACC" = "xyacc"; then
    AC_CHECK_PROG([YACC_EXISTS], [yacc], [yes], [no])
    if test x"$YACC_EXISTS" != xyes; then
        AC_MSG_ERROR([[bison/byacc/yacc not found. Please install bison.]])
    fi
fi

# file - we don't use it but other tests do.
AC_CHECK_PROG(FILE,file,yes)
if test x"$FILE" != x"yes" ; then
    AC_MSG_ERROR([Program 'file' not found. Please install it.])
fi

# Check if gnu make is present
CHECK_GNU_MAKE

AC_PROG_CC
AC_PROG_LIBTOOL

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

# Second detected database will be used as default (LMS_DEFAULT_DRIVER).
AS_IF([test "x$with_pgsql" = "xyes" -o "x$have_pgsql" = "xyes" ], [SETUP_POSTGRESQL])
AS_IF([test "x$with_mysql" = "xyes" -o "x$have_mysql" = "xyes"],  [SETUP_MYSQL])
# Error when there is no database
AS_IF([test ! "x$have_mysql" = "xyes" -a ! "x$have_pgsql" = "xyes"], AC_MSG_ERROR([No database client libraries detected. Try to use --with-mysql and/or --with-pgsql options.]))

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
    AS_HELP_STRING([--enable-debug0], [SQL query logging (disabled)]),
    AC_DEFINE(DEBUG0, 1, SQL Query debugging))

AC_ARG_ENABLE([debug1],
    AS_HELP_STRING([--enable-debug1], [events logging (disabled)]),
    AC_DEFINE(DEBUG1, 1, events logging))

GET_REVISION
AC_DEFINE_UNQUOTED(LMSD_REVISION, ["$LMSD_REVISION"], Directore where lmsd should look for configuration - including lms.ini)

AC_SUBST([LMSD_LIB_DIR], ["-DLMS_LIB_DIR=\\\"$libdir/lmsd\\\""])
AC_SUBST([LMSD_DEFAULT_DRIVER], ["-DLMS_DEFAULT_DRIVER=\\\"$lmsdefaultdriver\\\""])
AC_SUBST([LMS_CONF_DIR], ["-DLMS_CONF_DIR=\\\"$sysconfdir/lms\\\""])

AC_MSG_NOTICE([**********************************])
AC_MSG_NOTICE([Host os: $host_os])


AC_OUTPUT
