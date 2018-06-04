#!/usr/bin/perl -w

require('global.pl');

sub usage
{
	print "Usage: perl $0 [ file | -c text ] <key> <dir>\n";
	exit;
}

if(@ARGV < 2)
{
	usage();
}

my $txt = '';
my $key = '';
my $file = '';
my $filename = '';
my ($dir,$ret) = '';

if ($ARGV[0] eq "-c") {
	$txt = $ARGV[1];
	$key = $ARGV[2];
	$ret = 0;
} else {
	$file = $ARGV[0];
	$key = $ARGV[1];
	$dir = $ARGV[2];
	$ret = $ARGV[3];
	if(!$dir) {
		$dir = '.';
	}
	my @list = split(/\./,$file);
	if(length($key)) {
		$filename = $list[0].".".$key;
	} else {
		$filename = $list[0];
	}
	$filename = $dir."/logs/".$filename.".log";
	if ( ! -e $filename) {
		$txt = "$filename not found";
	} else {
		$txt = &file_get_contents($filename);
		$txt = &urlencode($txt);
	}
}

my $hostname = `/bin/hostname`;
my $ips      = &getips();
chomp $hostname;
chomp $ips;

my $content = "report_type=$key&params=$txt&hostname=$hostname&ips=$ips&res=$ret";
&postGaeaApi('client_report',"$content");
