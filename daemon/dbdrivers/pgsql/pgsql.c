/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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
#include <stdlib.h>
#include <syslog.h>
#include <stdarg.h>
#include <unistd.h>
#include "pgsql.h"
#include "../../util.h"
#include "../../config.h"

/* Private function for SELECT query result fetching */
static QueryHandle * get_query_result(ResultHandle *res)
{
    QueryHandle *query;
    COLUMN *my_col, *col;
    ROW *my_row;
    VALUE *val;
    int i, j;
    char *buf;
    Oid dtype;

    query = (QueryHandle *) malloc(sizeof(QueryHandle));
    query->nrows = PQntuples(res); 
    query->ncols = PQnfields(res); 

    my_col = (COLUMN *) malloc(query->ncols * sizeof(COLUMN));
    my_row = (ROW *) malloc(query->nrows * sizeof(ROW));
        
    // get columns defs 
    for (i = 0; i < query->ncols; i++) {

	my_col[i].name = (char *) malloc(sizeof(char *));
       	col = &(my_col[i]);
	
	col->name = str_save(col->name, PQfname(res, i));

	dtype = PQftype(res, i);

	// set column data type & size
	switch (dtype) {
	    case INT8OID:
	    case INT2OID:
	    case INT4OID:
	    case OIDOID:
	    case POSTGISUNKNOWNOID:
		col->type = DB_INT;
		col->size = PQfsize(res, i);
		break;
	    case CHAROID:
	    case BPCHAROID:
	    case VARCHAROID:
	    case TEXTOID:
	    case POSTGISPOINTOID:
		col->type = DB_CHAR;
		col->size = PQfmod(res, i) - 4; // Looks strange but works
		break;
            case FLOAT4OID:
	    case FLOAT8OID:
		col->type = DB_DOUBLE;
		col->size = PQfsize(res, i);
		break;
	    case DATEOID:
		col->type = DB_DATE;
		col->size = 10; // YYYY-MM-DD 
		break;
	    case TIMEOID:
		col->type = DB_TIME;
		col->size = 8; // HH-MM-SS 
		break;
	    default:
    		col->type = DB_UNKNOWN;
		break;
	}
    }	
    
    // add column defs to query table
    query->col = my_col;

    // get data
    for (i = 0; i < query->nrows; i++) {
    	my_row[i].value = (VALUE *) calloc(query->ncols, sizeof(VALUE));
        for (j = 0; j < query->ncols; j++) {
            val = &(my_row[i].value[j]);
	    buf = (char *) PQgetvalue(res, i, j); 
	    val->data = str_save(val->data,buf);
	}
    }
    
    //add rows to query table
    query->row = my_row;
    return query;
}

/* Parse special sequences query in statement */
static void parse_query_stmt(char **stmt)
{
    str_replace(stmt, "%NOW%", "EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::integer");
    str_replace(stmt, "LIKE", "ILIKE");
    str_replace(stmt, "like", "ILIKE");
}

/************************* CONNECTION FUNCTIONS *************************/
/* Opens a connection to the db server */
ConnHandle * db_connect(const char *db, const char *user, const char *password, 
		const char *host, int port, int ssl)
{
    ConnHandle *conn = NULL;
    char connect_string[BUFFER_LENGTH];
    
    if( !port ) 
	port = 5432;
    snprintf(connect_string, sizeof(connect_string)-1, "host='%s' dbname='%s' user='%s' port='%d' password='%s'",
	host, db, user, port, password);

    if(ssl)
	strcat(connect_string, " sslmode='require'");

    connect_string[sizeof(connect_string)-1] = '\x0';
    
    conn = PQconnectdb(connect_string);
    
    if( PQstatus(conn) == CONNECTION_BAD ) {
	syslog(LOG_CRIT, "ERROR: [db_connect] Unable to connect to database. %s", PQerrorMessage(conn));
	PQfinish(conn);
        return NULL;
    }
#ifdef DEBUG0
	syslog(LOG_INFO, "DEBUG: [lmsd] Connected with params: db='%s' host='%s' user='%s' port='%d' passwd='*'.",
	    db, host, user, port);
#endif
    db_exec(conn, "SET CLIENT_ENCODING TO 'UNICODE'");

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

    if( PQstatus(conn) != CONNECTION_BAD )
	PQfinish(conn);
#ifdef DEBUG0
    syslog(LOG_INFO, "DEBUG: [lmsd] Disconnected.");
#endif
    return OK;
}

