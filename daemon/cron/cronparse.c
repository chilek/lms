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

#include <string.h>
#include <stdlib.h>
#include <limits.h>
#include <syslog.h>
#include "cronparse.h"

ParseStatus processNumber(char *num, int *val)
{
	ParseStatus rc = ParseOk;
	long int n = 0;
	char *endptr = NULL;
	
	if( (*num == '\0') || 
	    ( (n = strtol(num, &endptr, 10) ) == LONG_MIN) ||
	    (n == LONG_MAX) ||
	    (*endptr != '\0'))
	{
		syslog(LOG_ERR, "[processNumber] Invalid number: %s\n", num);
		rc = ParseInvalidNumber;
	} 
	else
		*val = n;
	
	return rc;
}

ParseStatus processRange(char *range, int *begin, int *end)
{
	ParseStatus rc = ParseOk;
	char *low = NULL, *high = NULL;
	
	if( (*range == '*') && (range[1] == '\0') )
	{
		*begin = -1;
		*end = -1;
	}
	else
	{
		low = range;
		
		if( (high = strchr(range, '-')) != NULL )
		{
			*high = '\0';
			++high;
		}
		
		if( (rc = processNumber(low, begin)) != ParseOk )
			return rc;
		
		if( high != NULL )
		{
			if( (rc = processNumber(high, end)) != ParseOk )
				return rc;
		}
		else
			*end = *begin;
	}
	
	return rc;
}

ParseStatus processSubElement(char *e, int *begin, int *end, int *step)
{
	ParseStatus rc = ParseOk;
	char *rangestr = NULL, *stepstr = NULL;
	
	rangestr = e;
	
	if( (stepstr = strchr(e, '/')) != NULL )
	{
		*stepstr = '\0';
		++stepstr;
	}
		
	if( stepstr != NULL )
	{
		if( (rc = processNumber(stepstr, step)) != ParseOk )
			return rc;
	}
	
	if( (rc = processRange(rangestr, begin, end)) != ParseOk )
		return rc;
	
	return rc;
}

ParseStatus processElement(char *spec, CronTime *t, int type)
{
	ParseStatus rc = ParseOk;
	char *strtokbuf = NULL;
	char *tok = NULL;
	char *buf = NULL;
	int i, low = 0, high = 0, begin = 0, end = -1, step = 1;
	
	switch(type)
	{
		case MIN: low = 0; high = 59; break;
		case HOU: low = 0; high = 23; break;
		case DOM: low = 1; high = 31; break;
		case MON: low = 1; high = 12; break;
		/* 0 and 7 are both Sunday */
		case DOW: low = 0; high = 7; break;
	}

	buf = spec;
	
	while( (tok = strtok_r(buf, ",", &strtokbuf)) != NULL )
	{
		buf = NULL;
		begin = -1;
		end = -1;
		step = 1;

		if( (rc = processSubElement(tok, &begin, &end, &step)) != ParseOk )
			return rc;	

		if( (begin == -1) && (end == -1) )
		{
			begin = low;
			end = high;
		}
		
		if( (begin > end) || (begin < low) || (end > high) )
			return ParseInvalidRange; 
		
		if( (step < 1) || ((step > 1) && (step >= (end - begin))) )
			return ParseInvalidStep; 
		
		if( (step > 1) && (begin == end) )
			return ParseStepNoEffect; 
		    
		i = begin;
		do {
			switch(type)
			{
				case MIN: setTimeMin(t, i); break;
				case HOU: setTimeHour(t, i); break;
				case DOM: setTimeDOM(t, i);  break;
				case MON: setTimeMonth(t, i); break;
				case DOW: setTimeDOW(t, i); break;
			}
			i += step;	
		} while (i <= end);
	}
	
	return rc;	
}

ParseStatus processTime(char *line, CronTime *t)
{
	ParseStatus rc = ParseOk;
	char *strtokbuf=NULL, *tok=NULL;

	if( (tok = strtok_r(line, " \t", &strtokbuf))== NULL ||
		(rc = processElement(tok, t, MIN))!=ParseOk )
	{
		return rc;
	}
	if( (tok = strtok_r(NULL, " \t", &strtokbuf))== NULL ||
		(rc = processElement(tok, t, HOU))!=ParseOk )
	{
		return rc;
	}
	if( (tok = strtok_r(NULL, " \t", &strtokbuf))== NULL ||
		(rc = processElement(tok, t, DOM))!=ParseOk )
	{
		return rc;
	}
	if( (tok = strtok_r(NULL, " \t", &strtokbuf))==NULL ||
		(rc = processElement(tok, t, MON))!=ParseOk )
	{
		return rc;
	}
	if( (tok = strtok_r(NULL, " \t", &strtokbuf))==NULL ||
		(rc = processElement(tok, t, DOW))!=ParseOk )
	{
		return rc;
	}
	return rc;
}

void setTimeMin(CronTime *t, int minute)
{
	if(minute < 30)
		t->min[0] |= (1 << minute);
	else
		t->min[1] |= (1 << (minute-30));
}

void setTimeHour(CronTime *t, int hour)
{
	t->hour |= (1 << hour);
}

void setTimeDOM(CronTime *t, int dom)
{
	t->dom |= (1 << dom);
}

void setTimeMonth(CronTime *t, int month)
{
	t->month |= (1 << (month-1));
}

void setTimeDOW(CronTime *t, int dow)
{
	t->dow |= (1 << (dow%7));
}
