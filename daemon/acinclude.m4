#####################
# Locate PostgreSQL #
#####################
AC_DEFUN([LOCATE_POSTGRESQL],
[
    # Try to locate postgresql
    AC_PATH_PROGS(PG_CONFIG, pg_config)

    # If provided path try to use it
    AC_ARG_WITH(pgsql, AS_HELP_STRING([--with-pgsql=DIR], [enables use of PostgreSQL database (conflits with mysql, autodetection is run if DIR is not present)]),
    [
        if test "$withval" != "yes"  ;  then
            PG_CONFIG="$withval"
            if test ! -f "${PG_CONFIG}" ; then
                AC_MSG_ERROR([Could not find your PostgreSQL pg_config binary in ${PG_CONFIG}])
            fi
        else
            if test ! -f "${PG_CONFIG}" ; then
                AC_MSG_ERROR([Could not find your PostgreSQL installation (pg_config not detected). You might need to use the --with-pgsql=DIR configure option])
            fi
        fi
        with_pgsql=yes
    ])

    if test -f "${PG_CONFIG}" ; then
        PG_INCLUDE=`${PG_CONFIG} --includedir`
        if test -d "${PG_INCLUDE}" ; then
            have_pgsql=yes
        else
            AC_MSG_ERROR([pg_config pointed on non existent directory. Yourr PostgreSQL installation may be broken or you might need to use the --with-pgsql=DIR configure option to point right pg_config])
        fi
    fi
    AM_CONDITIONAL([PGSQL], [test x$with_pgsql = xyes])
])


#########################################
# Check for libpq libraries and headers #
#########################################
AC_DEFUN([SETUP_POSTGRESQL],
[

    PG_LIB=`${PG_CONFIG} --libdir`
    PGSQL_OLD_LDFLAGS="$LDFLAGS"
    PGSQL_OLD_CPPFLAGS="$CPPFLAGS"

    AC_LANG_SAVE
    AC_LANG_C
    AC_CHECK_LIB(ssl, SSL_library_init, [LIB_SSL=yes], [LIB_SSL=no])
    AC_LANG_RESTORE

    # Solaris needs -lssl for this test
    case "${host}" in
        *solaris*)
            if test "$LIB_SSL" = "yes" ; then
                LDFLAGS="$LDFLAGS -L${PG_LIB} -lssl"
            else
                LDFLAGS="$LDFLAGS -L${PG_LIB}"
            fi
            ;;
        *)
            LDFLAGS="$LDFLAGS -L${PG_LIB}"
            ;;
    esac

    AC_LANG_SAVE
    AC_LANG_C
    AC_CHECK_LIB(pq, PQexec, [PG_LIBPQ=yes], [PG_LIBPQ=no])
    AC_LANG_RESTORE

    AC_LANG_SAVE
    AC_LANG_C

    if test "$LIB_SSL" = "yes" ; then
        # Check for SSL support
        if test "$build_cpu-$build_vendor" = "powerpc-apple" -o "$build_cpu-$build_vendor" = "i386-apple" -o "$build_cpu-$build_vendor" = "i686-apple" ; then
            AC_MSG_CHECKING(for SSL_connect in -lpq)
            if test "$(otool -L ${PG_LIB}/libpq.?.dylib | grep -c libssl)" -gt 0 ; then
                AC_MSG_RESULT(present)
                PG_SSL="yes"
            else
                AC_MSG_RESULT(not present)
                PG_SSL="no"
            fi
        else
            AC_CHECK_LIB(pq, SSL_connect, [PG_SSL=yes], [PG_SSL=no])
        fi
    else
        PG_SSL="no"
    fi

    AC_LANG_RESTORE

    CPPFLAGS="$CPPFLAGS -I${PG_INCLUDE} -Idbdrivers/pgsql"
    PG_VERSION=`${PG_CONFIG} --version`

    if test "$build_os" = "mingw32" ; then
        CRYPTO_LIB=""
    else
        CRYPTO_LIB="-lcrypto"
    fi

    if test "$PG_SSL" = "yes" ; then
        LIBS="$LIBS -L${PG_LIB} -lpq"
    else
        LIBS="$LIBS -L${PG_LIB} $CRYPTO_LIB -lpq"
    fi

    AC_LANG_SAVE
    AC_LANG_C
    AC_CHECK_HEADER(libpq-fe.h, [PG_LIBPQFE=yes], [PG_LIBPQFE=no])
    AC_LANG_RESTORE

    if test "$PG_LIBPQ" = "yes" ; then
        AC_MSG_CHECKING(PostgreSQL)
        AC_MSG_RESULT(ok)
    else
        AC_MSG_CHECKING(PostgreSQL)
        AC_MSG_RESULT(failed)
        LDFLAGS="$PGSQL_OLD_LDFLAGS"
        CPPFLAGS="$PGSQL_OLD_CPPFLAGS"
        AC_MSG_ERROR([you must specify a valid PostgreSQL installation with --with-pgsql=DIR])
    fi

    if test "$PG_SSL" = "yes" ; then
        CPPFLAGS="$CPPFLAGS -DSSL"
    fi

    CFLAGS="-DUSE_PGSQL $CFLAGS"
    AC_SUBST([DBDRIVER], [pgsql])
    AC_SUBST(PG_CONFIG)

    lmsdefaultdriver=pgsql
])



