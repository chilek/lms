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
#define CONFIG_INVALID_KEY    ((unsigned char*)-1)

typedef struct dictionary Config;

/* Main functions */
Config * config_new(int);
void config_free(Config *);
void config_add(Config *c, unsigned char *sec, unsigned char * key, unsigned char *val);

/* Get config from database */
Config * config_load(ConnHandle *, const unsigned char *, const unsigned char *);

/* Data fetching functions */
unsigned char * config_getstring(Config *, unsigned char *, unsigned char *, unsigned char *);
int config_getint(Config *, unsigned char *, unsigned char *, int);
int config_getbool(Config *, unsigned char *, unsigned char *, int);
double config_getdouble(Config *, unsigned char *, unsigned char *, double);

#ifdef CONFIGFILE
Config * config_load_from_file(const unsigned char *);
unsigned char * strskp(unsigned char *);
unsigned char * strcrop(unsigned char *);
#endif

#endif
