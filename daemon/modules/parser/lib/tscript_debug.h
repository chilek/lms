#ifndef TSCRIPT_DEBUG_H
#define TSCRIPT_DEBUG_H

void tscript_set_verbose(int verbose);
void tscript_debug(const char* format, ...);
void tscript_internal_error(const char* format, ...);
char* tscript_compile_error();

#endif
