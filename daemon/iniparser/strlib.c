
/*-------------------------------------------------------------------------*/
/**
  @file		strlib.c
  @author	N. Devillard
  @date		Jan 2001
  @version	$Revision$
  @brief	Various string handling routines to complement the C lib.

  This modules adds a few complementary string routines usually missing
  in the standard C library.
*/
/*--------------------------------------------------------------------------*/

/*
	$Id$
	$Author$
	$Date$
	$Revision$
*/

/*---------------------------------------------------------------------------
   								Includes
 ---------------------------------------------------------------------------*/

#include <string.h>
#include <ctype.h>

#include "strlib.h"

/*---------------------------------------------------------------------------
   							    Defines	
 ---------------------------------------------------------------------------*/
#define ASCIILINESZ	1024

/*---------------------------------------------------------------------------
  							Function codes
 ---------------------------------------------------------------------------*/


/*-------------------------------------------------------------------------*/
/**
  @brief	Convert a string to lowercase.
  @param	s	String to convert.
  @return	ptr to statically allocated string.

  This function returns a pointer to a statically allocated string
  containing a lowercased version of the input string. Do not free
  or modify the returned string! Since the returned string is statically
  allocated, it will be modified at each function call (not re-entrant).
 */
/*--------------------------------------------------------------------------*/

unsigned char * strlwc(const unsigned char * s)
{
    static char l[ASCIILINESZ+1];
    int i ;

    if (s==NULL) return NULL ;
    memset(l, 0, ASCIILINESZ+1);
    i=0 ;
    while (s[i] && i<ASCIILINESZ) {
        l[i] = (char)tolower((int)s[i]);
        i++ ;
    }
    l[ASCIILINESZ]=(char)0;
    return l ;
}



/*-------------------------------------------------------------------------*/
/**
  @brief	Convert a string to uppercase.
  @param	s	String to convert.
  @return	ptr to statically allocated string.

  This function returns a pointer to a statically allocated string
  containing an uppercased version of the input string. Do not free
  or modify the returned string! Since the returned string is statically
  allocated, it will be modified at each function call (not re-entrant).
 */
/*--------------------------------------------------------------------------*/

char * strupc(char * s)
{
    static char l[ASCIILINESZ+1];
    int i ;

    if (s==NULL) return NULL ;
    memset(l, 0, ASCIILINESZ+1);
    i=0 ;
    while (s[i] && i<ASCIILINESZ) {
        l[i] = (char)toupper((int)s[i]);
        i++ ;
    }
    l[ASCIILINESZ]=(char)0;
    return l ;
}



/*-------------------------------------------------------------------------*/
/**
  @brief	Skip blanks until the first non-blank character.
  @param	s	String to parse.
  @return	Pointer to char inside given string.

  This function returns a pointer to the first non-blank character in the
  given string.
 */
/*--------------------------------------------------------------------------*/

char * strskp(char * s)
{
    char * skip = s;
	if (s==NULL) return NULL ;
    while (isspace((int)*skip) && *skip) skip++;
    return skip ;
} 



/*-------------------------------------------------------------------------*/
/**
  @brief	Remove blanks at the end of a string.
  @param	s	String to parse.
  @return	ptr to statically allocated string.

  This function returns a pointer to a statically allocated string,
  which is identical to the input string, except that all blank
  characters at the end of the string have been removed.
  Do not free or modify the returned string! Since the returned string
  is statically allocated, it will be modified at each function call
  (not re-entrant).
 */
/*--------------------------------------------------------------------------*/

char * strcrop(char * s)
{
    static char l[ASCIILINESZ+1];
	char * last ;

    if (s==NULL) return NULL ;
    memset(l, 0, ASCIILINESZ+1);
	strcpy(l, s);
	last = l + strlen(l);
	while (last > l) {
		if (!isspace((int)*(last-1)))
			break ;
		last -- ;
	}
	*last = (char)0;
    return l ;
}



/*-------------------------------------------------------------------------*/
/**
  @brief	Remove blanks at the beginning and the end of a string.
  @param	s	String to parse.
  @return	ptr to statically allocated string.

  This function returns a pointer to a statically allocated string,
  which is identical to the input string, except that all blank
  characters at the end and the beg. of the string have been removed.
  Do not free or modify the returned string! Since the returned string
  is statically allocated, it will be modified at each function call
  (not re-entrant).
 */
/*--------------------------------------------------------------------------*/
char * strstrip(char * s)
{
    static char l[ASCIILINESZ+1];
	char * last ;
	
    if (s==NULL) return NULL ;
    
	while (isspace((int)*s) && *s) s++;
	
	memset(l, 0, ASCIILINESZ+1);
	strcpy(l, s);
	last = l + strlen(l);
	while (last > l) {
		if (!isspace((int)*(last-1)))
			break ;
		last -- ;
	}
	*last = (char)0;

	return (char*)l ;
}

/* ----------------------------------------------------------
* Added function for parse special char sequences in ini values
* 
* LMS Developers : www.lms.rulez.pl 
* Author Aleksander Machniak : amachniak@go2.pl	
* -----------------------------------------------------------*/
unsigned char * parse(unsigned char *string)
{
    static unsigned char *out;
    unsigned char c,d,e;
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

#ifdef TEST
int main(int argc, char * argv[])
{
	char * str ;

	str = "\t\tI'm a lumberkack and I'm OK      " ;
	printf("lowercase: [%s]\n", strlwc(str));
	printf("uppercase: [%s]\n", strupc(str));
	printf("skipped  : [%s]\n", strskp(str));
	printf("cropped  : [%s]\n", strcrop(str));
	printf("stripped : [%s]\n", strstrip(str));

	return 0 ;
}
#endif
