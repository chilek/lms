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
		struct tscript_value** reference_data;
		tscript_values_array* array_data;
//	};
	tscript_values_list* sub_variables;
} tscript_value;

map_declaration_2(tscript_values_array, tscript_value*, tscript_value*);
map_declaration_2(tscript_values_list, char*, tscript_value*);

tscript_value* tscript_value_create(tscript_value_type type, char* data);
tscript_value* tscript_value_create_error(const char* format, ...);
tscript_value* tscript_value_create_null();
tscript_value* tscript_value_create_number(double val);
tscript_value* tscript_value_create_string(char* str);
tscript_value* tscript_value_create_array();
tscript_value* tscript_value_create_reference(tscript_value** val);

void tscript_value_free(tscript_value* val);

/**
	Returns double type value of specified value after dereferencing.
	Returns 0 if val is not a number.
**/
double tscript_value_as_number(tscript_value* val);

/**
	Returns char pointer string type value of specified value
	after dereferencing.
	You should not free allocated memory pointed by returned pointer.
	Returns NULL if val is not a string or error.
**/
char* tscript_value_as_string(tscript_value* val);

/**
	Returns boolean type value of specified value after dereferencing.
	Strings with length greater than 0 are interpreted as TRUE.
	Numbers greater than 0 are interpreted as TRUE.
	Everything else is interpreted as FALSE.
**/
int tscript_value_as_bool(tscript_value* val);

/**
	Creates new value - number representing size of the array.
**/
tscript_value* tscript_value_array_count(tscript_value* val);

tscript_value** tscript_value_array_item_ref(tscript_value** val, tscript_value* index);
tscript_value* tscript_value_array_item_key(tscript_value* val, int index);
tscript_value* tscript_value_array_item_get(tscript_value* val, int index);
tscript_value** tscript_value_subvar_ref(tscript_value* val, char* name);

tscript_value* tscript_value_dereference(tscript_value* val);

/**
	Creates new value - string representation of specified value.
**/
tscript_value* tscript_value_convert_to_string(tscript_value* val);

/**
	Creates new value - number representation of specified value.
**/
tscript_value* tscript_value_convert_to_number(tscript_value* val);

/**
	Creates new value - copy of specified value.
**/
tscript_value* tscript_value_duplicate(tscript_value* val);

/**
	Adds to values. If one of them is not numeric convert both
	to strings and concatenate them.
**/
tscript_value* tscript_value_add(tscript_value* val1, tscript_value* val2);

/**
	Compares two values and checks if val1 equals val2.
	Two strings or one string and one number are compared as strings.
	Two numbers are compared as numbers.
	If two null values are compared the result is true.
	Other possibilities gives false result for now.
**/
int tscript_value_equals(tscript_value* val1, tscript_value* val2);

/**
	Compares two values and checks if val1 is less than val2.
	Strings are converted to numbers.
	Two numbers are compared as numbers.
	Other possibilities gives false result for now.
**/
int tscript_value_less(tscript_value* val1, tscript_value* val2);

/**
	Compares two values and checks if val1 is less than or equals val2.
	Strings are converted to numbers.
	Two numbers are compared as numbers.
	Other possibilities gives false result for now.
**/
int tscript_value_less_or_equals(tscript_value* val1, tscript_value* val2);

/**
	Returns string representation of value type, dereferencing it.
**/
tscript_value* tscript_value_type_string(tscript_value* val);

#endif
