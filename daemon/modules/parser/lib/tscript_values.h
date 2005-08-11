#ifndef TSCRIPT_VALUES_H
#define TSCRIPT_VALUES_H

#include "map.h"

typedef enum tscript_value_type
{
	TSCRIPT_TYPE_ERROR,
	TSCRIPT_TYPE_NULL,
	TSCRIPT_TYPE_REFERENCE,
	TSCRIPT_TYPE_NUMBER,
	TSCRIPT_TYPE_STRING,
	TSCRIPT_TYPE_ARRAY
} tscript_value_type;

map_declaration_1(tscript_values_array);
map_declaration_1(tscript_values_list);

typedef struct tscript_value
{
	tscript_value_type type;
/*	union
	{*/
		char* data;
		struct tscript_value* reference_data;
		tscript_values_array* array_data;
//	};
	tscript_values_list* sub_variables;
} tscript_value;

map_declaration_2(tscript_values_array, int, tscript_value);
map_declaration_2(tscript_values_list, char*, tscript_value);

tscript_value tscript_value_create(tscript_value_type type, char* data);
tscript_value tscript_value_create_error(const char* format, ...);
tscript_value tscript_value_create_null();
tscript_value tscript_value_create_number(double val);
tscript_value tscript_value_create_string(char* str);
tscript_value tscript_value_create_array();
tscript_value tscript_value_create_reference(tscript_value* val);

tscript_value tscript_value_array_count(tscript_value* val);
tscript_value* tscript_value_array_item_ref(tscript_value* val, int index);
tscript_value* tscript_value_subvar_ref(tscript_value* val, char* name);
tscript_value tscript_value_dereference(tscript_value val);

tscript_value tscript_value_convert_to_string(tscript_value val);
tscript_value tscript_value_convert_to_number(tscript_value val);

#endif
