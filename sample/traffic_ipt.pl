#! /usr/bin/perl

# Script reads stats from iptables chain, so need to create it: 
# iptables -t mangle -N STAT
# iptables -t mangle -I FORWARD -j STAT
# and for each host following rules:
# iptables -t mangle -A STAT -d xxx.xxx.xxx.xxx
# iptables -t mangle -A STAT -s xxx.xxx.xxx.xxx
# -----------------------------------------------------------

my $log = '/var/log/traffic.log';	# logfile for lms-traffic
my $ipt = '/usr/local/sbin/iptables'; 	# iptables binary

# ----------Do not change anything below this line-----------
# -----------------------------------------------------------

# Read iptables counters
my @info = `$ipt -t mangle -L STAT -vnxZ`;
my %upload;
my %download;

foreach my $line (@info)
{
	chomp($line);
	if($line =~ /^[ ]+([0-9]+)[ ]+([0-9]+).*0.* 0\.0\.0\.0\/0[ ]+([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})/ )
	{
		$line =~ s/^[ ]+([0-9]+)[ ]+([0-9]+).*0.* 0\.0\.0\.0\/0[ ]+([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})/$1 $2 $3/g;
		my ($pkts, $bytes, $host) = split ' ',$line;
		$download{$host} = $bytes;
	}
	elsif($line =~ /^[ ]+([0-9]+)[ ]+([0-9]+).*0.* ([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})[ ]+0\.0\.0\.0\/0/ )
	{
		$line =~ s/^[ ]+([0-9]+)[ ]+([0-9]+).*0.* ([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})[ ]+0\.0\.0\.0\/0/$1 $2 $3/g;
		my ($pkts, $bytes, $host) = split ' ',$line;
		$upload{$host} = $bytes;
	}
	elsif($line =~ /^[ ]+([0-9]+)[ ]+([0-9]+).*all.* 0\.0\.0\.0\/0[ ]+([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})/ )
	{
		$line =~ s/^[ ]+([0-9]+)[ ]+([0-9]+).*all.* 0\.0\.0\.0\/0[ ]+([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})/$1 $2 $3/g;
		my ($pkts, $bytes, $host) = split ' ',$line;
		$download{$host} = $bytes;
	}
	elsif($line =~ /^[ ]+([0-9]+)[ ]+([0-9]+).*all.* ([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})[ ]+0\.0\.0\.0\/0/ )
	{
		$line =~ s/^[ ]+([0-9]+)[ ]+([0-9]+).*all.* ([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})[ ]+0\.0\.0\.0\/0/$1 $2 $3/g;
		my ($pkts, $bytes, $host) = split ' ',$line;
		$upload{$host} = $bytes;
	}
}

# Write stats to logfile
open(OUTFILE, ">$log") or die("Fatal error: Unable to write '$log'. Exiting.\n");

foreach my $host (keys %upload)
{
	print OUTFILE "$host\t$upload{$host}\t$download{$host}\n";
}

close(OUTFILE);
