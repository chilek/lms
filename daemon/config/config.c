/* $Id$ */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <syslog.h>

#include "config.h"

/* Private: Parse special char sequences in string */
/* Maybe we don't need this if we have dictionary in database? */
unsigned char * parse(unsigned char *string)
{
    static unsigned char *out;
    unsigned char c, d, e;
    int i, k, n;

    n = strlen(string);
    out = (char *) malloc(n+1); // in the worst case we'll need so much 
    k = 0; 
    for(i=0; i<n; i++) { // foreach character in string 

	if(string[i]=='\\') { // is it '\' ? 

	    c=string[i+1]; // get next character
	    if(!c) continue; // if it's end of string, forget that 
	    if(c=='n') {
	    	out[k++] = '\n'; i++;
		continue;
	    }
	    if(c=='t') {
	    	out[k++] = '\t'; i++;
		continue;
	    }
	    if(c=='x') { // x - means hexadecimal code of character 
	        
		if( (d=string[i+2]) ) { // get first hex digit 
	        
	    	    if( (e=string[i+3]) ) { // get second hex digit 
		    
		        if(d>='a' && d<='f') d=d-'a'+10; else d-='0';
		        if(e>='a' && e<='f') e=e-'a'+10; else e-='0';
		        out[k++] = d << 4 | e; // calculate character code and write into final string 
			i += 3; // x<hex_digit><hex_digit> 
			continue; 
		    }
		}
	    }
	    out[k++] = c; i++; // just an escaped character
	}
	else
	{
	    out[k++] = string[i]; // add that end marker to final string
	}
    }
    out[k]=0;
    return out;
}

Config * config_new(int size)
{
	return dictionary_new(size);
}

void config_free(Config *c)
{
	dictionary_free(c);
}

/* Add an entry to the config object */
void config_add(Config *c, unsigned char *sec, unsigned char * key, unsigned char *val)
{
    unsigned char longkey[2*NAMESZ+1];
    unsigned char *value;

    /* Make a key as section:keyword */
    sprintf(longkey, "%s:%s", sec, key);
    
    /* Parse value: do we need this? */
    value = parse(val);

    /* Add (key,val) to config object */
    dictionary_set(c, longkey, value);
    
    /* clean up */
    free(value);
}

Config * config_load(ConnHandle *conn, const unsigned char *dbhost, const unsigned char *section)
{
    Config *c;
    QueryHandle *res;
    unsigned char *sec;
    unsigned char *var;
    unsigned char *val;
    int i;

    if( !conn )
    {
	    syslog(LOG_ERR, "ERROR: [config_load] Lost connection handle.");
	    return NULL;
    }

    // Initialize a new config entry
    c = config_new(0);
    
    if( ! section )
	    res = db_pquery(conn, "SELECT daemoninstances.name AS section, var, value FROM daemonconfig, daemonhosts, daemoninstances WHERE hostid=daemonhosts.id AND instanceid=daemoninstances.id AND daemonhosts.name='?' AND daemonconfig.disabled=0",dbhost);
    else
	    res = db_pquery(conn, "SELECT daemoninstances.name AS section, var, value FROM daemonconfig, daemonhosts, daemoninstances WHERE hostid=daemonhosts.id AND instanceid=daemoninstances.id AND daemonhosts.name='?' AND daemoninstances.name='?' AND daemonconfig.disabled=0", dbhost, section);

    for(i=0; i<db_nrows(res); i++) 
    {
	sec = db_get_data(res, i, "section");
	var = db_get_data(res, i, "var");
	val = db_get_data(res, i, "value");	
        config_add(c, sec, var, val);
    }
    
    db_free(&res);
    return c;
}

unsigned char * config_getstring(Config *c, unsigned char *sec, unsigned char *key, unsigned char *def)
{
    unsigned char *sval;
    unsigned char longkey[2*NAMESZ+1];

    if( c==NULL || key==NULL || sec==NULL )
        return def;

    /* Make a key as section:keyword */
    sprintf(longkey, "%s:%s", sec, key);
    sval = dictionary_get(c, longkey, def);

    return sval;
}

int config_getint(Config *c, unsigned char *sec, unsigned char *key, int notfound)
{
    unsigned char *str;

    str = config_getstring(c, sec, key, CONFIG_INVALID_KEY);
    if( str==CONFIG_INVALID_KEY ) 
	    return notfound;
    return atoi(str);
}

double config_getdouble(Config *c, unsigned char *sec, unsigned char *key, double notfound)
{
    unsigned char *str;

    str = config_getstring(c, sec, key, CONFIG_INVALID_KEY);
    if( str==CONFIG_INVALID_KEY ) 
	    return notfound;
    return atof(str);
}

int config_getbool(Config *c, unsigned char *sec, unsigned char * key, int notfound)
{
    unsigned char *str;
    int ret ;

    str = config_getstring(c, sec, key, CONFIG_INVALID_KEY);
    
    if( str==CONFIG_INVALID_KEY ) 
	    return notfound;
    
    switch( str[0] )
    {
	    case 'y': ret = 1; break; //true
	    case 'Y': ret = 1; break;
	    case '1': ret = 1; break;
	    case 't': ret = 1; break;
	    case 'T': ret = 1; break;
	    case 'n': ret = 0; break; //false
	    case 'N': ret = 0; break;
	    case '0': ret = 0; break;
	    case 'f': ret = 0; break;
	    case 'F': ret = 0; break;
	    default: ret = notfound;
    }
    return ret;
}

