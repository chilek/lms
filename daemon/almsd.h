/***********************************************************
*
*	A.L.E.C's LMS Daemon - Main headers
*
************************************************************/
/* $Id$ */

#ifndef _ALMSD_H_
#define _ALMSD_H_

#include "db.h"
#include "./iniparser/iniparser.h"

#define APIVERSION 4

struct global 
{
	int api_version;
	char *inifile;
	struct module *modules;
	
	//db functions
	QUERY_HANDLE * (*db_query)(unsigned char *);
	void (*db_free)(QUERY_HANDLE *);
	int (*db_exec)(unsigned char *);
	int (*db_begin)();
	int (*db_commit)();
	int (*db_abort)();
	unsigned char * (*db_get_data)(QUERY_HANDLE *, int, const char *);
	
	//iniparser functions
	char * (*iniparser_getstr)(dictionary *, char *);
	char * (*iniparser_getstring)(dictionary *, char *, char *);
	int (*iniparser_getint)(dictionary *, char *, int);
	double (*iniparser_getdouble)(dictionary *, char *, double);
	dictionary * (*iniparser_load)(char *);
	void (*iniparser_freedict)(dictionary *);
	
	//util functions
	unsigned char * (*str_replace)(unsigned char *, const unsigned char *, const unsigned char *);
	unsigned char * (*save_string)(unsigned char *, const unsigned char *);
	unsigned char * (*str_concat)(const unsigned char *, const unsigned char *);
};

typedef struct 
{
	unsigned char *key;
	unsigned char *val;
}
MOD_ARGS;

struct module
{
	struct module *next;
	unsigned char *filename;
	MOD_ARGS *args;
	void *dlh;
	void (*reload)(struct global *, struct module *); 
};

typedef struct module MODULE;
typedef struct global GLOBAL;

#endif
