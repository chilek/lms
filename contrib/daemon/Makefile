CFLAGS = -Wall -O2
MODULES = ethers hostfile dhcp oident dns cutoff payments traffic notify tc \
	    system pinger cbq-init \
	    ggnotify

all:
	for i in `echo $(MODULES)`; do make -C $$i ; done
	
clean:
	for i in `echo $(MODULES)`; do make -C $$i clean; done
	