#ifndef _UTIL_H_
#define _UTIL_H_

#define ASCIILINESZ	1024

/* Replaces all instances of string in some string with new string 
   Returns number of replaces */
int str_replace(char**, const char*, const char*);

/* Saves string with realloc */
char * str_save(char *, const char*);

/* Concatenates strings */
char * str_concat(const char *, const char *);

/* Convert string to lower case */
char * str_lwc(const char *);

/* Convert string to upper case */
char * str_upc(const char *);

/* join vlist elements with delmimiter */
char * va_list_join(int cnt, char * delim, va_list vl);

#endif
