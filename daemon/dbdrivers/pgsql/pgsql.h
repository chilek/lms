/* $Id$ */

#ifndef _DB_POSTGRES_H_
#define _DB_POSTGRES_H_

#include <libpq-fe.h>

#define ERROR	0
#define OK	1

#define DB_UNKNOWN 	0
#define DB_CHAR   	1
#define DB_INT    	2
#define DB_DOUBLE 	3
#define DB_DATE 	4
#define DB_TIME		5

#define BUFFER_LENGTH	1024

/* Definitions in: /usr/include/pgsql/server/catalog/pg_type.h */
#define BOOLOID                 16
#define BYTEAOID                17
#define CHAROID                 18
#define NAMEOID                 19
#define INT8OID                 20
#define INT2OID                 21
#define INT2VECTOROID        	22
#define INT4OID                 23
#define REGPROCOID              24
#define TEXTOID                 25
#define OIDOID                  26
#define TIDOID               	27
#define XIDOID                	28
#define CIDOID                	29
#define OIDVECTOROID         	30
#define POINTOID                600
#define LSEGOID                 601
#define PATHOID                 602
#define BOXOID                  603
#define POLYGONOID              604
#define LINEOID                 628
#define FLOAT4OID           	700
#define FLOAT8OID           	701
#define ABSTIMEOID              702
#define RELTIMEOID              703
#define TINTERVALOID         	704
#define UNKNOWNOID              705
#define CIRCLEOID               718
#define CASHOID           	790
#define INETOID           	869
#define CIDROID           	650
#define ACLITEMSIZE           	8
#define BPCHAROID               1042
#define VARCHAROID              1043
#define DATEOID                 1082
#define TIMEOID                 1083
#define TIMESTAMPOID         	1114
#define TIMESTAMPTZOID       	1184
#define INTERVALOID             1186
#define TIMETZOID               1266
#define BITOID             	1560
#define VARBITOID              	1562
#define NUMERICOID              1700
#define REFCURSOROID         	1790
#define POSTGISPOINTOID         17409 
#define POSTGISUNKNOWNOID     	7405753 

/******************************* DATA TYPES *****************************/

typedef PGconn ConnHandle;
typedef PGresult ResultHandle;

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

/************************** FUNCTIONS ******************************/

/* Connect to database. Params: db, user, password, host, port, ssl.
    Returns 0 if connection failed, alse returns 1 */
ConnHandle * db_connect(const char *, const char *, const char *, const char *, int, int);

/* Closes connection */
int db_disconnect(ConnHandle *);

/* Executes SELECT query. Returns handle to query results */
QueryHandle * db_query(ConnHandle *, char *);

/* Prepares and executes SELECT query. Returns handle to query results. */
QueryHandle * db_pquery(ConnHandle *, char *, ...);

/* Free memory allocated in db_select() */
void db_free(QueryHandle **);

/* Executes UPDATE, INSERT, DELETE query. Returns number of affected rows */
int db_exec(ConnHandle *, char *);

/* Preparse and executes UPDATE, INSERT, DELETE query. Returns number of affected rows */
int db_pexec(ConnHandle *, char *, ...);

/* Gets last insert id. Returns int. */
int db_last_insert_id(ConnHandle *, const char *);

/* Begin transaction */
int db_begin(ConnHandle *);

/* Commit transaction */
int db_commit(ConnHandle *);

/* Aborts (rollbacks) transaction */
int db_abort(ConnHandle *);

/* Get string data from query results. Params: handle, row number, column name. */
char * db_get_data(QueryHandle *, int, const char *);

/* Escaping strings for use within an SQL command */
char * db_escape(ConnHandle *, const char *);

/* Get number of rows and columns */
int db_nrows(QueryHandle *);
int db_ncols(QueryHandle *);

/* Get column name */
char * db_colname(QueryHandle *, int);

/* concat strings specific to pgsql */
char * db_concat(int cnt, ...);

#endif
