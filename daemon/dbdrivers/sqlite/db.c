/*
 * LMS version 1.7-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

#include <string.h>
#include <syslog.h>
#include <stdlib.h>
#include <unistd.h>
#include <math.h>
#include <locale.h>
#include "db.h"
#include "../../util.h"

/* Parse special sequences in query statement */
static void parse_query_stmt(unsigned char **stmt)
{
    str_replace(stmt,"%NOW%","strftime('%s','now')");
}

/********************* USER DEFINED FUNCTIONS **********************/
static void inet_ntoa_f(sqlite_func *context, int argc, const char **argv)
{
    static char s[16];
    unsigned long z = strtoll(argv[0], (char **)NULL, 10);
    sprintf(s, "%ld.%ld.%ld.%ld", (z>>24)&0xff, (z>>16)&0xff, (z>>8)&0xff, z&0xff);
    sqlite_set_result_string(context, s, -1);
}

static void inet_aton_f(sqlite_func *context, int argc, const char **argv)
{
    static char s1[4], s2[4], s3[4], s4[4], s[12];
    unsigned long z;
    sscanf(argv[0], "%[0-9].%[0-9].%[0-9].%[0-9]", s1, s2, s3, s4);
    z = atoi(s1)*256*256*256+atoi(s2)*256*256+atoi(s3)*256+atoi(s4);
    sprintf(s, "%u", z);
    sqlite_set_result_string(context, s, -1);
}

static void floor_f(sqlite_func *context, int argc, const char **argv)
{
    static char s[12];
    double z = strtod(argv[0], (char **)NULL);
    sprintf(s,"%.0f", z);
    sqlite_set_result_string(context, s, -1);
}

static void upper_f(sqlite_func *context, int argc, const char **argv) 
{
    unsigned char *z;
    int i;
    if( argc<1 || argv[0]==0 ) return;
    z = sqlite_set_result_string(context, argv[0], -1);
    if( z==0 ) return;
    setlocale(LC_ALL, "");
    for(i=0; z[i]; i++)
        z[i] = toupper(z[i]);
}

static void lower_f(sqlite_func *context, int argc, const char **argv)
{
    unsigned char *z;
    int i;
    if( argc<1 || argv[0]==0 ) return;
    z = sqlite_set_result_string(context, argv[0], -1);
    if( z==0 ) return;
    setlocale(LC_ALL, "");
    for(i=0; z[i]; i++)
        z[i] = tolower(z[i]);
}

/************************* CONNECTION FUNCTIONS *************************/
/* Opens a connection to the db server */
ConnHandle * db_connect(const unsigned char *db, const unsigned char *user, const unsigned char *password, 
		const unsigned char *host, int port)
{
    ConnHandle *conn = NULL;
    char *error = NULL;
    /* first check file, because if file isn't exist sqlite_open() creates file */
    if( access(db, F_OK)!=0 ) {
	syslog(LOG_CRIT,"ERROR: [db_connect] Database file not exist, check config!");
	return NULL;
    }
    conn = sqlite_open(db, 0, &error);
    if( error ) {
    	syslog(LOG_CRIT,"ERROR: [db_connect] Unable to connect to database. %s", error);
	sqlite_freemem(error);
        return NULL;
    }
    /* add udf functions on every connect */	   
    sqlite_create_function(conn, "inet_ntoa", 1, inet_ntoa_f, NULL);
    sqlite_create_function(conn, "inet_aton", 1, inet_aton_f, NULL);
    sqlite_create_function(conn, "floor", 1, floor_f, NULL);
    sqlite_create_function(conn, "upper", 1, upper_f, NULL);
    sqlite_create_function(conn, "lower", 1, lower_f, NULL);
#ifdef DEBUG0
    syslog(LOG_INFO, "DEBUG: [lmsd] Connected with params: db='%s' host='%s' user='%s' port='%d' passwd='*'",db, host, user, port);
#endif
    return conn;
}

/* Closes connection to db server */
int db_disconnect(ConnHandle *conn)
{
    if( !conn )
    {
	    syslog(LOG_ERR, "ERROR: [db_disconnect] Lost connection handle.");
	    return ERROR;
    }

    sqlite_close(conn);
#ifdef DEBUG0
    syslog(LOG_INFO, "DEBUG: [lmsd] Disconnected");
#endif
    return OK;
}

