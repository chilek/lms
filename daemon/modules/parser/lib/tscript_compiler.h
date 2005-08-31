#ifndef TSCRIPT_COMPILER_H
#define TSCRIPT_COMPILER_H

#include "tscript_ast.h"
#include <stdio.h>

int tscript_compile_stream(tscript_context* context, FILE* file);
int tscript_compile_string(tscript_context* context, const char* string);
int tscript_compile_stdin(tscript_context* context);
int tscript_compile_file(tscript_context* context, char* file_name);

#endif
