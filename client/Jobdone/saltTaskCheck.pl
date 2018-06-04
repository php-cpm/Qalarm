#!/usr/bin/perl -w

use Data::Dumper;

&usage() if(@ARGV < 2);
&checkTimeout();


## function
sub checkTimeout()
{
	my %param = &_getParams();

	for($i=0;$i<$param{'timeout'};$i++)
	{
		my @pidlist = &_getPidTree($param{'pid'});
		exit if(@pidlist == 0);
		sleep 1;
	}

	my @pidlist = &_getPidTree($param{'pid'});
	die('Too many pid') if(@pidlist > 10);
	foreach $pid (@pidlist)
	{
		next if($pid == $param{'pid'});
		next if($pid < 100);
		$cmd = "/usr/bin/kill -9 $pid";
		system($cmd);
	}
}

sub usage()
{
	print "Usage: $0 \$pid \$timeout\n";
	exit;
}

sub _getParams()
{
	my %param;
	$param{'pid'}  = $ARGV[0];
	$param{'timeout'}  = $ARGV[1];
	&usage() if(!$param{'timeout'});
	&usage() if(!$param{'pid'});
	return %param;
}

sub _getPidTree()
{
	my $pid = shift;
	exit if(!$pid);
	exit if($pid < 100);
	my $res = `/usr/bin/pstree -p $pid`;
	#print Dumper($res);
	$res =~ s/\D/ /g;
	$res =~ s/[ ][ ]*/ /g;
	$res =~ s/^[ ]*|[ ]*$//g;
	my @pidlist = split(/ /,$res);
	#print Dumper(@pidlist);
	return @pidlist;
}

