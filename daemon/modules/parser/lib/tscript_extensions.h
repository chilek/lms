#ifndef TSCRIPT_EXTENSIONS_H
#define TSCRIPT_EXTENSIONS_H

#include "tscript_values.h"

typedef tscript_value tscript_extension_func(tscript_value arg);
typedef tscript_value tscript_constant_func();

typedef struct
{
	tscript_constant_func* func;
	int cached;
	tscript_value value;
} tscript_constant;

map_declaration(tscript_extension_map, char*, tscript_extension_func*);
map_declaration(tscript_constant_map, char*, tscript_constant);

void tscript_add_extension(char* keyword, tscript_extension_func* func);
void tscript_remove_extension(char* keyword);
int tscript_has_extension(char* keyword);
tscript_value tscript_run_extension(char* keyword, tscript_value arg);

void tscript_add_constant(char* keyword, tscript_constant_func* func);
void tscript_remove_constant(char* keyword);
int tscript_has_constant(char* keyword);
tscript_value tscript_run_constant(char* keyword);

#endif