/************************* QUERY FUNCTIONS ************************/
/* Executes SELECT query */
QueryHandle * db_query(ConnHandle *conn, unsigned char *q) 
{
    QueryHandle *query;
    COLUMN *my_col, *col;
    ROW *my_row;
    VALUE *val;
    char *error = NULL;
    const char *query_tail = NULL;
    sqlite_vm *vm = NULL;
    const char **results = NULL;
    const char **columnNames = NULL;
    int i, numCols = 0;
    unsigned char *buf, *stmt;

    if( !conn )
    {
	    syslog(LOG_ERR, "ERROR: [db_query] Lost connection handle.");
	    return NULL;
    }

    stmt = strdup(q);
    parse_query_stmt(&stmt);
#ifdef DEBUG0
    syslog(LOG_INFO,"DEBUG: [SQL] %s.", stmt);
#endif

    sqlite_compile(conn, stmt, &query_tail, &vm, &error);
    if( error ) {
    	syslog(LOG_CRIT,"ERROR: [db_query] Query failed. %s", error);
	sqlite_freemem(error);
        free(stmt);
	return NULL;
    }
    query = (QueryHandle *) malloc(sizeof(QueryHandle));
    my_row = (ROW *) malloc(sizeof(ROW));
    query->nrows = 0;
    
    while( sqlite_step(vm, &numCols, &results, &columnNames)==SQLITE_ROW ) {
	
	    my_row = (ROW *) realloc(my_row, sizeof(ROW) * (query->nrows+1)); 
    	    my_row[query->nrows].value = (VALUE *) calloc(numCols, sizeof(VALUE));
            
	    for( i=0; i<numCols; i++ ) {
	   
    		    val = &(my_row[query->nrows].value[i]);
		    buf = (unsigned char *) (results[i] ? results[i] : "");
		    val->data = str_save(val->data,buf);
	    }
	    query->nrows++; 
    }
    query->row = my_row;
    query->ncols = numCols; 
    my_col = (COLUMN *) malloc(query->ncols * sizeof(COLUMN));
    for( i=0; i<numCols; i++ ) {
  	    
	    my_col[i].name = (char *) malloc(sizeof(char *));
    	    col = &(my_col[i]);
	    col->name = str_save(col->name, columnNames[i]);
	    col->size = DB_UNKNOWN;
	    col->type = DB_UNKNOWN;
    }
    query->col = my_col;
    sqlite_finalize(vm, NULL);
    free(stmt);
    return query;
}

/* Prepares and executes SELECT query */
QueryHandle * db_pquery(ConnHandle *conn, unsigned char *q, ... ) 
{
    QueryHandle *query;
    va_list ap;
    int i;
    unsigned char *p, *s, *result, *temp;

    result = (unsigned char*) strdup("");
    s = (unsigned char *) malloc (sizeof(unsigned char*));    
    
    // find '?' and replace with arg value
    va_start(ap, q);
    for(p=q; *p; p++) {
	    if( *p != '?' ) {
		    i = strlen(result)+2;
		    s = (unsigned char*) realloc(s, i);
	    	    snprintf(s, i,"%s%c", result, *p);
	    } else {
        	    temp = va_arg(ap, unsigned char*);
		    i = strlen(temp)+strlen(result)+1;
		    s = (unsigned char*) realloc(s, i);
		    snprintf(s, i, "%s%s", result, temp);
	    }
	    free(result);
	    result = (unsigned char *) strdup(s);
    } 
    va_end(ap);
    
    // execute prepared query
    query = db_query(conn, result);
    // free temporary vars
    free(s); free(result);
    
    return query;
}

/* executes a INSERT, UPDATE, DELETE queries */
int db_exec(ConnHandle *conn, unsigned char *q)
{
    ResultHandle res = 0;
    int result = 0;
    unsigned char *stmt;
    char *error = NULL;

    if( !conn )
    {
	    syslog(LOG_ERR, "ERROR: [db_exec] Lost connection handle.");
	    return 0;
    }

    stmt = strdup(q);
    parse_query_stmt(&stmt);
#ifdef DEBUG0
    syslog(LOG_INFO,"DEBUG: [SQL] %s", stmt);
#endif
    res = sqlite_exec(conn, stmt, NULL, NULL, &error);
    if( error ) {
    	syslog(LOG_CRIT,"ERROR: [db_exec] Query failed. %s",error);
	sqlite_freemem(error);
	free(stmt);
        return ERROR;
    }
    result = sqlite_changes(conn);
    free(stmt);
    return result;
}

