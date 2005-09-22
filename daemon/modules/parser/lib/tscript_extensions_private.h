#ifndef TSCRIPT_EXTENSIONS_PRIVATE_H
#define TSCRIPT_EXTENSIONS_PRIVATE_H

#include "tscript_values.h"

typedef tscript_value* tscript_extension_func(tscript_value* arg);
typedef tscript_value* tscript_constant_func();

typedef struct
{
	tscript_extension_func* func;
	int min_args;
	int max_args;
	int block;
} tscript_extension;

typedef struct
{
	tscript_constant_func* func;
	int cached;
	tscript_value* value;
} tscript_constant;

map_declaration(tscript_extension_map, char*, tscript_extension);
map_declaration(tscript_constant_map, char*, tscript_constant);

#endif
