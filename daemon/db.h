
#ifndef _DB_H_
#define _DB_H_

typedef void (*ConnHandle)();
typedef void (*QueryHandle)();

struct dbs
{
	ConnHandle *conn;

	// db functions
	ConnHandle * (*connect)(const char *, const char *, const char *, const char *, int, int);
	int (*disconnect)(ConnHandle *);
	QueryHandle * (*query)(ConnHandle *, char *);
	QueryHandle * (*pquery)(ConnHandle *, char *, ...);
	void (*free)(QueryHandle **);
	int (*exec)(ConnHandle *, char *);
	int (*pexec)(ConnHandle *, char *, ...);
	int (*last_insert_id)(ConnHandle *, const char *);
	int (*begin)(ConnHandle *);
	int (*commit)(ConnHandle *);
	int (*abort)(ConnHandle *);
	int (*nrows)(QueryHandle *);
	int (*ncols)(QueryHandle *);
	char * (*concat)(int cnt, ...);
	char * (*get_data)(QueryHandle *, int, const char *);
	char * (*escape)(ConnHandle *, const char *);
	char * (*colname)(QueryHandle *, int);
};


typedef struct dbs DB;

#endif