/* Prepares and executes INSERT, UPDATE, DELETE queries */
int db_pexec(ConnHandle *conn, unsigned char *q, ... ) 
{
    va_list ap;
    int i, res;
    unsigned char *p, *s, *result, *temp;

    result = (unsigned char*) strdup("");
    s = (unsigned char *) malloc (sizeof(unsigned char*));    
    
    // find '?' and replace with arg value
    va_start(ap, q);
    for(p=q; *p; p++) {
	    if( *p != '?' ) {
		    i = strlen(result)+2;
		    s = (unsigned char*) realloc(s, i);
	    	    snprintf(s, i,"%s%c", result, *p);
	    } else {
        	    temp = va_arg(ap, unsigned char*);
		    i = strlen(temp)+strlen(result)+1;
		    s = (unsigned char*) realloc(s, i);
		    snprintf(s, i, "%s%s", result, temp);
	    }
	    free(result);
	    result = (unsigned char *) strdup(s);
    } 
    va_end(ap);
    
    // execute prepared query
    res = db_exec(conn, result);
    // free temporary vars
    free(s); free(result);

    return res;
}

/* Starts transaction */
int db_begin(ConnHandle *conn)
{
    ResultHandle res;
    char *error = NULL;

    if( !conn )
    {
	    syslog(LOG_ERR, "ERROR: [db_begin] Lost connection handle.");
	    return ERROR;
    }

    res = sqlite_exec(conn,"BEGIN", NULL, NULL, &error);
#ifdef DEBUG0
    syslog(LOG_INFO,"DEBUG: [SQL] BEGIN.");
#endif
    if(res!=SQLITE_OK) {
    	syslog(LOG_CRIT,"ERROR: [db_begin] Query failed. %s",error);
	sqlite_freemem(error);
        return ERROR;
    }
    return OK;
}

/* Commits transaction */
int db_commit(ConnHandle *conn)
{
    ResultHandle res;
    char *error = NULL;

    if( !conn )
    {
	    syslog(LOG_ERR, "ERROR: [db_commit] Lost connection handle.");
	    return ERROR;
    }

    res = sqlite_exec(conn,"COMMIT", NULL, NULL, &error);
#ifdef DEBUG0
    syslog(LOG_INFO,"DEBUG: [SQL] COMMIT.");
#endif
    if(res!=SQLITE_OK) {
    	syslog(LOG_CRIT,"ERROR: [db_commit] Query failed. %s",error);
	sqlite_freemem(error);
        return ERROR;
    }
    return OK;
}

/* Aborts (rollbacks) transaction */
int db_abort(ConnHandle *conn)
{
    ResultHandle res;
    char *error = NULL;

    if( !conn )
    {
	    syslog(LOG_ERR, "ERROR: [db_abort] Lost connection handle.");
	    return ERROR;
    }

    res = sqlite_exec(conn,"ROLLBACK", NULL, NULL, &error);
#ifdef DEBUG0
    syslog(LOG_INFO,"DEBUG: [SQL] ROLLBACK.");
#endif
    if(res!=SQLITE_OK) {
    	syslog(LOG_CRIT,"ERROR: [db_abort] Query failed. %s",error);
	sqlite_freemem(error);
        return ERROR;
    }
    return OK;
}

/* Free memory allocated for query results */
void db_free(QueryHandle **query)
{
    int i, j;
    QueryHandle *q = *query;

    if( q )
    {
	for(i=0; i<db_nrows(q); i++) 
	{
	    for (j=0; j<db_ncols(q); j++)
	        free(q->row[i].value[j].data);
	    free(q->row[i].value);
	}
    
	for(i=0; i<db_ncols(q); i++) 
	    free(q->col[i].name);
    
	free(q->col);
	free(q->row);
	free(q);
	*query=NULL;
    }
}

/********************* DATA FETCHING FUNCTIONS *********************/
/* fetch string data from given field */
unsigned char * db_get_data(QueryHandle *query, int row, const char *colname) 
{
    int i;

    if( query )
    {
	for(i=0; i<db_ncols(query); i++)
	    if( !strcmp(query->col[i].name, colname) )
		break;
    
	if( i>=db_ncols(query) ) 
	{
	    syslog(LOG_ERR,"ERROR: [db_get_data] Column '%s' not found.", colname);
	    return "";
	}

	if( row > db_nrows(query) || !db_nrows(query) ) 
	{
	    syslog(LOG_ERR,"ERROR: [db_get_data] Row '%d' not found.", row);
	    return "";
	}
    
	return query->row[row].value[i].data; 
    }
    return "";
}

// get number of rows
int db_nrows(QueryHandle *query)
{
    if( query )
	    return query->nrows;
    else
	    return 0;
}

// get number of columns
int db_ncols(QueryHandle *query)
{
    if( query )
	    return query->ncols;
    else
	    return 0;
}

/* fetch name of column given by number */
unsigned char * db_colname(QueryHandle *query, int column) 
{
    if( !query )
	    return "";
    
    if( column > db_ncols(query) || !db_ncols(query) ) 
    {
	    syslog(LOG_CRIT,"ERROR: [db_colname] Column '%d' not found.", column);
	    return "";
    }
    
    return query->col[column].name; 
}
