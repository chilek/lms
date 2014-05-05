/* $Id$ */

#ifndef _CONFIG_H_
#define _CONFIG_H_

#include "dictionary.h"
#include "../db.h"

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
Config * config_load(const char *, DB *, const char *, const char *);

/* Data fetching functions */
char * config_getstring(Config *, char *, char *, char *);
int config_getint(Config *, char *, char *, int);
int config_getbool(Config *, char *, char *, int);
double config_getdouble(Config *, char *, char *, double);

void config_load_from_file(const char *, const char *);
void config_load_from_db(DB *, const char *, const char *);
char * strskp(char *);
char * strcrop(char *);

#endif
