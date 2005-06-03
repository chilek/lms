#ifndef TSCRIPT_DEBUG_H
#define TSCRIPT_DEBUG_H

extern int tscript_verbose;

void tscript_debug(const char* format, ...);
void tscript_internal_error(const char* format, ...);
void tscript_runtime_error(const char* format, ...);

#endif
