/* $Id$ */

#ifndef _DICTIONARY_H_
#define _DICTIONARY_H_

/* Maximum value size for integers and doubles */
#define MAXVALSZ	1024

/* Maximum size of key name */
#define KEYMAXSZ	1024

/* Minimal allocated number of entries in a dictionary */
#define DICTMINSZ	128

struct dictionary {
	int n;			/* Number of entries in dictionary */
	int size;		/* Storage size */
	char **val;	/* List of string values */
	char **key;	/* List of string keys */
	unsigned *hash;		/* List of hash values for keys */
};

/* Create dictionary object */
struct dictionary * dictionary_new(int);

/* Destroy dictionary and free allocated memory */
void dictionary_free(struct dictionary *);

/* Set dictionary entry */
void dictionary_set(struct dictionary *, char *, char *);

/* Get entry value from dictionary */
char * dictionary_get(struct dictionary *, char *, char *);

#endif
