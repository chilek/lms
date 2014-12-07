# Account payments
1 0 * * * www-data test ! -f /usr/sbin/lms-daemon && /usr/sbin/lms-payments

# Disable nodes of customers with dept
1 1 * * * www-data test ! -f /usr/sbin/lms-daemon && /usr/sbin/lms-cutoff
