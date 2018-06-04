#!/usr/bin/perl -w
# 通过版本号自动更新gaea-client

require '../global.pl';

use Data::Dumper;
my $versionFile = "../version";
my $update = 1;
my $res = &postGaeaApi('client_version','');

if ($res == 0) {
	print "api error";
	exit;
}
my $newVer = $res->{'version'};
my $DEBUG = 0;

if(-e $versionFile)
{
	$nowVer = file_get_contents($versionFile);
	$update = 0 if($nowVer eq $newVer);
}

if($update)
{
	my @hosts = &getRsyncHosts();
	my $res = '';
	foreach my $host (@hosts)
	{
		$res = &update_gaea($host);
		if ($res == 0)
		{
			exit;
		}
	}
}


sub getRsyncHosts()
{
	my @hosts = ();
    if (-e "/etc/salt/minion")
    {
        $master = `grep "^master:" /etc/salt/minion 2>/dev/null`;
        chomp $master;
        $master =~ s/ //g;
        @list = split ':',$master;
        $hosts[0] = $list[1];
    }
    if (!$hosts[0])
    {
        $hosts[0] = &getScriptHost();
    }else{
        $hosts[1] = &getScriptHost();
    }
    if ($DEBUG) 
    {
        print Dumper(@hosts);
    }
    return @hosts;
}

sub update_gaea()
{
    my $host = shift;
    $cmd = "rsync --timeout=10 -ar --exclude=.svn --exclude=.*.swp ".$host."::GAEA_CLIENT/ /home/t/system/gaea-client";
    $res = system($cmd);
    print $cmd." $res\n" if($DEBUG);
    if($res == 0)
    {
        file_put_contents($versionFile,$newVer);
        `chown ttyc.ttyc -R /home/t/system/gaea-client`;
		print "update to ".$newVer."\n";
	}
	return $res;
}
