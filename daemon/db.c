/***************************************************************
*
*      DB.C - Database routines
*
***************************************************************/
/* $Id$ */

#include <string.h>
#include <syslog.h>
#include "db.h"
#include "util.h"

#ifdef USE_MYSQL
CONN_HANDLE conn;
#endif
#ifdef USE_PGSQL
CONN_HANDLE *conn=NULL;
#endif
RESULT_HANDLE *res=NULL;

/************************* CONNECTION FUNCTIONS *************************/

/* Opens a connection to the db server */
int db_connect(const unsigned char *db, const unsigned char *user, const unsigned char *password, 
		const unsigned char *host, int port)
{
#ifdef USE_MYSQL
    if( mysql_init(&conn)==NULL ) {
	syslog(LOG_CRIT,"[db_connect] Unable to initialize database");
	return ERROR;
    }
    if( !port ) 
	port = 3306;
    if( !mysql_real_connect(&conn,host,user,password,db,port,NULL,0) ) {
	syslog(LOG_CRIT,"[db_connect] Unable to connect to database. Error: %s",mysql_error(&conn));
        mysql_close(&conn);
	return ERROR;
    }
#endif
#ifdef USE_PGSQL
    char connect_string[BUFFER_LENGTH];
    if( !port ) 
	port = 5432;
    snprintf(connect_string,sizeof(connect_string)-1,"host='%s' port=%d dbname='%s' user='%s' password='***'",host,port,db,user,password);
    connect_string[sizeof(connect_string)-1]='\x0';
    conn = PQconnectdb(connect_string);
    if(PQstatus(conn) == CONNECTION_BAD) {
	syslog(LOG_CRIT,"[db_connect] Unable to connect to database. Error: %s",PQerrorMessage(conn));
	PQfinish(conn);
        return ERROR;
    }
#endif
#ifdef DEBUG
    syslog(LOG_INFO, "DEBUG: Connected with params: db=%s host=%s user=%s passwd=%s port=%d",db, host, user, password, port);
#endif
    return OK;
}

/* Closes connection to db server */
int db_disconnect(void)
{
#ifdef USE_MYSQL
     mysql_close(&conn);
#endif
#ifdef USE_PGSQL
     if( PQstatus(conn) != CONNECTION_BAD )
          PQfinish(conn);
#endif
#ifdef DEBUG
    syslog(LOG_INFO, "DEBUG: Disconnected");
#endif
     return OK;
}

/************************* QUERY FUNCTIONS ************************/
/* Executes SELECT query */
QUERY_HANDLE * db_query(unsigned char *stmt) 
{
    QUERY_HANDLE *query;
    stmt = parse_query_stmt(stmt);
#ifdef DEBUG
    syslog(LOG_INFO,"DEBUG: %s", stmt);
#endif
#ifdef USE_MYSQL
    if( mysql_query(&conn,stmt) < 0 ) {
	syslog(LOG_CRIT,"[db_select] Query failed. Error: %s",mysql_error(&conn));
	return NULL;
    }
    if ( (res = mysql_store_result(&conn)) == NULL ) {
	syslog(LOG_CRIT,"[db_select] Unable to get query result. Error: %s",mysql_error(&conn));
	return NULL;
    }
#endif
#ifdef USE_PGSQL
    res = PQexec(conn,stmt);
    if( res==NULL || PQresultStatus(res)!=PGRES_TUPLES_OK ) {
	syslog(LOG_CRIT,"[db_select] Query failed. Error: %s",PQerrorMessage(conn));
	PQclear(res);
        return NULL;
    }
#endif
    query = get_query_result(res);
#ifdef USE_MYSQL
    mysql_free_result(res);
#endif
#ifdef USE_PGSQL
    PQclear(res);
#endif
    return query;
}

/* executes a INSERT, UPDATE, DELETE queries */
int db_exec(unsigned char *stmt)
{
    int result;
    stmt = parse_query_stmt(stmt);
#ifdef DEBUG
    syslog(LOG_INFO,"DEBUG: %s", stmt);
#endif
#ifdef USE_MYSQL
    if( mysql_query(&conn,stmt) != 0 ) {
	syslog(LOG_CRIT,"[db_exec] Query failed. Error: %s", mysql_error(&conn));
	return ERROR;
    }
    result = mysql_affected_rows(&conn);
#endif
#ifdef USE_PGSQL
    res = PQexec(conn,stmt);
    if( res==NULL || PQresultStatus(res)!=PGRES_COMMAND_OK ) {
	syslog(LOG_CRIT,"[db_exec] Query failed. Error: %s",PQerrorMessage(conn));
	PQclear(res);
        return ERROR;
    }
    result = atoi(PQcmdTuples(res));
    PQclear(res);
#endif
    return result;
}