################
# Locate MySQL #
################
AC_DEFUN([LOCATE_MYSQL],
[
    # first try to find MySQL config
    AC_PATH_PROGS(MYSQL_CONFIG, mysql_config)

    AC_ARG_WITH(mysql,
                AC_HELP_STRING([--with-mysql=PATH],[path to mysql_config binary [[/usr/bin/mysql_config]]]),
    [
        # We have to use MySQL
        if test "$withval" != "yes"  ;  then               # If provided path to MySQL use it
            if test -f $withval ; then
                MYSQL_CONFIG=$withval
            else
                AC_MSG_ERROR(Cannot find mysql_config binary in provided location ($withval).)
            fi
        else                                               # If check we have found mysql_config earlier
            if test ! -f "${MYSQL_CONFIG}" ; then
                AC_MSG_ERROR(Could not find mysql_config binary. Use --with-mysql= to specify non-default path to mysql_config binary.)
            fi
        fi
        with_mysql=yes

    ])

    if test -f "${MYSQL_CONFIG}"; then
        MYSQL_INC_DIR=`$MYSQL_CONFIG --variable=pkgincludedir`
        MYSQL_LIB_DIR=`$MYSQL_CONFIG --variable=pkglibdir`
        have_mysql=yes;
    fi

    AM_CONDITIONAL([MYSQL], [test x$with_mysql = xyes])

])



##################################################
# Check for libmysqlclient libraries and headers #
##################################################
AC_DEFUN([SETUP_MYSQL],
[
    for i in $MYSQL_DIR /usr/lib/x86_64-linux-gnu/ /usr /usr/local /opt /opt/mysql /usr/pkg /usr/local/mysql; do
        MYSQL_LIB_CHK($i/lib64)
        MYSQL_LIB_CHK($i/lib64/mysql)
        MYSQL_LIB_CHK($i/lib)
        MYSQL_LIB_CHK($i/lib/mysql)
    done

    if test -z "$MYSQL_LIB_DIR"; then
        AC_MSG_ERROR(Cannot find MySQL library)
    fi

    LDFLAGS="-L$MYSQL_LIB_DIR $LDFLAGS"
    CPPFLAGS="-I$MYSQL_INC_DIR $CPPFLAGS"

    AC_CHECK_LIB(mysqlclient_r ,mysql_init, LIBS="-lmysqlclient_r $LIBS",
      AC_MSG_ERROR([MySQL libraries not found])
    )

    CFLAGS="-DUSE_MYSQL $CFLAGS"
    AC_MSG_CHECKING(MySQL)
    AC_MSG_RESULT(ok)
    AC_SUBST([DBDRIVER], [mysql])

    lmsdefaultdriver=mysql
])

AC_DEFUN([MYSQL_LIB_CHK],
  [ str="$1/libmysqlclient_r.*"
    for j in `echo $str`; do
      if test -r $j; then
        MYSQL_LIB_DIR=$1
        break 2
      fi
    done
  ]
)


