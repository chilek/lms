#####################
# Locate PostgreSQL #
#####################
AC_DEFUN([LOCATE_POSTGRESQL],
[
    # Try to locate postgresql
    AC_PATH_PROGS(PG_CONFIG, pg_config)

    # If provided path try to use it
    AC_ARG_WITH(pgsql, AS_HELP_STRING([--with-pgsql=PATH], [path to pg_config binary (autodetection is run if PATH is not present)]),
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
            AC_MSG_ERROR([pg_config pointed on non existent directory. Your PostgreSQL installation may be broken or you might need to use the --with-pgsql=PATH configure option to point right pg_config])
        fi
    fi

    AM_CONDITIONAL([PGSQL], [test x$have_pgsql = xyes])
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
            PGSQL_LDFLAGS="$PGSQL_LDFLAGS -L${PG_LIB}";
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
    PGSQL_CPPFLAGS="$PGLSQL_CPPFLAGS -I${PG_INCLUDE}"
    PG_VERSION=`${PG_CONFIG} --version`

    if test "$build_os" = "mingw32" ; then
        CRYPTO_LIB=""
    else
        CRYPTO_LIB="-lcrypto"
    fi

    if test "$PG_SSL" = "yes" ; then
        PGSQL_LDFLAGS="$PGSQL_LDFLAGS -L${PG_LIB} -lpq"
    else
        PGSQL_LDFLAGS="$PGSQL_LDFLAGS -L${PG_LIB} $CRYPTO_LIB -lpq"
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
        AC_MSG_ERROR([you must specify a valid PostgreSQL installation with --with-pgsql=DIR])
    fi

    if test "$PG_SSL" = "yes" ; then
        PGSQL_CPPFLAGS="$PGSQL_CPPFLAGS -DSSL"
    fi

    LDFLAGS="$PGSQL_OLD_LDFLAGS"
    CPPFLAGS="$PGSQL_OLD_CPPFLAGS"

    AC_SUBST(PG_CONFIG)
    AC_SUBST([PGSQL_LDFLAGS], [$PGSQL_LDFLAGS])
    AC_SUBST([PGSQL_CPPFLAGS], [$PGSQL_CPPFLAGS])

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
                AC_HELP_STRING([--with-mysql=PATH],[path to mysql_config binary (autodetection is run if PATH is not present)]),
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
                AC_MSG_ERROR(Could not find MySQL installation (mysql_config not detected). You might need to use the --with-mysql= configure option.)
            fi
        fi
        with_mysql=yes
    ])

    if test -f "${MYSQL_CONFIG}"; then
        MYSQL_INC=`$MYSQL_CONFIG --include`
        if test -d `echo $MYSQL_INC |cut -d" " -f1 |cut -c 3-` ; then
            have_mysql=yes
        else
            AC_MSG_ERROR([mysql_config pointed on non existent directory. Your MySQL installation may be broken or you might need to use the --with-mysql=PATH configure option to point right mysql_config])
        fi
    fi

    AM_CONDITIONAL([MYSQL], [test x$have_mysql = xyes])
])



##################################################
# Check for libmysqlclient libraries and headers #
##################################################
AC_DEFUN([SETUP_MYSQL],
[
    MYSQL_LIB=`$MYSQL_CONFIG --libs_r`
    MYSQL_OLD_LDFLAGS="$LDFLAGS"
    MYSQL_OLD_CPPFLAGS="$CPPFLAGS"

    LDFLAGS="$LDFLAGS $MYSQL_LIB"
    CPPFLAGS="$CPPFLAGS $MYSQL_INC"

    AC_CHECK_LIB(mysqlclient_r ,mysql_init, have_mysql=yes,
      AC_MSG_ERROR([MySQL libraries not found])
    )

    AC_MSG_CHECKING(MySQL)
    AC_MSG_RESULT(ok)

    AC_SUBST([MYSQL_CPPFLAGS], [`$MYSQL_CONFIG --include`])
    AC_SUBST([MYSQL_LDFLAGS], [`$MYSQL_CONFIG --libs_r`])

    LDFLAGS="$MYSQL_OLD_LDFLAGS"
    CPPFLAGS="$MYSQL_OLD_CPPFLAGS"


    lmsdefaultdriver=mysql
])


