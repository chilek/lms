#ifndef _UTIL_H_
#define _UTIL_H_

#define ASCIILINESZ	1024

/* Replaces all instances of string in some string with new string 
   Returns number of replaces */
int str_replace(unsigned char**, const unsigned char*, const unsigned char*);

/* Saves string with realloc */
unsigned char * str_save(unsigned char *, const unsigned char*);

/* Concatenates strings */
unsigned char * str_concat(const unsigned char *, const unsigned char *);

/* Convert string to lower case */
unsigned char * str_lwc(const unsigned char *);

/* Convert string to upper case */
unsigned char * str_upc(const unsigned char *);

#endif
