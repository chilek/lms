#ifndef MAP_H
#define MAP_H

#include "list.h"

/**
	map_declaration(N, K, T)
		N - map type name
		K - type of key
		T - type of value
	
	map_implementation(N, K, T, K_CONSTR, V_CONSTR, K_DESTR, V_DESTR, CMP)
		K - type of key
		T - type of value
		CMP - key comparision macro

	N* N_create()
		Creates new map
		returns: pointer to new map

	void N_free(N* map)
		Deletes map
		map - pointer to map

	T* N_add(N* map, K key, T value)
		map - pointer to map
		key - key to insert
		value - value to insert
		returns: pointer to value in the map

	int N_remove(N* map, K key)
		Removes key from map
		list - list
		key - key to remove
		returns: 1 if removed, 0 if not found

	T* N_ref(N* map, K key, T value)
		Finds a value or add it to list if not found.
		map - pointer to map
		key - key to find
		value - value to add if key not found
		returns: pointer to element

	K N_key(N* map, int index)
		map - pointer to map
		index - index
		returns: key

	T N_get(N* map, int index)
		map - pointer to map
		index - index
		returns: value

	int N_contains(N* map, K key)
		Checks if a map contains a specified key.
		map - pointer to map
		key - key to find
		returns: 1 if found, 0 if not

	int N_count(N* map)
		Counts number of elements in the map.
		map - pointer to map
		returns: number of elements

	N* N_duplicate(N* map)
		Duplicates map
		map - pointer to map
		returns: pointer to new map
**/

#define map_declaration_1(N)						\
	struct N##_elem;						\
	typedef struct N##_elem N##_elem;				\
	list_declaration_1(N##_elem);					\
	typedef struct N##_elem_list N;

#define map_declaration_2(N, K, T)					\
									\
	struct N##_elem							\
	{								\
		K key;							\
		T value;						\
	};								\
									\
	list_declaration_2(N##_elem);					\
									\
	N* N##_create();						\
	void N##_free(N* map);						\
	T* N##_add(N* map, K key, T value);				\
	int N##_remove(N* map, K key);					\
	T* N##_ref(N* map, K key, T value);				\
	K N##_key(N* map, int index);					\
	int N##_contains(N* map, K key);				\
	int N##_count(N* map);						\
	N* N##_duplicate(N* map);

#define map_declaration(N, K, T)					\
	map_declaration_1(N)						\
	map_declaration_2(N, K, T)


#define map_implementation(N, K, T, K_CONSTR, V_CONSTR,			\
	K_DESTR, V_DESTR, CMP)						\
									\
	N##_elem N##_elem_constr(N##_elem e)				\
	{								\
		N##_elem res;						\
		res.key = K_CONSTR(e.key);				\
		res.value = V_CONSTR(e.value);				\
		return res;						\
	}								\
									\
	void N##_elem_destr(N##_elem e)					\
	{								\
		K_DESTR(e.key);						\
		V_DESTR(e.value);					\
	}								\
									\
	int N##_elem_comp(N##_elem a, N##_elem b)			\
	{								\
		return CMP(a.key, b.key);				\
	}								\
									\
	list_implementation(N##_elem, N##_elem_constr,			\
		N##_elem_destr, N##_elem_comp);				\
									\
	N* N##_create()							\
	{								\
		return N##_elem_list_create();				\
	}								\
									\
	void N##_free(N* map)						\
	{								\
		N##_elem_list_free(map);				\
	}								\
									\
	T* N##_add(N* map, K key, T value)				\
	{								\
		N##_elem e;						\
		e.key = key;						\
		e.value = value;					\
		return &N##_elem_list_add(map, e)->value;		\
	}								\
									\
	int N##_remove(N* map, K key)					\
	{								\
		N##_elem e;						\
		e.key = key;						\
		return N##_elem_list_remove(map, e);			\
	}								\
									\
	T* N##_ref(N* map, K key, T value)				\
	{								\
		N##_elem e;						\
		e.key = key;						\
		e.value = value;					\
		return &N##_elem_list_ref(map, e)->value;		\
	}								\
									\
	K N##_key(N* map, int index)					\
	{								\
		return N##_elem_list_get(map, index)->key;		\
	}								\
									\
	T N##_get(N* map, int index)					\
	{								\
		return N##_elem_list_get(map, index)->value;		\
	}								\
									\
	int N##_contains(N* map, K key)					\
	{								\
		N##_elem e;						\
		e.key = key;						\
		return N##_elem_list_contains(map, e);			\
	}								\
									\
	int N##_count(N* map)						\
	{								\
		return N##_elem_list_count(map);			\
	}								\
									\
	N* N##_duplicate(N* map)					\
	{								\
		return N##_elem_list_duplicate(map);			\
	}

#endif