############################
# Locate Net-SNMP/UCD-SNMP #
############################
AC_DEFUN([LOCATE_SNMP],
[
    AC_ARG_WITH(snmp,
                AC_HELP_STRING([--with-snmp=DIR], [SNMP include base directory [[/usr/(local/)include]]]),
    [
        SNMP_DIR=$withval
        # Determine UCD or Net-SNMP installation paths (autodetection)
        for i in / /ucd-snmp /include/ucd-snmp; do
            test -f $SNMP_DIR/$i/snmp.h             && SNMP_INCDIR=$SNMP_DIR$i && break
        done

        for i in / /net-snmp /include/net-snmp; do
            test -f $SNMP_DIR/$i/net-snmp-config.h  && SNMP_INCDIR=$SNMP_DIR$i && break
        done
        if test -z "$SNMP_INCDIR" ; then
            AC_MSG_ERROR(Cannot find Net-SNMP header files under $SNMP_DIR)
        fi

        # Accomodate 64-Bit Libraries
        test -f $SNMP_DIR/lib64/libsnmp.a -o -f $SNMP_DIR/lib64/libsnmp.$ShLib       && SNMP_LIBDIR=$SNMP_DIR/lib64
        test -f $SNMP_DIR/lib64/libnetsnmp.a -o -f $SNMP_DIR/lib64/libnetsnmp.$ShLib && SNMP_LIBDIR=$SNMP_DIR/lib64

        if test -z "$SNMP_LIBDIR"; then
            # Accomodate 32-Bit Libraries
            test -f $SNMP_DIR/lib/libsnmp.a -o -f $SNMP_DIR/lib/libsnmp.$ShLib       && SNMP_LIBDIR=$SNMP_DIR/lib
            test -f $SNMP_DIR/lib/libnetsnmp.a -o -f $SNMP_DIR/lib/libnetsnmp.$ShLib && SNMP_LIBDIR=$SNMP_DIR/lib
        fi
        if test -z "$SNMP_INCDIR"; then
            AC_MSG_ERROR(Cannot find SNMP header files under $SNMP_DIR)
        fi
    ],
    [
        #--with-snmp not given, try to autodetect
        for i in /usr /usr/local /usr/include /usr/pkg/include /usr/local/include /opt /opt/ucd-snmp /opt/net-snmp /opt/snmp; do
            test -f $i/snmp.h                                  && SNMP_INCDIR=$i                       && break
            test -f $i/ucd-snmp/snmp.h                         && SNMP_INCDIR=$i/ucd-snmp              && break
            test -f $i/include/net-snmp/net-snmp-config.h      && SNMP_INCDIR=$i/include/net-snmp      && break
            test -f $i/net-snmp/net-snmp-config.h              && SNMP_INCDIR=$i/net-snmp              && break
            test -f $i/net-snmp/include/net-snmp-config.h      && SNMP_INCDIR=$i/net-snmp/include      && break
            test -f $i/snmp/snmp.h                             && SNMP_INCDIR=$i/snmp                  && break
            test -f $i/snmp/include/ucd-snmp/snmp.h            && SNMP_INCDIR=$i/snmp/include/ucd-snmp && break
            test -f $i/snmp/include/net-snmp/net-snmp-config.h && SNMP_INCDIR=$i/snmp/include/net-snmp && break
        done

        # Accomodate 64-Bit Libraries
        for i in /usr /usr/local /usr/pkg /usr/snmp /opt /opt/net-snmp /opt/ucd-snmp /opt/snmp /usr/local/snmp; do
            test -f $i/lib64/libsnmp.a -o -f $i/lib64/libsnmp.$ShLib       && SNMP_LIBDIR=$i/lib64 && break
            test -f $i/lib64/libnetsnmp.a -o -f $i/lib64/libnetsnmp.$ShLib && SNMP_LIBDIR=$i/lib64 && break
        done

        # Only check for 32 Bit libraries if the 64 bit are not found
        if test -z "$SNMP_LIBDIR"; then
            # Accomodate 32-Bit Libraries
            for i in /usr /usr/local /usr/pkg /usr/snmp /opt /opt/net-snmp /opt/ucd-snmp /opt/snmp /usr/local/snmp; do
                test -f $i/lib/libsnmp.a -o -f $i/lib/libsnmp.$ShLib       && SNMP_LIBDIR=$i/lib && break
                test -f $i/lib/libnetsnmp.a -o -f $i/lib/libnetsnmp.$ShLib && SNMP_LIBDIR=$i/lib && break
            done
        fi
        if test -z "$SNMP_INCDIR"; then
            AC_MSG_ERROR(Cannot find SNMP headers.  Use --with-snmp= to specify non-default path.)
        fi

    ])
])

#####################################################
# Check for Net-SNMP/UCD-SNMP libraries and headers #
#####################################################
AC_DEFUN([SETUP_SNMP],
[
    LDFLAGS="-L$SNMP_LIBDIR $LDFLAGS"
    CFLAGS="-I$SNMP_INCDIR -I$SNMP_INCDIR/.. $CFLAGS"

    # Net/UCD-SNMP includes v3 support and insists on crypto unless compiled --without-openssl
    AC_MSG_CHECKING([if UCD-SNMP needs crypto support])
    SNMP_SSL=no
    AC_TRY_COMPILE([#include <ucd-snmp-config.h>], [exit(USE_OPENSSL != 1);],
      [  AC_MSG_RESULT(yes)
         SNMP_SSL=yes
      ],
      AC_MSG_RESULT(no)
    )

    AC_MSG_CHECKING([if Net-SNMP needs crypto support])
    AC_TRY_COMPILE([#include <net-snmp-config.h>], [exit(USE_OPENSSL != 1);],
      [  AC_MSG_RESULT(yes)
         SNMP_SSL=yes
      ],
      AC_MSG_RESULT(no)
    )

    AC_CHECK_LIB(netsnmp, snmp_timeout,
      [ LIBS="-lnetsnmp $LIBS"
        AC_DEFINE(USE_NET_SNMP, 1, New Net SNMP Version)
        USE_NET_SNMP=yes ],
        [ AC_MSG_RESULT(Cannot find NET-SNMP libraries(snmp)... checking UCD-SNMP)
        USE_NET_SNMP=no ])

    if test "$USE_NET_SNMP" = "no"; then
      AC_CHECK_LIB(snmp, snmp_timeout,
        LIBS="-lsnmp $LIBS",
        AC_MSG_ERROR(Cannot find UCD-SNMP libraries(snmp)))
    fi
])

###########################################
# Check for libgadu libraries and headers #
###########################################
AC_DEFUN([SETUP_LIBGADU],
[
    AC_CHECK_HEADER(libgadu.h, [libgadu=yes], [libgadu=no])
    AM_CONDITIONAL([LIBGADU], [test x$libgadu = xyes])
])
