/********************************************************************
*
* 	DB.H - Headers file for database routines
*
*********************************************************************/
/* $Id$ */

#ifndef _DB_H_
#define _DB_H_

#ifdef USE_MYSQL
#include "mysql.h"
#endif
#ifdef USE_PGSQL
#include "pgsql.h"
#endif
#ifdef USE_SQLITE
#include "sqlite_.h"
#endif

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
typedef struct
{
    char *name;
    int type;
    int size;
}
COLUMN;

typedef struct
{
    unsigned char *data;
}
VALUE;

typedef struct
{
    VALUE *value;
}
ROW;

typedef struct
{
    RESULT_HANDLE *handle;
    COLUMN *col;
    ROW *row;
    int ncols;
    int nrows;
} 
QUERY_HANDLE;

/************************** FUNCTIONS ******************************/

/* Connect to database. Params: db, user, password, host, port.
    Returns 0 if connection failed, alse returns 1 */
int db_connect(const unsigned char *, const unsigned char *, 
		const unsigned char *, const unsigned char *, int);

/* Closes connection */
int db_disconnect(void);

/* Executes SELECT query. Returns handle to query results */
QUERY_HANDLE * db_query(unsigned char *);

/* Prepares and executes SELECT query. Returns handle to query results.
   Args must be type of unsigned char* */
QUERY_HANDLE * db_pquery(unsigned char *, ...);

/* Free memory allocated in db_select() */
void db_free(QUERY_HANDLE *);

/* Executes UPDATE, INSERT, DELETE query. Returns number of affected rows */
int db_exec(unsigned char *);

/* Preparse and executes UPDATE, INSERT, DELETE query. Returns number of affected rows 
   Args must be type of unsigned char* */
int db_pexec(unsigned char *, ...);

/* Begin transaction */
int db_begin();

/* Commit transaction */
int db_commit();

/* Aborts (rollbacks) transaction */
int db_abort();

/* Get string data from query results. Params: handle, row number, column name. */
unsigned char * db_get_data(QUERY_HANDLE *, int, const char *);

/* Internal: copy data and result defs to QUERY_HANDLE */
QUERY_HANDLE * get_query_result(RESULT_HANDLE *);

/* Internal: Parse query statement */
//static unsigned char * parse_query_stmt(unsigned char *);
void parse_query_stmt(unsigned char **);
#endif
