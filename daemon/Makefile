
LIBS = -lpq -ldl
LDFLAGS = -L/usr/local/pgsql/lib
CFLAGS = -I/usr/local/pgsql/include -DUSE_PGSQL
INSTALLDIR = /usr/local/bin

SOURCES = almsd.c db.c util.c
SOURCES += iniparser/iniparser.c iniparser/dictionary.c iniparser/strlib.c
OBJECTS = ${SOURCES:.c=.o}
OUT = almsd

all: $(OUT) xmodules

xmodules:
	make -C modules

daemon:
	make $(OUT)

$(OUT): ${OBJECTS}
	gcc $(OBJECTS) -o $(OUT) $(LDFLAGS) $(LIBS)
	strip $(OUT)

install: 
	mkdir -p -m 755 $(INSTALLDIR)
	install -c -m 700 almsd $(INSTALLDIR)/
	 
remove:
	rm -f $(INSTALLDIR)/almsd
	
clean:
	rm -f $(OBJECTS)
	make -C modules clean