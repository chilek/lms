#ifndef _UTIL_H_
#define _UTIL_H_

/* Replaces all instances of string in some string with new string 
   Returns number of replaces */
int str_replace(unsigned char**, const unsigned char*, const unsigned char*);

/* Saves string with realloc */
unsigned char * str_save(unsigned char *, const unsigned char*);

/* Concatenates strings */
unsigned char * str_concat(const unsigned char *, const unsigned char *);

/* Termination signals handling */
void termination_handler(int);

#endif
