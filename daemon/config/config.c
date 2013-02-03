/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <syslog.h>
#include <ctype.h>

#include "config.h"

/* Private: Parse special char sequences in string */
/* Maybe we don't need this if we have dictionary in database? */
char * parse(char *string)
{
    static char *out;
    char c, d, e;
    int i, k, n;

    n = strlen(string);
    out = (char *) malloc(n+1); // in the worst case we'll need so much 
    k = 0; 
    for(i=0; i<n; i++) { // foreach character in string 

	if(string[i]=='\\') { // is it '\' ? 

	    c = string[i+1]; // get next character
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
	    if(c=='\n') { // backlash at end of line - do nothing (for parser module)
		out[k++] = '\\'; out[k++] = c; i++;
		continue; 
	    } 

	    out[k++] = '\\'; // do nothing
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
void config_add(Config *c, char *sec, char * key, char *val)
{
    char longkey[2*NAMESZ+1];
    char *value;

    /* Make a key as section:keyword */
    sprintf(longkey, "%s:%s", sec, key);
    
    /* Parse value: do we need this? */
    value = parse(val);

    /* Add (key,val) to config object */
    dictionary_set(c, longkey, value);
    
    /* clean up */
    free(value);
}

Config * config_load(ConnHandle *conn, const char *dbhost, const char *section)
{
#ifdef CONFIGFILE
    return config_load_from_file(section);
#else
    Config *c;
    QueryHandle *res;
    char *sec, *var, *val;
    int i;
    
    if( !conn )
    {
	    syslog(LOG_ERR, "ERROR: [config_load] Lost connection handle.");
	    return NULL;
    }

    // Initialize a new config entry
    c = config_new(0);
    
    if( ! section )
	    res = db_pquery(conn, "SELECT daemoninstances.name AS section, var, value FROM daemonconfig, hosts, daemoninstances WHERE hostid=hosts.id AND instanceid=daemoninstances.id AND hosts.name='?' AND daemonconfig.disabled=0", dbhost);
    else
	    res = db_pquery(conn, "SELECT daemoninstances.name AS section, var, value FROM daemonconfig, hosts, daemoninstances WHERE hostid=hosts.id AND instanceid=daemoninstances.id AND hosts.name='?' AND daemoninstances.name='?' AND daemonconfig.disabled=0", dbhost, section);

    for(i=0; i<db_nrows(res); i++) 
    {
	sec = db_get_data(res, i, "section");
	var = db_get_data(res, i, "var");
	val = db_get_data(res, i, "value");	
        config_add(c, sec, var, val);
    }
    
    db_free(&res);
    return c;
#endif
}

#ifdef CONFIGFILE
Config * config_load_from_file(const char *section)
{
    Config *c;
    char sec[1024+1];
    char key[1024+1];
    char lin[1024+1];
    char val[1024+1];
    char *where, *value, *lastsec = "";
    FILE * ini ;
    int lineno ;

    if ((ini=fopen(CONFIGFILE, "r"))==NULL)
    {
	    syslog(LOG_ERR, "[config_load] Unable to open file '%s'.", CONFIGFILE);
    	    return NULL ;
    }

    c = config_new(0);
    lineno = 0;
    sec[0] = 0;

    while( fgets(lin, 1024, ini)!=NULL )
    {
    	    lineno++ ;
    	    where = strskp(lin); /* Skip leading spaces */

    	    if( *where==';' || *where=='#' || *where==0 )
        	    continue ; /* Comment lines */
    	    else
	    {
    		    if( sscanf(where, "[%[^]]", sec)==1 )
		    {
			    lastsec = sec;/* Valid section name */
			    continue;
		    }
        	    else if( (sscanf (where, "%[^=] = \"%[^\"]\"", key, val) == 2
                	    ||  sscanf (where, "%[^=] = '%[^\']'", key, val) == 2
                	    ||  sscanf (where, "%[^=] = %[^;#]",   key, val) == 2)
			    && strlen(lastsec)
			    && ( !section || (!strcmp(lastsec, section))) )
		    {
			    strcpy(key, strcrop(key));
            		    strcpy(val, strcrop(val));
			    /*
                	    * sscanf cannot handle "" or '' as empty value,
                	    * this is done here
                	    */
        		    if (!strcmp(val, "\"\"") || !strcmp(val, "''"))
                		    val[0] = (char) 0;
	
			    value = parse(val);
			    config_add(c, lastsec, key, value);
			    free(value);
    		    }
    	    }
    }

    fclose(ini);
    return c ;
}

char * strskp(char * s)
{
	char * skip = s;
	if( s==NULL ) return NULL;
	while (isspace((int)*skip) && *skip) skip++;
	return skip;
} 

char * strcrop(char * s)
{
	static char l[1024+1];
	char *last;

	if( s==NULL ) return NULL;
	memset(l, 0, 1024+1);
	strcpy(l, s);
	last = l + strlen(l);
	while (last > l) 
	{
		if (!isspace((int)*(last-1)))
			break;
		last--;
	}
	*last = (char) 0;
	return l;
}

#endif

char * config_getstring(Config *c, char *sec, char *key, char *def)
{
    char *sval;
    char longkey[2*NAMESZ+1];

    if( c==NULL || key==NULL || sec==NULL )
        return def;

    /* Make a key as section:keyword */
    sprintf(longkey, "%s:%s", sec, key);
    sval = dictionary_get(c, longkey, def);

    return sval;
}

int config_getint(Config *c, char *sec, char *key, int notfound)
{
    char *str;

    str = config_getstring(c, sec, key, CONFIG_INVALID_KEY);
    if( str==CONFIG_INVALID_KEY ) 
	    return notfound;
    return atoi(str);
}

double config_getdouble(Config *c, char *sec, char *key, double notfound)
{
    char *str;

    str = config_getstring(c, sec, key, CONFIG_INVALID_KEY);
    if( str==CONFIG_INVALID_KEY ) 
	    return notfound;
    return atof(str);
}

int config_getbool(Config *c, char *sec, char * key, int notfound)
{
    char *str;
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

