/* $Id$ */

#ifndef _DB_H_
#define _DB_H_

#include <sqlite.h>

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

typedef sqlite ConnHandle;
typedef int ResultHandle;

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
    COLUMN *col;
    ROW *row;
    int ncols;
    int nrows;
} 
QueryHandle;

/************************** FUNCTIONS ******************************/

/* Connect to database. Params: db, user, password, host, port.
    Returns 0 if connection failed, alse returns 1 */
ConnHandle * db_connect(const unsigned char *, const unsigned char *, 
		const unsigned char *, const unsigned char *, int);

/* Closes connection */
int db_disconnect(ConnHandle *);

/* Executes SELECT query. Returns handle to query results */
QueryHandle * db_query(ConnHandle *, unsigned char *);

/* Prepares and executes SELECT query. Returns handle to query results.
   Args must be type of unsigned char* */
QueryHandle * db_pquery(ConnHandle *, unsigned char *, ...);

/* Executes UPDATE, INSERT, DELETE query. Returns number of affected rows */
int db_exec(ConnHandle *, unsigned char *);

/* Preparse and executes UPDATE, INSERT, DELETE query. Returns number of affected rows 
   Args must be type of unsigned char* */
int db_pexec(ConnHandle *, unsigned char *, ...);

/* Escapes a string for use within an SQL command. Returns allocated string */
unsigned char * db_escape(ConnHandle *, unsigned char *);

/* Free memory allocated in db_query() and others */
void db_free(QueryHandle **);

/* Begin transaction */
int db_begin(ConnHandle *);

/* Commit transaction */
int db_commit(ConnHandle *);

/* Aborts (rollbacks) transaction */
int db_abort(ConnHandle *);

/* Get string data from query results. Params: handle, row number, column name. */
unsigned char * db_get_data(QueryHandle *, int, const char *);

/* Get number of rows and cols */
int db_nrows(QueryHandle *);
int db_ncols(QueryHandle *);

/* Get column name */
unsigned char * db_colname(QueryHandle *, int);

#endif