/************************* QUERY FUNCTIONS ************************/
/* Executes SELECT query */
QueryHandle * db_query(ConnHandle *conn, char *q) 
{
    ResultHandle *res=NULL;
    QueryHandle *query;
    char *stmt;

    if( !conn ) 
    {
	    syslog(LOG_ERR, "ERROR: [db_query] Lost connection handle.");
	    return NULL;
    }

    stmt = strdup(q);
    parse_query_stmt(&stmt);
#ifdef DEBUG0
    syslog(LOG_INFO, "DEBUG: [SQL] %s", stmt);
#endif
    res = PQexec(conn,stmt);
    if( res==NULL || PQresultStatus(res)!=PGRES_TUPLES_OK ) {
	syslog(LOG_ERR, "ERROR: [db_query] Query failed. %s", PQerrorMessage(conn));
	PQclear(res);
        free(stmt);
	return NULL;
    }
    query = get_query_result(res);
    PQclear(res);
    free(stmt);
    return query;
}

/* Prepares and executes SELECT query */
QueryHandle * db_pquery(ConnHandle *conn, char *q, ... ) 
{
    QueryHandle *query;
    va_list ap;
    int i;
    char *p, *s, *result, *escstr;

    result = strdup("");
    s = (char*) malloc (sizeof(char*));    
    
    // find '?' and replace with arg value
    va_start(ap, q);
    for(p=q; *p; p++) {
	    if( *p != '?' ) {
		    i = strlen(result)+2;
		    s = (char*) realloc(s, i);
	    	    snprintf(s, i,"%s%c", result, *p);
	    } else {
		    escstr = db_escape(conn, va_arg(ap, char *));
		    i = strlen(escstr)+strlen(result)+1;
		    s = (char*) realloc(s, i);
		    snprintf(s, i, "%s%s", result, escstr);
		    free(escstr);
	    }
	    free(result);
	    result = (char*) strdup(s);
    } 
    va_end(ap);

    // execute prepared query
    query = db_query(conn, result);
    // free temporary vars
    free(s); free(result);
    
    return query;
}

/* executes a INSERT, UPDATE, DELETE queries */
int db_exec(ConnHandle *conn, char *q)
{
    ResultHandle *res=NULL;
    int result = 0;
    char *stmt;
    
    if( !conn ) 
    {
	    syslog(LOG_ERR, "ERROR: [db_exec] Lost connection handle.");
	    return 0; 
    }
    
    stmt = strdup(q);
    parse_query_stmt(&stmt);
#ifdef DEBUG0
    syslog(LOG_INFO, "DEBUG: [SQL] %s", stmt);
#endif
    res = PQexec(conn,stmt);
    if( res==NULL || (PQresultStatus(res)!=PGRES_COMMAND_OK && PQresultStatus(res)!=PGRES_TUPLES_OK) )
    {
	syslog(LOG_ERR, "ERROR: [db_exec] Query failed. %s", PQerrorMessage(conn));
	PQclear(res);
	free(stmt);
        return ERROR;
    }
    result = atoi(PQcmdTuples(res));
    PQclear(res);
    free(stmt);
    return result;
}

/* Prepares and executes INSERT, UPDATE, DELETE queries */
int db_pexec(ConnHandle *conn, char *q, ... ) 
{
    va_list ap;
    int i, res;
    char *p, *s, *result, *escstr;

    result = strdup("");
    s = (char *) malloc (sizeof(char*));    

    // find '?' and replace with arg value
    va_start(ap, q);
    for(p=q; *p; p++) {
	    if( *p != '?' ) {
		    i = strlen(result)+2;
		    s = (char*) realloc(s, i);
	    	    snprintf(s, i,"%s%c", result, *p);
	    } else {
		    escstr = db_escape(conn, va_arg(ap, char*));
		    i = strlen(escstr)+strlen(result)+1;
		    s = (char*) realloc(s, i);
		    snprintf(s, i, "%s%s", result, escstr);
		    free(escstr);
	    }
	    free(result);
	    result = (char *) strdup(s);
    } 
    va_end(ap);

    // execute prepared query
    res = db_exec(conn, result);
    // free temporary vars
    free(s); free(result);

    return res;
}

