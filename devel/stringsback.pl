#!/usr/bin/perl
use strict;

&pomocy if $ARGV[0]=~/^(-h|--help)$/;
&pomocy('Plik oryginalow nie daje siê czytac: '.$ARGV[0]) unless open(WE,$ARGV[0]);
&pomocy('Plik zmian nie daje siê czytac: '.$ARGV[1]) unless open(WY,$ARGV[1]);
shift; shift;

my (@we,@wy,$line,$max,$dir,$file,@dirs,@files,);
foreach $line (<WE>) { push(@we,$line); }
foreach $line (<WY>) { push(@wy,$line); }
print 'Zaladowalem oryginaly: '.scalar(@we)."\n";
print 'Zaladowalem zmiany: '.scalar(@wy)."\n";
if (scalar(@we) ne scalar(@wy)) { &pomocy('Liczba lancuchow do podmiany nie odpowiada liczbie oryginalow.'); }
$max=0;
foreach $max (0 .. $#we) {
	next if $we[$max] eq '';
	chomp($we[$max]);
	chomp($wy[$max]);
	if ($we[$max] eq $wy[$max]) {
		delete($we[$max]);
		delete($wy[$max]);
	} else {
		print "Zmiana #$max: \"".$we[$max].'" => "'.$wy[$max].'"'."\n";
	}
}
print 'Usunalem niezmienione, pozostalo lancuchow: '.scalar(@we)."\n";
&pomocy('Nie pozostalo nic do podmiany...') if (scalar(@we) eq 0);
if (@ARGV eq 0) { 
	@dirs=('../lib','../lib/locale/pl/','../modules','../templates');
	print "Zmieniam standardowe katalogi: @dirs\n";
} else {
	@dirs=@ARGV;
	print "Zmieniam katalogi: @ARGV\n";
}
print 'Wci¶nij ENTER aby kontynuowac, Ctrl-C aby przerwac ... ';
read(STDIN,$line,1);

foreach $dir (@dirs) {
	print "Wchodze do katalogu: $dir\n";
	opendir(DIR,$dir);
	@files=grep { -f "$dir/$_" } readdir(DIR);
	closedir(DIR);
	foreach $file (@files) {
		my $modified=0;
		open(READFILE,"$dir/$file") or &pomocy("Nie moge otworzyc do odczytu: $dir/$file");
		open(RITEFILE,">$dir/$file.tmp") or &pomocy("Nie moge otworzyc do zapisu: $dir/$file.tmp");
		while (<READFILE>) {
			foreach $max (0 .. $#we) {
				next if $we[$max] eq '';
				my $od=$we[$max];
				my $do=$wy[$max];
				$od=~s/\\//g if $file=~/\.(html|tmpl)$/;
				$do=~s/\\//g if $file=~/\.(html|tmpl)$/;
				$od=~s/\\/\\\\/g;
				$od=~s/\$/\\\$/g;
				$od=~s/\(/\\\(/g;
				$od=~s/\)/\\\)/g;
				$od=~s/\[/\\\[/g;
				$od=~s/\]/\\\]/g;
				chomp;
				# print "$dir/$file: '$_' <=> '$od' => '$do'"; #D
				if ((/['"]$od['"]/) or (/\{t\}$od\{\/t\}/)) {
					$modified++;
					s/$od/$do/;
				}	
				# print " ==> '$_'\n"; #D
			}
			print RITEFILE $_."\n";
		}
		close(READFILE);
		close(RITEFILE);
		print "Zmodyfikowalem $modified wp. w $dir/$file\n" if $modified;
		rename("$dir/$file.tmp","$dir/$file") if $modified;
		unlink("$dir/$file.tmp") if not $modified;
	}
}
print "Uff. Zrobione.\n";


sub pomocy {
	my $err=shift;
	if ($err) { print "[4mPRZERWANIE: $err[0m\n"; }
	print <<END;
stringsback.pl <oryginal> <zmiany> [ <katalog> [katalog] ... ]

Aktualizuje stringi w modulach i szablonach uwzgl. zmiany w tlum. ang.

Parametry:
	- oryginal	- plik strings.txt przed zmianami
	- zmiany	- plik strings.txt ze zmianami w tlumaczeniu
	- katalog	- katalog(i), ktore trzeba przejechac zmianami

Zarowno pliki oryginal jak i zmiany musza miec te sama ilosc linii.
Program sprawdza to przed startem i odmowi pracy jesli ta liczba bedzie
sie roznic.
	
Jesli nie podasz katalogow domyslnie przejechane zostana:
	../lib ../lib/locale/pl/ ../modules ../templates

stringsback.pl v0.99 <kondi\@kondi.net> for LMS (GPLv2)
END
	exit(($err)?1:0);
}
