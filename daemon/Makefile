
LIBS = -lmysqlclient -ldl
LDFLAGS = -L/usr/lib
CFLAGS = -I/usr/include -DUSE_MYSQL -Wall -O2
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