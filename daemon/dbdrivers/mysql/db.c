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
#include <stdio.h>
#include <syslog.h>
#include <stdarg.h>
#include <stdlib.h>
#include <unistd.h>
#include "db.h"
#include "../../util.h"

/* Private function for SELECT query result fetching */
static QueryHandle * get_query_result(ResultHandle *res)
{
    QueryHandle *query;
    COLUMN *my_col, *col;
    ROW *my_row;
    VALUE *val;
    int i, j;
    char *buf;
    MYSQL_ROW row;
    MYSQL_FIELD *field;

    query = (QueryHandle *) malloc(sizeof(QueryHandle));
    query->ncols = mysql_num_fields(res);
    query->nrows = mysql_num_rows(res);
    
    my_col = (COLUMN *) malloc(query->ncols * sizeof(COLUMN));
    my_row = (ROW *) malloc(query->nrows * sizeof(ROW));
    
    // get columns defs 
    for (i = 0; i < query->ncols; i++) {

	my_col[i].name = (char *) malloc(sizeof(char *));
        col = &(my_col[i]);

	field = mysql_fetch_field_direct(res, i);

	col->name = str_save(col->name, field->name);
	col->size = field->length;
	
	// set column data type 
	switch (field->type) {
	    case FIELD_TYPE_SHORT:
	    case FIELD_TYPE_LONG:
	    case FIELD_TYPE_LONGLONG:
		col->type = DB_INT;
		break;
	    case FIELD_TYPE_TINY:
	    case FIELD_TYPE_VAR_STRING:
	    case FIELD_TYPE_STRING:
	    case FIELD_TYPE_BLOB:
		col->type = DB_CHAR;
		break;
	    case FIELD_TYPE_DOUBLE:
	    case FIELD_TYPE_FLOAT:
		col->type = DB_DOUBLE;
		break;
	    default:
		col->type = DB_UNKNOWN;
		break;
	}
    }	
    
    // add column defs to query table
    query->col = my_col;   
    
    // get data
    i = 0;
    while ((row = mysql_fetch_row(res)) != NULL) {
	my_row[i].value = (VALUE *) calloc(query->ncols, sizeof(VALUE));
        for (j = 0; j < query->ncols; j++) {
	    val = &(my_row[i].value[j]);
	    buf = (char *) ( row[j] ? row[j] : "");
	    val->data = str_save(val->data,buf);
	}
	i++;
    }

    //add rows to query table
    query->row = my_row;
    return query;
}

/* Parse special sequences in query statement */
static void parse_query_stmt(char **stmt)
{
    str_replace(stmt, "%NOW%", "UNIX_TIMESTAMP()");
}

/************************* CONNECTION FUNCTIONS *************************/
/* Opens a connection to the db server */
ConnHandle * db_connect(const char *db, const char *user, const char *password, 
		const char *host, int port, int ssl)
{
    ConnHandle *c = (ConnHandle *) malloc (sizeof(ConnHandle));
    if( mysql_init(&c->conn)==NULL ) {
	syslog(LOG_CRIT, "ERROR: [db_connect] Unable to initialize database.");
	free(c);
	return NULL;
    }
    
    if(ssl)
	mysql_ssl_set(&c->conn, NULL, NULL, NULL, NULL, NULL);    

    if( !mysql_real_connect(&c->conn,host,user,password,db,port,NULL,0) ) {
	syslog(LOG_CRIT,"ERROR: [db_connect] Unable to connect to database. %s", mysql_error(&c->conn));
        mysql_close(&c->conn);
	free(c);
	return NULL;
    }
    
    // SET NAMES utf8
    mysql_set_character_set(&c->conn, "utf8");
    
#ifdef DEBUG0
	syslog(LOG_INFO, "DEBUG: [lmsd] Connected with params: db='%s' host='%s' user='%s' port='%d' passwd='*'.",
	    db, host, user, port);
#endif
    db_exec(c, "SET NAMES utf8");

    return c;
}

/* Closes connection to db server */
int db_disconnect(ConnHandle *c)
{
    if( !c )
    {
	syslog(LOG_ERR, "ERROR: [db_disconnect] Lost connection handle.");
	return ERROR;
    }

    mysql_close(&c->conn);
    free(c);
#ifdef DEBUG0
    syslog(LOG_INFO, "DEBUG: [lmsd] Disconnected.");
#endif
    return OK;
}

/************************* QUERY FUNCTIONS ************************/
/* Executes SELECT query */
QueryHandle * db_query(ConnHandle *c, char *q) 
{
    ResultHandle *res = NULL;
    QueryHandle *query;
    char *stmt;

    if( !c )
    {
	syslog(LOG_ERR, "ERROR: [db_query] Lost connection handle.");
	return NULL;
    }

    stmt = strdup(q);
    parse_query_stmt(&stmt);
#ifdef DEBUG0
    syslog(LOG_INFO, "DEBUG: [SQL] %s", stmt);
#endif
    if( mysql_query(&c->conn,stmt) != 0 ) {
	syslog(LOG_CRIT, "ERROR: [db_query] Query failed. %s", mysql_error(&c->conn));
	free(stmt);
	return NULL;
    }

    if( (res = mysql_store_result(&c->conn)) == NULL ) {
	syslog(LOG_CRIT, "ERROR: [db_query] Unable to get query result. %s", mysql_error(&c->conn));
	free(stmt);
	return NULL;
    }

    query = get_query_result(res);
    mysql_free_result(res);
    free(stmt);
    return query;
}

