/***************************************************************
*
*      DB.C - Database routines
*
***************************************************************/
/* $Id$ */

#include <string.h>
#include <syslog.h>
#include <stdarg.h>
#include <unistd.h>
#include <math.h>
#include <locale.h>
#include "db.h"
#include "util.h"

#ifdef USE_MYSQL
CONN_HANDLE conn;
RESULT_HANDLE *res=NULL;
#endif
#ifdef USE_PGSQL
CONN_HANDLE *conn=NULL;
RESULT_HANDLE *res=NULL;
#endif
#ifdef USE_SQLITE
CONN_HANDLE *conn=NULL;
RESULT_HANDLE res=0;
/*************************** SQLite UDF functions *************************/
// this would be in other file, maybe later ...
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
#endif

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
    snprintf(connect_string,sizeof(connect_string)-1,"host='%s' dbname='%s' user='%s' port='%d' password='%s'",host,db,user,port,password);
    connect_string[sizeof(connect_string)-1]='\x0';
    conn = PQconnectdb(connect_string);
    if( PQstatus(conn) == CONNECTION_BAD ) {
	syslog(LOG_CRIT,"[db_connect] Unable to connect to database. Error: %s",PQerrorMessage(conn));
	PQfinish(conn);
        return ERROR;
    }
#endif
#ifdef USE_SQLITE
    char *error = NULL;
    /* first check file, because if file isn't exist sqlite_open() creates file */
    if( access(db, F_OK)!=0 ) {
	syslog(LOG_CRIT,"[db_connect] Error: Database file not exist, check config!");
	return ERROR;
    }
    conn = sqlite_open(db, 0, &error);
    if( error ) {
    	syslog(LOG_CRIT,"[db_connect] Unable to connect to database. Error: %s", error);
	sqlite_freemem(error);
        return ERROR;
    }
    /* add udf functions on every connect */	   
    sqlite_create_function(conn, "inet_ntoa", 1, inet_ntoa_f, NULL);
    sqlite_create_function(conn, "inet_aton", 1, inet_aton_f, NULL);
    sqlite_create_function(conn, "floor", 1, floor_f, NULL);
    sqlite_create_function(conn, "upper", 1, upper_f, NULL);
    sqlite_create_function(conn, "lower", 1, lower_f, NULL);
#endif
#ifdef DEBUG0
	syslog(LOG_INFO, "DEBUG: [lmsd] Connected with params: db='%s' host='%s' user='%s' port='%d' passwd='*'",db, host, user, port);
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
#ifdef USE_SQLITE
    sqlite_close(conn);
#endif
#ifdef DEBUG0
    syslog(LOG_INFO, "DEBUG: [lmsd] Disconnected");
#endif
    return OK;
}

/************************* QUERY FUNCTIONS ************************/
/* Executes SELECT query */
QUERY_HANDLE * db_query(unsigned char *q) 
{
    QUERY_HANDLE *query;
    unsigned char *stmt = strdup(q);
#ifdef USE_SQLITE
    COLUMN *my_col, *col;
    ROW *my_row;
    VALUE *val;
    char *error = NULL;
    const char *query_tail = NULL;
    sqlite_vm *vm = NULL;
    const char **results = NULL;
    const char **columnNames = NULL;
    int i, numCols = 0;
    unsigned char *buf;
#endif
    parse_query_stmt(&stmt);
#ifdef DEBUG0
    syslog(LOG_INFO,"DEBUG: [SQL] %s", stmt);
#endif
#ifdef USE_MYSQL
    if( mysql_query(&conn,stmt) != 0 ) {
	syslog(LOG_CRIT,"[db_query] Query failed. Error: %s",mysql_error(&conn));
	free(stmt);
	return NULL;
    }
    if( (res = mysql_store_result(&conn)) == NULL ) {
	syslog(LOG_CRIT,"[db_query] Unable to get query result. Error: %s",mysql_error(&conn));
	free(stmt);
	return NULL;
    }
    if( mysql_num_rows(res) == 0 ) {  // empty result
	free(stmt);
	mysql_free_result(res);
	return NULL;
    }
    query = get_query_result(res);
    mysql_free_result(res);
#endif
#ifdef USE_PGSQL
    res = PQexec(conn,stmt);
    if( res==NULL || PQresultStatus(res)!=PGRES_TUPLES_OK ) {
	syslog(LOG_CRIT,"[db_query] Query failed. %s",PQerrorMessage(conn));
	PQclear(res);
        free(stmt);
	return NULL;
    }
    query = get_query_result(res);
    PQclear(res);
#endif
#ifdef USE_SQLITE
    sqlite_compile(conn, stmt, &query_tail, &vm, &error);
    if( error ) {
    	syslog(LOG_CRIT,"[db_query] Query failed. %s", error);
	sqlite_freemem(error);
        free(stmt);
	return NULL;
    }
    query = (QUERY_HANDLE *) malloc(sizeof(QUERY_HANDLE));
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
#endif
    free(stmt);
    return query;
}

