/* $Id$ */

#ifndef _CONFIG_H_
#define _CONFIG_H_

#include "dictionary.h"

#ifdef USE_MYSQL
#include "../dbdrivers/mysql/db.h"
#endif
#ifdef USE_PGSQL
#include "../dbdrivers/pgsql/db.h"
#endif

/* Maximum size of variable name or section name*/
#define NAMESZ		100

/* Invalid key token */
#define CONFIG_INVALID_KEY    ((char*)-1)

typedef struct dictionary Config;

/* Main functions */
Config * config_new(int);
void config_free(Config *);
void config_add(Config *, char *, char *, char *);

/* Get config from database */
Config * config_load(ConnHandle *, const char *, const char *);

/* Data fetching functions */
char * config_getstring(Config *, char *, char *, char *);
int config_getint(Config *, char *, char *, int);
int config_getbool(Config *, char *, char *, int);
double config_getdouble(Config *, char *, char *, double);

#ifdef CONFIGFILE
Config * config_load_from_file(const char *);
char * strskp(char *);
char * strcrop(char *);
#endif

#endif
