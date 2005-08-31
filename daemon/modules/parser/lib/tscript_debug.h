#ifndef TSCRIPT_DEBUG_H
#define TSCRIPT_DEBUG_H

#include <stdarg.h>
#include "tscript_context.h"

void tscript_set_debug_callback(tscript_context* context, tscript_debug_callback* callback);
void tscript_debug(tscript_context* context, const char* format, ...);
void tscript_internal_error(const char* format, ...);
char* tscript_compile_error(tscript_context* context);

#endif