/* Internal function for SELECT query result fetching */
static QUERY_HANDLE * get_query_result(RESULT_HANDLE * result)
{
    QUERY_HANDLE * query;
    COLUMN *my_col, *col;
    ROW *my_row;
    VALUE *val;
    int i, j;
    unsigned char *buf;
#ifdef USE_MYSQL
    MYSQL_ROW row;
    MYSQL_FIELD *field;
    enum enum_field_types dtype;

    query = (QUERY_HANDLE *) malloc(sizeof(QUERY_HANDLE));
    //query->handle = result; //we need this?
    query->ncols = mysql_num_fields(result);
    query->nrows = mysql_num_rows(result);
    
    my_col = (COLUMN *) malloc(query->ncols * sizeof(COLUMN));
    my_row = (ROW *) malloc(query->nrows * sizeof(ROW));
    
    // get columns defs 
    for (i = 0; i < query->ncols; i++) {

	my_col[i].name = (char *) malloc(sizeof(char *));
        col = &(my_col[i]);

	field = mysql_fetch_field_direct(result, i);

	col->name = save_string(col->name, field->name);
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
    while ((row = mysql_fetch_row(result)) != NULL) {
	my_row[i].value = (VALUE *) calloc(query->ncols, sizeof(VALUE));
        for (j = 0; j < query->ncols; j++) {
	    val = &(my_row[i].value[j]);
	    buf = (unsigned char *) row[j];
	    val->data = save_string(val->data,buf);
	}
	i++;
    }

    //add rows to query table
    query->row = my_row;
#endif
#ifdef USE_PGSQL
    Oid dtype;

    query = (QUERY_HANDLE *) malloc(sizeof(QUERY_HANDLE));
    //query->handle = result; //don't need this?
    query->nrows = PQntuples(result); 
    query->ncols = PQnfields(result); 

    my_col = (COLUMN *) malloc(query->ncols * sizeof(COLUMN));
    my_row = (ROW *) malloc(query->nrows * sizeof(ROW));
        
    // get columns defs 
    for (i = 0; i < query->ncols; i++) {

	my_col[i].name = (unsigned char *) malloc(sizeof(char *));
       	col = &(my_col[i]);
	
	col->name = save_string(col->name,PQfname(res,i));

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
	    buf = (unsigned char *) PQgetvalue(res, i, j); 
	    val->data = save_string(val->data,buf);
	}
    }
    
    //add rows to query table
    query->row = my_row;
#endif
    return query;
}

/* Parse query statement */
static unsigned char * parse_query_stmt(unsigned char * stmt)
{
#ifdef USE_MYSQL
    stmt = str_replace(stmt,"?NOW?","UNIX_TIMESTAMP()");
#endif
#ifdef USE_PGSQL
    stmt = str_replace(stmt,"?NOW?","EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))");
#endif
#ifdef USE_PGSQL
    stmt = str_replace(stmt,"LIKE","ILIKE");
    stmt = str_replace(stmt,"like","ILIKE");
#endif
    return stmt;
}

/* Starts transaction */
int db_begin()
{
#ifdef USE_PGSQL
    res = PQexec(conn,"BEGIN WORK");
#ifdef DEBUG
    syslog(LOG_INFO,"DEBUG: BEGIN WORK");
#endif
    if( res==NULL || PQresultStatus(res)!=PGRES_COMMAND_OK ) {
	syslog(LOG_CRIT,"[db_begin] Query failed. Error: %s",PQerrorMessage(conn));
	PQclear(res);
        return ERROR;
    }
    PQclear(res);
#endif
    return OK;
}

/* Commits transaction */
int db_commit()
{
#ifdef USE_PGSQL
    res = PQexec(conn,"COMMIT WORK");
#ifdef DEBUG
    syslog(LOG_INFO,"DEBUG: COMMIT WORK");
#endif
    if( res==NULL || PQresultStatus(res)!=PGRES_COMMAND_OK ) {
	syslog(LOG_CRIT,"[db_commit] Query failed. Error: %s",PQerrorMessage(conn));
	PQclear(res);
        return ERROR;
    }
    PQclear(res);
#endif
    return OK;
}

/* Aborts (rollbacks) transaction */
int db_abort()
{
#ifdef USE_PGSQL
    res = PQexec(conn,"ROLLBACK WORK");
#ifdef DEBUG
    syslog(LOG_INFO,"DEBUG: ROLLBACK WORK");
#endif    
    if( res==NULL || PQresultStatus(res)!=PGRES_COMMAND_OK ) {
	syslog(LOG_CRIT,"[db_abort] Query failed. Error: %s",PQerrorMessage(conn));
	PQclear(res);
        return ERROR;
    }
    PQclear(res);
#endif
    return OK;
}

/* Free memory allocated for query results */
void db_free(QUERY_HANDLE *query)
{
    int i, j;
    
    if( query!=NULL ) {
	for(i=0; i<query->nrows; i++) {
	    for (j=0; j<query->ncols; j++) {
		free(query->row[i].value[j].data);
	    }
	}
    
	for(i=0; i<query->ncols; i++) 
	    free(query->col[i].name);
    
	free(query->col);
	free(query->row);
	//free(query->handle);
	free(query);
    }
}

/********************* DATA FETCHING FUNCTIONS *********************/

/* fetch string data from given field */
unsigned char * db_get_data(QUERY_HANDLE *query, int row, const char *colname) 
{
    int i=1;
    // row number validation
//    if( row > query->nrows ) {
//	syslog(LOG_CRIT,"[db_get_str] Row number too big");
//	return "row number too big";
//    }
    // get column number
    for(i=0; i<query->ncols; i++) {
	if( !strcmp(query->col[i].name,colname) )
	    break;
    }
    if( i>=query->ncols ) {
	syslog(LOG_CRIT,"[db_get_str] Column '%s' not found",colname);
	return "NULL";
    }
    
    return query->row[row].value[i].data; 
}
