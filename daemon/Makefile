
LIBS = -lpq -ldl
LDFLAGS = -L/usr/local/pgsql/lib
CFLAGS = -I/usr/local/pgsql/include -DUSE_PGSQL -DDEBUG0 -DDEBUG1
INSTALLDIR = /usr/local/bin


SOURCES = almsd.c db.c util.c
SOURCES += iniparser/iniparser.c iniparser/dictionary.c iniparser/strlib.c
OBJECTS = ${SOURCES:.c=.o}
OUT = almsd

all: $(OUT) xmodules

xmodules:
	make -C modules

$(OUT): ${OBJECTS}
	gcc $(OBJECTS) -o $(OUT) $(LDFLAGS) $(LIBS)
	strip $(OUT)
	rm -f $(OBJECTS)

