/* $Id$ */

#ifndef _DB_MYSQL_H_
#define _DB_MYSQL_H_

#include <mysql/mysql.h>
#include <mysql/mysqld_error.h>

#define ERROR	0
#define OK	1

#define DB_UNKNOWN 	0
#define DB_CHAR   	1
#define DB_INT    	2
#define DB_DOUBLE 	3
#define DB_DATE 	4
#define DB_TIME		5

#define BUFFER_LENGTH	1024

/******************************* DATA TYPES *****************************/

typedef MYSQL CONN;
typedef MYSQL_RES ResultHandle;

typedef struct
{
    char *name;
    int type;
    int size;
}
COLUMN;

typedef struct
{
    char *data;
}
VALUE;

typedef struct
{
    VALUE *value;
}
ROW;

typedef struct
{
    COLUMN *col;
    ROW *row;
    int ncols;
    int nrows;
} 
QueryHandle;

typedef struct
{
    CONN conn;
}
ConnHandle;

/************************** FUNCTIONS ******************************/

/* Connect to database. Params: db, user, password, host, port.
    Returns 0 if connection failed, alse returns 1 */
ConnHandle *db_connect(const char *, const char *, const char *, const char *, int, int);

/* Closes connection */
int db_disconnect(ConnHandle *);

/* Executes SELECT query. Returns handle to query results */
QueryHandle * db_query(ConnHandle *, char *);

/* Prepares and executes SELECT query. Returns handle to query results. */
QueryHandle * db_pquery(ConnHandle *, char *, ...);

/* Executes UPDATE, INSERT, DELETE query. Returns number of affected rows */
int db_exec(ConnHandle *, char *);

/* Preparse and executes UPDATE, INSERT, DELETE query. Returns number of affected rows */
int db_pexec(ConnHandle *, char *, ...);

/* Escapes a string for use within an SQL command. Returns allocated string */
char *db_escape(ConnHandle *, const char *);

/* Gets last insert id. Returns int. */
int db_last_insert_id(ConnHandle *, const char *);

/* Free memory allocated in db_query() and etc */
void db_free(QueryHandle **);

/* Begin transaction */
int db_begin(ConnHandle *);

/* Commit transaction */
int db_commit(ConnHandle *);

/* Aborts (rollbacks) transaction */
int db_abort(ConnHandle *);

/* Get string data from query results. Params: handle, row number, column name. */
char * db_get_data(QueryHandle *, int, const char *);

/* Get number of rows and cols */
int db_nrows(QueryHandle *);
int db_ncols(QueryHandle *);

/* Get column name */
char * db_colname(QueryHandle *, int);

/* concat strings specific to mysql */
char * db_concat(int cnt, ...);

#endif
