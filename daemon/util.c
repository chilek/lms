/* $Id$ */

#include <string.h>
#include <stdlib.h>
#include <stdio.h>
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

/* Concatenate strings */
unsigned char * str_concat(const unsigned char *s1, const unsigned char *s2)
{
	int l = strlen(s1) + strlen(s2) + 1;
	unsigned char *ret = (unsigned char*) malloc(l);
	
	snprintf(ret, l, "%s%s", s1, s2);
	//free(s1);
	//free(s2);
	return(ret);
}

/* termination signals handling */
void termination_handler(int signum)
{
#ifdef DEBUG1
         syslog(LOG_INFO, "LMS Daemon exited.");
#endif
	 exit(0);
}
