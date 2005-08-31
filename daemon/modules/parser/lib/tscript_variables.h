#ifndef TSCRIPT_VARIABLES_H
#define TSCRIPT_VARIABLES_H

#include "tscript_context.h"
#include "tscript_values.h"
#include "map.h"

map_declaration(tscript_variables_map, char*, tscript_value*);

tscript_value** tscript_variable_get_reference(tscript_context* context, char* name);

#endif
