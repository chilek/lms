/**********************************************************
*
*	UTIL.C - Other staff
*
***********************************************************/
/* $Id$ */

#include <string.h>
#include <syslog.h>
#include "util.h"

/* Replaces each instance of string 'old' on the string 'string' with string 'new' */
int str_replace(unsigned char **string, const unsigned char *old, const unsigned char *new)
{
    size_t newLen = strlen(new);
    size_t oldLen = strlen(old);
    unsigned char *buffer = (unsigned char*)malloc(strlen(*string) + strlen(*string)*newLen +1); 
    unsigned char *temp, *scan = buffer;
    int i=0;

    temp = *string;  // remember old string
   
    if( buffer == NULL ) 
	return 0;

    *scan = 0;

    while(1)
    {
	unsigned char *there = strstr(temp, old);
	if( there == 0 ) {
	    strcat(scan,temp);
	    break;
	} else {
	    size_t skip = there - temp;
	    memcpy(scan, temp, skip);
	    memcpy(scan + skip, new, newLen);
	    temp = there + oldLen;
	    scan = scan + skip + newLen;
	    *scan = 0;
	    i++;
	}
    }
    buffer = (unsigned char *) realloc(buffer, strlen(buffer)+1);
    free(*string);  // warning string must be allocated
    *string = buffer;  //return new string
    return i; 
}

/* Save value to string (needed i.e. for database routines)*/
unsigned char * str_save(unsigned char *str, const unsigned char *val)
{
    str = (unsigned char *) realloc(str, strlen(val)+1);
    return strcpy(str, val);
}

/* termination signals handling */
void termination_handler(int signum)
{
     syslog(LOG_INFO, "A.L.E.C's LMS Daemon exited.");
     exit(0);
}

/* Parsing args line. Needed for parse_module_argstring() */
unsigned char * ini_parse(unsigned char *string, int *length, unsigned char terminator)
{
    unsigned char *out;
    unsigned char c,d,e;
    int i,k;

    out=(char*) malloc(strlen(string)); k=0; /* in the worst case we'll need so much */
    for(i=0;string[i]!=0;i++) /* foreach character in string */
    {
	if(string[i]=='\\') /* is it '\' ? */
	{
	    c=string[i+1]; /* get next character .. */
	    if(!c) continue; /* if it's end of string, forget that */
	    if(c=='n')
	    {
	    	out[k++] = '\n'; i++;
		continue;
	    }
	    if(c=='x') /* x - means hexadecimal code of character */
	    {
	        if((d=string[i+2])) /* get first hex digit */
	        {
	    	    if((e=string[i+3])) /* get second hex digit */
		    {
		        if(d>='a' && d<='f') d=d-'a'+10; else d-='0';
		        if(e>='a' && e<='f') e=e-'a'+10; else e-='0';
		        out[k++]=d << 4 | e; /* calculate character code and write into final string */
			i+=3; /* x<hex_digit><hex_digit> */
			continue; 
		    }
		}
	    }
	    out[k++]=c; i++; /* just an escaped character */
	}
	else
	{
	    if(string[i]==terminator) break; /* we reached end of string */
	    out[k++]=string[i]; /* add that end marker to final string */
	}
    }
    out[k]=0;
    if(length) *length=i;
    return(out);
}

/* Concatenate strings */
unsigned char * str_concat(const unsigned char *s1, const unsigned char *s2)
{
	int l = strlen(s1) + strlen(s2) + 1;
	unsigned char *ret = malloc(l);
	
	snprintf(ret, l, "%s%s", s1, s2);
	//free(s1);
	//free(s2);
	return(ret);
}
