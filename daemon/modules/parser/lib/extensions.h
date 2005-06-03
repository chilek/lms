#ifndef TSCRIPT_EXTENSIONS_H
#define TSCRIPT_EXTENSIONS_H

#include "values.h"

typedef tscript_value tscript_extension_func(tscript_value arg);

map_declaration(tscript_extension_map, char*, tscript_extension_func*);

void tscript_add_extension(char* keyword, tscript_extension_func* func);
void tscript_remove_extension(char* keyword);
int tscript_has_extension(char* keyword);
tscript_value tscript_run_extension(char* keyword, tscript_value arg);

#endif
