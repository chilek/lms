#!/usr/bin/perl -w

use strict;

# for searching strings in smarty templates.
# I don't know how it will be working but i hope quite well :)
# Using recurention searches strings in specified template, and
# in all included templates.
# It gets two parametrs
# 1. file name
# 2. Pointer to list to add new values into:
# Returns
# new list with new values;
sub parse_html()
{
    my $plik = shift;
    my $list = shift;
    my $force = shift;
    open (FILE, "../templates/$plik") or print "Error reading file ../templates/$plik: $!\n";
    my @file = <FILE>;
    close (FILE);
    foreach my $l (@file)
    {

	if ($l =~ /{include file="([a-z]+.html)"}/)
	{
	    #we dont analize global files header.html and footer.html unless we want to
	    if ((($1 ne 'header.html') and ($1 ne 'footer.html')) or ($force==1)) 	
		{$list = &parse_html ("$1", $list, $force);}
	}
	if (($l =~ /\{t[^}]*\}([^{]*)\{\/t}/) or ($l =~ /text="(.*?[^\\])"/))
	{
	    my $f = $1;
	    $f =~ s/\\\$/\$/g;		# $ -> \$
	    $f =~ s/\\/\\\\/g;		# \ -> \\
    	    $f =~ s/\x27/\\\x27/g;	# ' -> \'
	    push (@$list, $f);
	}
    }    
    return $list;
}

# function is parsing modules it searches strings to translation, function calls
# and templates to display. Then executes special function for searching dependent
# strings in LMS.class.php and templates. 
# It gets two parameters:
# 1. module name (ie 'addbalance.php')
# 2. Pointer to list to add new strings;

sub parse_module()
{
    my $path = shift;
    my $module = shift;
    my $list = shift;
    open (MODULE, "$path/$module");
    foreach my $l (<MODULE>)
    {
        if ($l =~ /\$SMARTY->display\('(.+\.html)'\);/)
        {
	    my $list = &parse_html ($1, $list, 0);
	}
	if ($l =~ /trans\(\x27(.*?[^\\])\x27/)
	{
	    push (@$list, $1);
	}
	if ($l =~ /\$LMS *-> *([a-zA-Z]+)/)
	{
	    $list = &parse_LMS_class ($1, $list);
	}
    }
    close (MODULE);

    &save_list ($module, $list);
}


# function is searching in LMS.class.php specified procedures, then it searches tralation strings
# and other function calls. And then by recurention it shuld return all used translation strings. 
# There are two parameters:
# 1. Function name
# 2. List to add new possitions
# Returns
# new list with new values;
sub parse_LMS_class()
{
    my $function = shift;
    my $list = shift;
    my $isstart = 0;
    my $level = -1;
    open (LMSCLASS, "../lib/LMS.class.php") or die ;
    my @lmsclass = <LMSCLASS>;
    close (LMSCLASS);
    @lmsclass = reverse (@lmsclass);				#for future pop
    while ((my $line = pop(@lmsclass)) and ($level != 0))
    {
	if ($line =~ /function $function *\(/)
	{
	    $isstart = 1;
	}
	if (($line =~ /{/) and ($isstart == 1))
	{
	    if ($level == -1) {$level=0}
	    $level++;
	}
	if (($line =~ /}/) and ($isstart == 1))
	{
	    $level--;
	}
	if (($line =~ /trans\('(.+)'\);/) and ($isstart == 1))
	{
	    push (@$list, $1);
	}
	if (($line =~ /\$this *-> *([a-zA-Z]+) *\(/) and ($isstart == 1))
	{
	    $list = &parse_LMS_class ($1, $list);
	}
    }
    return ($list);
}


# only for saving list
# (after sorting and with removing duplicated values)
# function requires module name, and list pointer
sub save_list()
{
    my $module = shift;
    my $list = shift;
    my $prv = "";
    open (STRINGS, " > ./strings/$module");
    if (defined (@$list))
    {
	@$list = sort (@$list);
	foreach my $line (@$list)
	{
	    if ($line ne $prv)
	    {
	        print STRINGS "\$_LANG['$line'] = '$line'\n";
	        $prv = $line;
	    }
	}
    }
    close (STRINGS);
}


opendir (HANDLE, "../modules/") || die "Cannot opendir: $!";
my @list;
foreach my $module (sort readdir(HANDLE))
{
    if ( $module =~ /(.+\.php$)/)
    {
        print "Analizing: $1\n";
        &parse_module ('../modules', $1, @list);
    }
}

# okay lets analize global strings

print "Analizing globals\n";

my $global_list;
$global_list = &parse_html ('header.html', $global_list, 1);
$global_list = &parse_html ('footer.html', $global_list, 1);
$global_list = &parse_module ('..', 'index.php', $global_list);

