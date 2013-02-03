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

#include "dictionary.h"

static void * mem_double(void *ptr, int size)
{
    void *newptr;
 
    newptr = calloc(2*size, 1);
    memcpy(newptr, ptr, size);
    free(ptr);
    return newptr;
}

static unsigned dictionary_hash(char *key)
{
	int len;
	unsigned hash;
	int i;

	len = strlen(key);
	for (hash=0, i=0; i<len; i++) {
		hash += (unsigned) key[i];
		hash += (hash<<10);
		hash ^= (hash>>6);
	}
	hash += (hash <<3);
	hash ^= (hash >>11);
	hash += (hash <<15);
	return hash;
}

struct dictionary * dictionary_new(int size)
{
	struct dictionary *d;

	/* If no size was specified, allocate space for dictionaryMINSZ */
	if( size<DICTMINSZ ) 
		size = DICTMINSZ ;

	d = calloc(1, sizeof(struct dictionary));
	d->size = size;
	d->val  = calloc(size, sizeof(char*));
	d->key  = calloc(size, sizeof(char*));
	d->hash = calloc(size, sizeof(unsigned));
	return d;
}

void dictionary_free(struct dictionary *d)
{
	int i;

	if( d==NULL ) return;
	
	for (i=0; i<d->size; i++) 
	{
		if (d->key[i]!=NULL)
			free(d->key[i]);
		if (d->val[i]!=NULL)
			free(d->val[i]);
	}
	free(d->val);
	free(d->key);
	free(d->hash);
	free(d);
}

void dictionary_set(struct dictionary *d, char *key, char *val)
{
	int i;
	unsigned hash;

	if( d==NULL || key==NULL ) return;
	
	/* Compute hash for this key */
	hash = dictionary_hash(key) ;
	
	/* Find if value is already in blackboard */
	if( d->n>0 ) 
	{
		for(i=0; i<d->size; i++) 
		{
        		if( d->key[i]==NULL )
            			continue ;
			if( hash==d->hash[i] ) /* Same hash value */
			{ 
				if( !strcmp(key, d->key[i]) ) /* Same key */
				{	
					/* Found a value: modify and return */
			    		if( d->val[i]!=NULL )
						free(d->val[i]);
                			d->val[i] = val ? strdup(val) : NULL;
                			/* Value has been modified: return */
					return;
				}
			}
		}
	}
	/* Add a new value */
	/* See if dictionary needs to grow */
	if( d->n==d->size ) 
	{
		/* Reached maximum size: reallocate blackboard */
		d->val  = mem_double(d->val, d->size * sizeof(char*));
		d->key  = mem_double(d->key, d->size * sizeof(char*));
		d->hash = mem_double(d->hash, d->size * sizeof(unsigned));

		/* Double size */
		d->size *= 2;
	}

	/* Insert key in the first empty slot */
	for(i=0; i<d->size; i++)
    		if( d->key[i]==NULL ) /* Add key here */
        		break ;
	/* Copy key */
	d->key[i]  = strdup(key);
	d->val[i]  = val ? strdup(val) : NULL;
	d->hash[i] = hash;
	d->n++;
	return;
}

char * dictionary_get(struct dictionary *d, char *key, char *def)
{
	unsigned hash;
	int i;

	hash = dictionary_hash(key);
	for(i=0; i<d->size; i++) 
	{
    	    if( d->key==NULL )
        	continue ;
    	    /* Compare hash */
	    if( hash==d->hash[i] )
            /* Compare string, to avoid hash collisions */
        	if( !strcmp(key, d->key[i]) )
			return d->val[i];
	}
	return def;
}

