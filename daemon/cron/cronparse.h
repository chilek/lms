#ifndef _CRONPARSE_H_
#define _CRONPARSE_H_

#define MIN	0
#define HOU	1
#define DOM	2
#define MON	3
#define DOW	4

typedef unsigned long U32;
typedef unsigned short U16;
typedef unsigned char U8;

typedef struct Time
{
	U32 min[2];
	U32 hour;
	U32 dom;
	U16 month;
	U8  dow;
} CronTime;

typedef enum Status
{
	ParseOk = 0,
	ParseInvalidNumber,
	ParseInvalidRange,
	ParseInvalidStep,
	ParseStepNoEffect
} ParseStatus;

/* Parse crontab time definition functions */
ParseStatus processNumber(char *, int *);
ParseStatus processRange(char *, int *, int *);
ParseStatus processSubElement(char *, int *, int *, int *);
ParseStatus processElement(char *, CronTime *, int);
ParseStatus processTime(char *, CronTime *);

/* Change time to internat CronTime format */
void setTimeMin(CronTime *, int);
void setTimeHour(CronTime *, int);
void setTimeDOM(CronTime *, int);
void setTimeMonth(CronTime *, int);
void setTimeDOW(CronTime *, int);

#endif
