#ifndef _CRON_H_
#define _CRON_H_

#include <time.h>
#include "cronparse.h"

#define INVALID_TIME_FORMAT	0
#define PARSE_OK		1

/* Parse crontab time line to CronTime structure. Returns parse status. */
int cron_parse_time(CronTime *, const char *);

/* Compare current time returned by cron_sync_sleep() with CronTime structure.
*  Returns result of comparision */
int cron_match_time(CronTime *, time_t *);

/* Finds how long it's going to be until :00 of the following minute and
*  sleeps for this value. Returns timestamp of full minute */
time_t cron_sync_sleep(void);

#endif