/* Prepares and executes SELECT query */
QUERY_HANDLE * db_pquery(unsigned char *q, ... ) 
{
    QUERY_HANDLE *query;
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
    query = db_query(result);
    // free temporary vars
    free(s); free(result);
    
    return query;
}

/* executes a INSERT, UPDATE, DELETE queries */
int db_exec(unsigned char *q)
{
    int result = 0;
    unsigned char *stmt = strdup(q);
#ifdef USE_SQLITE
    char *error = NULL;
#endif
    parse_query_stmt(&stmt);
#ifdef DEBUG0
    syslog(LOG_INFO,"DEBUG: [SQL] %s", stmt);
#endif
#ifdef USE_MYSQL
    if( mysql_query(&conn,stmt) != 0 ) {
	syslog(LOG_CRIT,"[db_exec] Query failed. Error: %s", mysql_error(&conn));
	free(stmt);
	return ERROR;
    }
    result = mysql_affected_rows(&conn);
#endif
#ifdef USE_PGSQL
    res = PQexec(conn,stmt);
    if( res==NULL || PQresultStatus(res)!=PGRES_COMMAND_OK ) {
	syslog(LOG_CRIT,"[db_exec] Query failed. %s",PQerrorMessage(conn));
	PQclear(res);
	free(stmt);
        return ERROR;
    }
    result = atoi(PQcmdTuples(res));
    PQclear(res);
#endif
#ifdef USE_SQLITE
    res = sqlite_exec(conn, stmt, NULL, NULL, &error);
    if( error ) {
    	syslog(LOG_CRIT,"[db_exec] Query failed. %s",error);
	sqlite_freemem(error);
	free(stmt);
        return ERROR;
    }
    result = sqlite_changes(conn);
#endif
    free(stmt);
    return result;
}

/* Prepares and executes INSERT, UPDATE, DELETE queries */
int db_pexec(unsigned char *q, ... ) 
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
    res = db_exec(result);
    // free temporary vars
    free(s); free(result);

    return res;
}

/* Internal function for SELECT query result fetching */
QUERY_HANDLE * get_query_result(RESULT_HANDLE * result)
{
    QUERY_HANDLE *query;
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
    while ((row = mysql_fetch_row(result)) != NULL) {
	my_row[i].value = (VALUE *) calloc(query->ncols, sizeof(VALUE));
        for (j = 0; j < query->ncols; j++) {
	    val = &(my_row[i].value[j]);
	    buf = (unsigned char *) ( row[j] ? row[j] : "");
	    val->data = str_save(val->data,buf);
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
	
	col->name = str_save(col->name,PQfname(res,i));

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
	    val->data = str_save(val->data,buf);
	}
    }
    
    //add rows to query table
    query->row = my_row;
#endif
    return query;
}

/* Parse query statement */
void parse_query_stmt(unsigned char **stmt)
{
#ifdef USE_MYSQL
    str_replace(stmt,"%NOW%","UNIX_TIMESTAMP()");
#endif
#ifdef USE_SQLITE
    str_replace(stmt,"%NOW%","strftime('%s','now')");
#endif
#ifdef USE_PGSQL
    str_replace(stmt,"%NOW%","EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))");
    str_replace(stmt,"LIKE","ILIKE");
    str_replace(stmt,"like","ILIKE");
#endif
}

