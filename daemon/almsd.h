/***********************************************************
*
*	A.L.E.C's LMS Daemon - Main headers
*
************************************************************/
/* $Id$ */

#ifndef _ALMSD_H_
#define _ALMSD_H_

#include "db.h"
#include "iniparser/iniparser.h"
#include "iniparser/strlib.h"

#define APIVERSION 4

struct global 
{
	int api_version;
	char *inifile;
	
	//db functions
	QUERY_HANDLE * (*db_query)(unsigned char *);
	QUERY_HANDLE * (*db_pquery)(unsigned char *, ...);
	void (*db_free)(QUERY_HANDLE *);
	int (*db_exec)(unsigned char *);
	int (*db_pexec)(unsigned char *, ...);
	int (*db_begin)();
	int (*db_commit)();
	int (*db_abort)();
	unsigned char * (*db_get_data)(QUERY_HANDLE *, int, const char *);
	
	//iniparser functions
	char * (*iniparser_getstr)(dictionary *, char *);
	char * (*iniparser_getstring)(dictionary *, char *, char *);
	int (*iniparser_getint)(dictionary *, char *, int);
	int (*iniparser_getboolean) (dictionary *, char *, int);
	double (*iniparser_getdouble)(dictionary *, char *, double);
	dictionary * (*iniparser_load)(char *);
	void (*iniparser_freedict)(dictionary *);
	
	//util functions
	int (*str_replace)(unsigned char **, const unsigned char *, const unsigned char *);
	unsigned char * (*str_save)(unsigned char *, const unsigned char *);
	unsigned char * (*str_concat)(const unsigned char *, const unsigned char *);
	unsigned char * (*str_lwc)(const unsigned char *);
};

struct module
{
	unsigned char *filename;
	unsigned char *instance;
	void *dlh;
	void (*reload)(struct global *, struct module *); 
};

typedef struct module MODULE;
typedef struct global GLOBAL;

#endif