############################
# Locate Net-SNMP/UCD-SNMP #
############################
AC_DEFUN([LOCATE_SNMP],
[
    AC_MSG_CHECKING([for libsnmp])
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
            AC_MSG_ERROR(Cannot find Net-SNMP header files.)
        fi

        # Accomodate 64-Bit Libraries
        test -f $SNMP_DIR/lib64/libsnmp.a -o -f $SNMP_DIR/lib64/libsnmp.so       && SNMP_LIBDIR=$SNMP_DIR/lib64
        test -f $SNMP_DIR/lib64/libnetsnmp.a -o -f $SNMP_DIR/lib64/libnetsnmp.so && SNMP_LIBDIR=$SNMP_DIR/lib64

        if test -z "$SNMP_LIBDIR"; then
            # Accomodate 32-Bit Libraries
            test -f $SNMP_DIR/lib/libsnmp.a -o -f $SNMP_DIR/lib/libsnmp.so       && SNMP_LIBDIR=$SNMP_DIR/lib
            test -f $SNMP_DIR/lib/libnetsnmp.a -o -f $SNMP_DIR/lib/libnetsnmp.so && SNMP_LIBDIR=$SNMP_DIR/lib
        fi
        if test -z "$SNMP_INCDIR"; then
            AC_MSG_ERROR(Cannot find SNMP header files under $SNMP_DIR)
        else
            AC_MSG_RESULT(ok)
            have_snmp=yes
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
            test -f $i/lib64/libsnmp.a -o -f $i/lib64/libsnmp.so       && SNMP_LIBDIR=$i/lib64 && break
            test -f $i/lib64/libnetsnmp.a -o -f $i/lib64/libnetsnmp.so && SNMP_LIBDIR=$i/lib64 && break
        done

        # Only check for 32 Bit libraries if the 64 bit are not found
        if test -z "$SNMP_LIBDIR"; then
            # Accomodate 32-Bit Libraries
            for i in /usr /usr/local /usr/pkg /usr/snmp /opt /opt/net-snmp /opt/ucd-snmp /opt/snmp /usr/local/snmp; do
                test -f $i/lib/libsnmp.a -o -f $i/lib/libsnmp.so       && SNMP_LIBDIR=$i/lib && break
                test -f $i/lib/libnetsnmp.a -o -f $i/lib/libnetsnmp.so && SNMP_LIBDIR=$i/lib && break
            done
        fi

        if test -z "$SNMP_INCDIR"; then
            AC_MSG_RESULT(no)
            have_snmp=no
        else
            AC_MSG_RESULT(yes)
            have_snmp=yes
        fi

    ])
])

#####################################################
# Check for Net-SNMP/UCD-SNMP libraries and headers #
#####################################################
AC_DEFUN([SETUP_SNMP],
[
    if test -n "$SNMP_LIBDIR"; then
        SNMP_LDFLAGS="-L$SNMP_LIBDIR $LDFLAGS"
    fi
    SNMP_CFLAGS="-I$SNMP_INCDIR -I$SNMP_INCDIR/.."

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
      [ SNMP_LDFLAGS="$SNMP_LDFLAGS -lnetsnmp"
        AC_DEFINE(USE_NET_SNMP, 1, New Net SNMP Version)
        USE_NET_SNMP=yes ],
        [ AC_MSG_RESULT(Cannot find NET-SNMP libraries(snmp)... checking UCD-SNMP)
        USE_NET_SNMP=no ])

    if test "$USE_NET_SNMP" = "no"; then
      AC_CHECK_LIB(snmp, snmp_timeout,
        SNMP_LDFLAGS="$SNMP_LDFLAGS -lsnmp",
        AC_MSG_ERROR(Cannot find UCD-SNMP libraries(snmp)))
    fi

    AC_SUBST([SNMP_LDFLAGS], [$SNMP_LDFLAGS])
    AC_SUBST([SNMP_CFLAGS], [$SNMP_CFLAGS])
])

###########################################
# Check for libgadu libraries and headers #
###########################################
AC_DEFUN([SETUP_LIBGADU],
[
    AC_CHECK_HEADER(libgadu.h, [libgadu=yes], [libgadu=no])
    AM_CONDITIONAL([LIBGADU], [test x$libgadu = xyes])
])

######################
# Check for GNU Make #
######################
AC_DEFUN([CHECK_GNU_MAKE], [ AC_CACHE_CHECK( for GNU make,_cv_gnu_make_command,
        _cv_gnu_make_command='' ;
        for a in "$MAKE" make gmake gnumake ; do
            if test -z "$a" ; then continue ; fi ;
                if  ( sh -c "$a --version" 2> /dev/null | grep GNU  2>&1 > /dev/null ) ;  then
                    _cv_gnu_make_command=$a ;
                break;
            fi
        done ;
    );

    if test  "x$_cv_gnu_make_command" == "x"  ; then
        AC_MSG_ERROR(Cannot find GNU Make.)
    fi
])


############################
# Generate revision number #
############################
AC_DEFUN([GET_REVISION],
[
    AC_PATH_PROGS(GIT, git)
    if test -f "${GIT}" ; then  # last commit time
        LMSD_REVISION=`date -d @\`git show -s --format=%at\` "+%Y%m%d%H%m"`
    else                        # or compile time if git is not found
        LMSD_REVISION=`date "+%Y%m%d%H%m"`
    fi
])