/* Starts transaction */
int db_begin()
{
#ifdef USE_PGSQL
    res = PQexec(conn,"BEGIN WORK");
#ifdef DEBUG0
    syslog(LOG_INFO,"DEBUG: [SQL] BEGIN WORK");
#endif
    if( res==NULL || PQresultStatus(res)!=PGRES_COMMAND_OK ) {
	syslog(LOG_CRIT,"[db_begin] Query failed. Error: %s",PQerrorMessage(conn));
	PQclear(res);
        return ERROR;
    }
    PQclear(res);
#endif
#ifdef USE_SQLITE
    char *error = NULL;
    res = sqlite_exec(conn,"BEGIN", NULL, NULL, &error);
#ifdef DEBUG0
    syslog(LOG_INFO,"DEBUG: [SQL] BEGIN");
#endif
    if(res!=SQLITE_OK) {
    	syslog(LOG_CRIT,"[db_begin] Query failed. Error: %s",error);
	sqlite_freemem(error);
        return ERROR;
    }
#endif
    return OK;
}

/* Commits transaction */
int db_commit()
{
#ifdef USE_PGSQL
    res = PQexec(conn,"COMMIT WORK");
#ifdef DEBUG0
    syslog(LOG_INFO,"DEBUG: [SQL] COMMIT WORK");
#endif
    if( res==NULL || PQresultStatus(res)!=PGRES_COMMAND_OK ) {
	syslog(LOG_CRIT,"[db_commit] Query failed. Error: %s",PQerrorMessage(conn));
	PQclear(res);
        return ERROR;
    }
    PQclear(res);
#endif
#ifdef USE_SQLITE
    char *error = NULL;
    res = sqlite_exec(conn,"COMMIT", NULL, NULL, &error);
#ifdef DEBUG0
    syslog(LOG_INFO,"DEBUG: [SQL] COMMIT");
#endif
    if(res!=SQLITE_OK) {
    	syslog(LOG_CRIT,"[db_commit] Query failed. Error: %s",error);
	sqlite_freemem(error);
        return ERROR;
    }
#endif
    return OK;
}

/* Aborts (rollbacks) transaction */
int db_abort()
{
#ifdef USE_PGSQL
    res = PQexec(conn,"ROLLBACK WORK");
#ifdef DEBUG0
    syslog(LOG_INFO,"DEBUG: [SQL] ROLLBACK WORK");
#endif    
    if( res==NULL || PQresultStatus(res)!=PGRES_COMMAND_OK ) {
	syslog(LOG_CRIT,"[db_abort] Query failed. Error: %s",PQerrorMessage(conn));
	PQclear(res);
        return ERROR;
    }
    PQclear(res);
#endif
#ifdef USE_SQLITE
    char *error = NULL;
    res = sqlite_exec(conn,"ROLLBACK", NULL, NULL, &error);
#ifdef DEBUG0
    syslog(LOG_INFO,"DEBUG: [SQL] ROLLBACK");
#endif
    if(res!=SQLITE_OK) {
    	syslog(LOG_CRIT,"[db_abort] Query failed. Error: %s",error);
	sqlite_freemem(error);
        return ERROR;
    }
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
	    free(query->row[i].value);
	}
    
	for(i=0; i<query->ncols; i++) 
	    free(query->col[i].name);
    
	free(query->col);
	free(query->row);
	free(query);
    }
}

/********************* DATA FETCHING FUNCTIONS *********************/

/* fetch string data from given field */
unsigned char * db_get_data(QUERY_HANDLE *query, int row, const char *colname) 
{
    int i=1;

    for(i=0; i<query->ncols; i++) {
	if( !strcmp(query->col[i].name,colname) )
	    break;
    }
    if( i>=query->ncols ) {
	syslog(LOG_CRIT,"[db_get_data] Column '%s' not found",colname);
	return "NULL";
    }
    
    return query->row[row].value[i].data; 
}
