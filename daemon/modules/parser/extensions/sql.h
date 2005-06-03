#ifndef SQL_H
#define SQL_H

#ifdef USE_PGSQL
#include "../../../dbdrivers/pgsql/db.h"
#endif
#ifdef USE_MYSQL
#include "../../../dbdrivers/mysql/db.h"
#endif
#ifdef USE_SQLITE
#include "../../../dbdrivers/sqlite/db.h"
#endif

void tscript_ext_sql_init(ConnHandle *);
void tscript_ext_sql_close();

#endif
