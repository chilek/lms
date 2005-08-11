#ifndef NET_H
#define NET_H

void tscript_ext_net_init();
void tscript_ext_net_close();

int mask2prefix(const char *);
char *long2ip(const unsigned long);
unsigned long ip2long(const char *);
char *broadcast(const char *, const char *);

#endif
