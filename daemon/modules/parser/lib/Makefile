CC=gcc
AR=ar
FLEX=flex
BISON=bison
STRIP=strip
SOEXT=.so

CFLAGS=-O2

#######################################################################

### MINGW
ifneq ($(findstring mingw,$(CC)),)
LDFLAGS		+= -liberty
SOEXT		= .dll
else
SOEXT		= .so
endif

HEADERS=ast.h tscript_compiler.h interpreter.h variables.h parser.tab.h values.h extensions.h debug.h list.h map.h
OBJECTS=ast.o tscript_compiler.o interpreter.o variables.o parser.tab.o values.o extensions.o debug.o lexical.o

all: tscript.a tscript$(SOEXT)

tscript$(SOEXT): $(OBJECTS)
	$(CC) -shared -o tscript$(SOEXT) $(OBJECTS) $(LDFLAGS)
	$(STRIP) tscript$(SOEXT)

tscript.a: $(OBJECTS)
	$(AR) rs tscript.a $(OBJECTS)

$(OBJECTS): %.o: %.c $(HEADERS)
	$(CC) -c $< $(CFLAGS)

lexical.c: lexical.yy $(HEADERS)
	$(FLEX) -olexical.c -Ptscript_yy lexical.yy 

parser.tab.h: parser.y $(HEADERS)
	$(BISON) -d parser.y -p tscript_yy

clean:
	rm -f tscript.a tscript.so tscript.dll *.o lexical.c parser.tab.c parser.tab.h
