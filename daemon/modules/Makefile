
MODULES = ethers firewall hostfile dhcp

all:
	for i in `echo $(MODULES)`; do make -C $$i ; done
	
clean:
	for i in `echo $(MODULES)`; do make -C $$i clean; done
	