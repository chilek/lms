pkglib_LTLIBRARIES = dns.la
dns_la_SOURCES = dns.c
dns_la_LDFLAGS = -no-undefined -module -avoid-version
dns_la_CFLAGS=@LMS_CONF_DIR@


dns_la_sampledir = $(DESTDIR)@sysconfdir@/lms/daemon/dns/sample
dns_la_sample_DATA = sample/named.conf

dns_la_sampleforwarddir = $(DESTDIR)@sysconfdir@/lms/daemon/dns/sample/forward
dns_la_sampleforward_DATA = sample/forward/generic

dns_la_samplereversedir = $(DESTDIR)@sysconfdir@/lms/daemon/dns/sample/reverse
dns_la_samplereverse_DATA = sample/reverse/generic

install-data-local:
	$(MKDIR_P) $(DESTDIR)@sysconfdir@/lms/daemon/dns/sample/out/forward $(DESTDIR)@sysconfdir@/lms/daemon/dns/sample/out/reverse
