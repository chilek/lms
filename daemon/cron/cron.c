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

#include <time.h>
#include <syslog.h>
#include <stdio.h>
#include <stdarg.h>
#include <stdlib.h>
#include <unistd.h>
#include <string.h>

#include "cron.h"

/* Parse crontab time line to CronTime structure. Returns parse status. */
int cron_parse_time(CronTime *t, const char *line)
{
	static char min[255], hour[255], dom[255], mon[255], dow[255];
	char *str;
		
	// first crontab format check
	if(sscanf(line, "%s %s %s %s %s", min, hour, dom, mon, dow) < 5)
	{
		syslog(LOG_ERR, "[parseTime] Wrong crontab format: %s\n", line);
		return INVALID_TIME_FORMAT;
	}

	str = (char *)strdup(line);
	if( processTime(str, t) != ParseOk )
	{
		free(str);
		return INVALID_TIME_FORMAT;
	}

	free(str);
	return PARSE_OK;
}

/* Compare current time returned by cron_sync_sleep() with CronTime structure.
*  Returns result of comparision. 
*/
int cron_match_time(CronTime *t, time_t *time)
{
	struct tm *tt;
	CronTime ct;
	
	memset(&ct, 0, sizeof(CronTime));
	tt = localtime(time);
	
	setTimeMin(&ct, tt->tm_min);
	setTimeHour(&ct, tt->tm_hour);	
	setTimeDOW(&ct, tt->tm_wday);
	setTimeMonth(&ct, tt->tm_mon+1);
	setTimeDOM(&ct, tt->tm_mday);

	if( !((ct.min[0] & t->min[0]) || (ct.min[1] & t->min[1])) )
		return 0;

	if( !(ct.hour & t->hour) )
		return 0;

	if( !(ct.month & t->month) )
		return 0;

	if( !(ct.dom & t->dom) )
		return 0;

	if( !(ct.dow & t->dow) )
		return 0;

	return 1;
}

/*
* Finds how long it's going to be until :00 of the following minute and
* sleeps for this value. Returns timestamp of full minute.
*/
time_t cron_sync_sleep(void)
{
	register struct tm *t;
	time_t tt;
	int sec;
	
	tt = time(0);
	t = localtime(&tt);
	
	sec = 60 - t->tm_sec;
	tt += sec;
	
	// don't stop sleeping even when got any signal
	while( (sec = sleep(sec))!=0 ) {};
	
	return tt;
}
