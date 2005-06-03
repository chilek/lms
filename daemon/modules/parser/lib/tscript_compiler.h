#ifndef TSCRIPT_VARIABLES_H
#define TSCRIPT_VARIABLES_H

#include <stdio.h>

int tscript_compile_stream(FILE* file);
int tscript_compile_string(const char* string);
int tscript_compile_stdin();
int tscript_compile_file(char* file_name);

#endif