/* Escapes a string for use within an SQL command */
char * db_escape(ConnHandle *c, const char *str) 
{
    //c isn't used, but required by mysql version of db_escape()
    char *escstr = (char *) malloc(strlen(str)*2 + 1);
    PQescapeString(escstr, str, strlen(str));
    return escstr;
}

/* Gets last insert id. Returns int. */
int db_last_insert_id(ConnHandle *c, const char *str)
{
	int id = 0;
	QueryHandle *res = db_pquery(c, "SELECT currval('?_id_seq') AS id", str);

	if(db_nrows(res))
                id = atoi(db_get_data(res, 0, "id"));
	db_free(&res);

	return id;
}

/* Starts transaction */
int db_begin(ConnHandle *conn)
{
    ResultHandle *res;
    
    if( !conn )
    {
	    syslog(LOG_ERR, "ERROR: [db_begin] Lost connection handle.");
	    return ERROR;
    }
    
    res = PQexec(conn, "BEGIN WORK");
#ifdef DEBUG0
    syslog(LOG_INFO, "DEBUG: [SQL] BEGIN WORK");
#endif
    if( res==NULL || PQresultStatus(res)!=PGRES_COMMAND_OK ) {
	syslog(LOG_ERR, "ERROR: [db_begin] Query failed. %s", PQerrorMessage(conn));
	PQclear(res);
        return ERROR;
    }
    PQclear(res);
    return OK;
}

/* Commits transaction */
int db_commit(ConnHandle *conn)
{
    ResultHandle *res;
    
    if( !conn )
    {
	    syslog(LOG_ERR, "ERROR: [db_commit] Lost connection handle.");
	    return ERROR;
    }
    
    res = PQexec(conn, "COMMIT WORK");
#ifdef DEBUG0
    syslog(LOG_INFO, "DEBUG: [SQL] COMMIT WORK");
#endif
    if( res==NULL || PQresultStatus(res)!=PGRES_COMMAND_OK ) {
	syslog(LOG_ERR, "ERROR: [db_commit] Query failed. %s", PQerrorMessage(conn));
	PQclear(res);
        return ERROR;
    }
    PQclear(res);
    return OK;
}

/* Aborts (rollbacks) transaction */
int db_abort(ConnHandle *conn)
{
    ResultHandle *res;
    
    if( !conn )
    {
	    syslog(LOG_ERR, "ERROR: [db_abort] Lost connection handle.");
	    return ERROR;
    }
    
    res = PQexec(conn, "ROLLBACK WORK");
#ifdef DEBUG0
    syslog(LOG_INFO, "DEBUG: [SQL] ROLLBACK WORK");
#endif    
    if( res==NULL || PQresultStatus(res)!=PGRES_COMMAND_OK ) {
	syslog(LOG_ERR, "ERROR: [db_abort] Query failed. %s", PQerrorMessage(conn));
	PQclear(res);
        return ERROR;
    }
    PQclear(res);
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
char * db_get_data(QueryHandle *query, int row, const char *colname) 
{
    int i;

    if( query ) 
    {
	for(i=0; i<db_ncols(query); i++)
	    if( !strcmp(query->col[i].name, colname) )
		break;

	if( i >= db_ncols(query) ) 
	{
	    syslog(LOG_ERR, "ERROR: [db_get_data] Column '%s' not found", colname);
	    return "";
	}

	if( row > db_nrows(query) || !db_nrows(query) ) 
	{
	    syslog(LOG_ERR, "ERROR: [db_get_data] Row '%d' not found", row);
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
char * db_colname(QueryHandle *query, int column) 
{
    if( !query )
	    return "";

    if( column > db_ncols(query) || !db_ncols(query) ) 
    {
	    syslog(LOG_CRIT, "ERROR: [db_colname] Column '%d' not found.", column);
	    return "";
    }
    
    return query->col[column].name; 
}

/* concat strings specific to pgsql */
char * db_concat(int cnt, ...)
{
    va_list vs;
    va_start(vs, cnt);
    char * result = va_list_join(cnt, " || ", vs);
    va_end(vs);

    return result;
}
