 /*
  *
  *  LMS Coffee Cup
  *
  *  (C) Copyright 2005 Marcin Król
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
  * $Id$
  */
			 
#include <sys/io.h>
#include <errno.h>

int podstawa = 0x3bc; // /dev/lp0
int zakres   = 8;
int czas     = 60;    // jedna minuta
int i;

int main ()	{
    printf("LMS Coffee Cup 0.1\n");
    printf("W³±czam ekspres do kawy.\n");
    if (ioperm(podstawa, zakres, 1)==-1) {
	printf("Wyst±pi³ b³±d: %s\n",strerror(errno));
	printf("Kawa NIE gotowa.\n");
	return errno;
    } else {
	outb(1, podstawa);
	printf("Proszê czekaæ....\n");
	for (i=1; i<11; i++) {
	    sleep(czas/10);
	    printf("%d%\n",i*10);
	}
	printf("Wy³±czam ekspres do kawy.\n");
	outb(0, podstawa);
	ioperm(podstawa, zakres, 0);
	printf("Kawa gotowa.\n");
	return 0;
    }
}