/* Prepares and executes SELECT query */
QueryHandle * db_pquery(ConnHandle *c, char *q, ... ) 
{
    QueryHandle *query;
    va_list ap;
    int i;
    char *p, *s, *result, *escstr;

    result = (char*) strdup("");
    s = (char *) malloc (sizeof(char*));    
    
    // find '?' and replace with arg value
    va_start(ap, q);
    for(p=q; *p; p++) {
	    if( *p != '?' ) {
		    i = strlen(result)+2;
		    s = (char*) realloc(s, i);
	    	    snprintf(s, i,"%s%c", result, *p);
	    } else {
        	    escstr = db_escape(c, va_arg(ap, char*));
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
    query = db_query(c, result);
    // free temporary vars
    free(s); free(result);
    
    return query;
}

/* executes a INSERT, UPDATE, DELETE queries */
int db_exec(ConnHandle *c, char *q)
{
    int result = 0;
    char *stmt;

    if( !c )
    {
	syslog(LOG_ERR, "ERROR: [db_exec] Lost connection handle.");
	return 0;
    }

    stmt = strdup(q);
    parse_query_stmt(&stmt);
#ifdef DEBUG0
    syslog(LOG_INFO, "DEBUG: [SQL] %s", stmt);
#endif
    if( mysql_query(&c->conn,stmt) != 0 ) {
	syslog(LOG_CRIT, "ERROR: [db_exec] Query failed. %s", mysql_error(&c->conn));
	free(stmt);
	return ERROR;
    }
    result = mysql_affected_rows(&c->conn);
    free(stmt);
    return result;
}

/* Prepares and executes INSERT, UPDATE, DELETE queries */
int db_pexec(ConnHandle *c, char *q, ... ) 
{
    va_list ap;
    int i, res;
    char *p, *s, *result, *escstr;

    result = (char*) strdup("");
    s = (char *) malloc (sizeof(char*));    
    
    // find '?' and replace with arg value
    va_start(ap, q);
    for(p=q; *p; p++) {
	    if( *p != '?' ) {
		    i = strlen(result)+2;
		    s = (char*) realloc(s, i);
	    	    snprintf(s, i,"%s%c", result, *p);
	    } else {
	    	    escstr = db_escape(c, va_arg(ap, char*));
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
    res = db_exec(c, result);
    // free temporary vars
    free(s); free(result);

    return res;
}

/* Escapes a string for use within an SQL command */
char *db_escape(ConnHandle *c, const char *str) 
{
    char *escstr = (char *) malloc(strlen(str)*2 + 1);
    mysql_real_escape_string(&c->conn, escstr, str, strlen(str));
    return escstr;
}

/* Gets last insert id. Returns int. */
int db_last_insert_id(ConnHandle *c, const char *str)
{
    int id = 0;
    QueryHandle *res = db_query(c, "SELECT LAST_INSERT_ID() AS id");

    if(db_nrows(res))
        id = atoi(db_get_data(res, 0, "id"));
    db_free(&res);

    return id;
}

/* Starts transaction */
int db_begin(ConnHandle *c)
{
    if( !c )
    {
	syslog(LOG_ERR, "ERROR: [db_begin] Lost connection handle.");
	return ERROR;
    }
#ifdef DEBUG0
    syslog(LOG_INFO, "DEBUG: [SQL] BEGIN");
#endif
    if (mysql_autocommit(&c->conn, 0))
    {
	syslog(LOG_CRIT, "ERROR: [db_begin] Error. %s", mysql_error(&c->conn));
	return ERROR;
    }
    
    return OK;
}

/* Commits transaction */
int db_commit(ConnHandle *c)
{
    if( !c )
    {
	syslog(LOG_ERR, "ERROR: [db_commit] Lost connection handle.");
	return ERROR;
    }
#ifdef DEBUG0
    syslog(LOG_INFO, "DEBUG: [SQL] COMMIT");
#endif
    if (mysql_commit(&c->conn))
    {
	syslog(LOG_CRIT, "ERROR: [db_commit] Error. %s", mysql_error(&c->conn));
	return ERROR;
    }

    return OK;
}

/* Aborts (rollbacks) transaction */
int db_abort(ConnHandle *c)
{
    if( !c )
    {
	syslog(LOG_ERR, "ERROR: [db_abort] Lost connection handle.");
	return ERROR;
    }
#ifdef DEBUG0
    syslog(LOG_INFO, "DEBUG: [SQL] ROLLBACK");
#endif
    if (mysql_rollback(&c->conn))
    {
	syslog(LOG_CRIT, "ERROR: [db_abort] Error. %s", mysql_error(&c->conn));
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
char * db_get_data(QueryHandle *query, int row, const char *colname) 
{
    int i;

    if( query )
    {
	for(i=0; i<db_ncols(query); i++)
	    if( !strcmp(query->col[i].name,colname) )
		break;
		
	if( i>=db_ncols(query) ) 
	{
	    syslog(LOG_CRIT, "ERROR: [db_get_data] Column '%s' not found.",colname);
	    return "";
	}
    
	if( row > db_nrows(query) || !db_nrows(query) ) 
	{
	    syslog(LOG_CRIT, "ERROR: [db_get_data] Row '%d' not found.", row);
	    return "";
	}
    
	return query->row[row].value[i].data; 
    }
    return "";
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
