
LIBS = -lmysqlclient -ldl /usr/lib/libgadu.so
CFLAGS = -I/usr/include -DUSE_MYSQL -I../.. -I/usr/src/ekg/lib
LDFLAGS = -L/usr/lib
INSTALLDIR = /usr/local/bin


SOURCES = ggnotify.c
OBJECTS = ${SOURCES:.c=.o}

OUT = ggnotify.so

all: $(OUT)

$(OUT): ${OBJECTS}
	gcc -shared $(OBJECTS) -o $(OUT) $(LDFLAGS) $(LIBS)
	rm -rf $(OBJECTS)

clean:
	rm -rf $(OUT)

