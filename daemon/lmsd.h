
#ifndef _LMSD_H_
#define _LMSD_H_

#include "db.h"
#include "util.h"
#include "cron/cron.h"
#include "config/config.h"

#define APIVERSION 6
#define PROGNAME "lmsd"

struct global
{
	int api_version;
	struct dbs * db;

	// config  functions
	char * (*config_getstring)(Config *, char *, char *, char *);
	int (*config_getint)(Config *, char *, char *, int);
	int (*config_getbool)(Config *, char *, char *, int);
	double (*config_getdouble)(Config *, char *, char *, double);

	// util functions
	int (*str_replace)(char **, const char *, const char *);
	char * (*str_save)(char *, const char *);
	char * (*str_concat)(const char *, const char *);
	char * (*str_upc)(const char *);
	char * (*str_lwc)(const char *);
	char * (*va_list_join)(int cnt, char * delim, va_list vl);
};

struct lmsd_module
{
	char *file;
	char *instance;
	Config *ini;
	void *dlh;
	void (*reload)(struct global *, struct lmsd_module *); 
};

struct instance
{
	char *name;
	char *crontab;
	char *module;
};

typedef struct lmsd_module MODULE;
typedef struct global GLOBAL;
typedef struct instance INSTANCE;

#endif
