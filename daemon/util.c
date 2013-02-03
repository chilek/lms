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

#include <string.h>
#include <stdlib.h>
#include <stdio.h>
#include <syslog.h>
#include <ctype.h>

#include "util.h"

/* Replaces each instance of string 'old' on the string 'string' with string 'new' */
int str_replace(char **string, const char *old, const char *new)
{
    size_t newLen = strlen(new);
    size_t oldLen = strlen(old);

    int n = 0;
    char* temp = *string;
    char* last = NULL;
    char* scan = NULL;

    while((temp = strstr(temp, old)) != NULL)
    {
        temp += oldLen;
        n++;
    }

    char *buffer = (char*)malloc(strlen(*string) + (newLen - oldLen) * n + 1);

    if( buffer == NULL )
        return 0;

    scan = buffer;
    temp = *string;
    last = *string;

    while((temp = strstr(temp, old)) != NULL)
    {
        size_t skip = temp - last;
        memcpy(scan, last, skip);
        memcpy(scan + skip, new, newLen);
        temp += oldLen;
        scan += skip + newLen;
        last = temp;
    }

    memcpy(scan, last, (*string) + strlen(*string) - last + 1);

    free(*string);  // warning string must be allocated
    *string = buffer;  //return new string
    return n;
}

/* Save value to string (needed i.e. for database routines)*/
char * str_save(char *str, const char *val)
{
    str = (char *) realloc(str, strlen(val)+1);
    return strcpy(str, val);
}

/* Concatenate strings */
char * str_concat(const char *s1, const char *s2)
{
	int l = strlen(s1) + strlen(s2) + 1;
	char *ret = (char*) malloc(l);
	
	snprintf(ret, l, "%s%s", s1, s2);
	return(ret);
}

/* Convert string to lower case */
char * str_lwc(const char *s)
{
    static char l[ASCIILINESZ+1];
    int i;

    if( s==NULL ) return NULL;
    memset(l, 0, ASCIILINESZ+1);
    i = 0;
    while( s[i] && i<ASCIILINESZ )
    {
        l[i] = (char) tolower((int)s[i]);
        i++;
    }
    l[ASCIILINESZ] = (char) 0;
    return l;
}

/* Convert string to upper case */
char * str_upc(const char *s)
{
    static char l[ASCIILINESZ+1];
    int i;

    if( s==NULL ) return NULL;
    memset(l, 0, ASCIILINESZ+1);
    i = 0;
    while( s[i] && i<ASCIILINESZ )
    {
        l[i] = (char) toupper((int)s[i]);
        i++;
    }
    l[ASCIILINESZ] = (char) 0;
    return l;
}

