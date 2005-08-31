#ifndef NET_H
#define NET_H

#include "tscript_context.h"

void tscript_ext_net_init(tscript_context *);
void tscript_ext_net_close(tscript_context *);

int mask2prefix(const char *);
char *long2ip(const unsigned long);
unsigned long ip2long(const char *);
char *broadcast(const char *, const char *);

#endif
