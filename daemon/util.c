/*
 * LMS version 1.8-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

#include <string.h>
#include <stdlib.h>
#include <stdio.h>
#include <syslog.h>
#include <ctype.h>

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
	return(ret);
}

/* Convert string to lower case */
unsigned char * str_lwc(const unsigned char *s)
{
    static unsigned char l[ASCIILINESZ+1];
    int i;

    if( s==NULL ) return NULL;
    memset(l, 0, ASCIILINESZ+1);
    i = 0;
    while( s[i] && i<ASCIILINESZ )
    {
        l[i] = (unsigned char) tolower((int)s[i]);
        i++;
    }
    l[ASCIILINESZ] = (char) 0;
    return l;
}

/* Convert string to upper case */
unsigned char * str_upc(const unsigned char *s)
{
    static unsigned char l[ASCIILINESZ+1];
    int i;

    if( s==NULL ) return NULL;
    memset(l, 0, ASCIILINESZ+1);
    i = 0;
    while( s[i] && i<ASCIILINESZ )
    {
        l[i] = (unsigned char) toupper((int)s[i]);
        i++;
    }
    l[ASCIILINESZ] = (char) 0;
    return l;
}
