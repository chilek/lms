
#ifndef _LMSD_H_
#define _LMSD_H_

#include "util.h"
#include "cron/cron.h"
#include "config/config.h"

#ifdef USE_PGSQL
#include "dbdrivers/pgsql/db.h"
#endif
#ifdef USE_MYSQL
#include "dbdrivers/mysql/db.h"
#endif
#ifdef USE_SQLITE
#include "dbdrivers/sqlite/db.h"
#endif

#define APIVERSION 5

struct global 
{
	int api_version;
	ConnHandle *conn;
	
	// db functions
	ConnHandle * (*db_connect)(const unsigned char *, const unsigned char *, 
				const unsigned char *, const unsigned char *, int);
	int (*db_disconnect)(ConnHandle *);
	QueryHandle * (*db_query)(ConnHandle *, unsigned char *);
	QueryHandle * (*db_pquery)(ConnHandle *, unsigned char *, ...);
	void (*db_free)(QueryHandle **);
	int (*db_exec)(ConnHandle *, unsigned char *);
	int (*db_pexec)(ConnHandle *, unsigned char *, ...);
	int (*db_begin)(ConnHandle *);
	int (*db_commit)(ConnHandle *);
	int (*db_abort)(ConnHandle *);
	int (*db_nrows)(QueryHandle *);
	int (*db_ncols)(QueryHandle *);
	unsigned char * (*db_get_data)(QueryHandle *, int, const char *);
	
	// config  functions
	unsigned char * (*config_getstring)(Config *, unsigned char *, unsigned char *, unsigned char *);
	int (*config_getint)(Config *, unsigned char *, unsigned char *, int);
	int (*config_getbool)(Config *, unsigned char *, unsigned char *, int);
	double (*config_getdouble)(Config *, unsigned char *, unsigned char *, double);
	
	// util functions
	int (*str_replace)(unsigned char **, const unsigned char *, const unsigned char *);
	unsigned char * (*str_save)(unsigned char *, const unsigned char *);
	unsigned char * (*str_concat)(const unsigned char *, const unsigned char *);
	unsigned char * (*str_upc)(const unsigned char *);
	unsigned char * (*str_lwc)(const unsigned char *);
};

struct module
{
	unsigned char *file;
	unsigned char *instance;
	Config *ini;
	void *dlh;
	void (*reload)(struct global *, struct module *); 
};

struct instance
{
	unsigned char *name;
	unsigned char *crontab;
	unsigned char *module;
};

typedef struct module MODULE;
typedef struct global GLOBAL;
typedef struct instance INSTANCE;

#endif
