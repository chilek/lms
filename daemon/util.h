/***************************************************************
*
*	UTIL.H - Other staff
*
****************************************************************/
/* $Id$ */

#ifndef _UTIL_H_
#define _UTIL_H_

#include "almsd.h"

/* Replaces all instances of string in some string with new string */
unsigned char *str_replace(unsigned char*, const unsigned char*, const unsigned char*);

/* Saves string with realloc */
unsigned char * str_save(unsigned char *, const unsigned char*);

/* Termination signals handling */
void termination_handler(int);

/* Parsing module args */
//MOD_ARGS * parse_module_argstring(unsigned char *);

/* Parsing args line. Needed for parse_module_argstring() */
unsigned char *ini_parse(unsigned char *,int *,unsigned char );

/* Concatenates strings */
unsigned char * str_concat(const unsigned char *, const unsigned char *);

#endif
						   				 