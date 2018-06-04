#!/usr/bin/perl -w
require 'global.pl';

use Cwd qw(realpath);
BEGIN{
    push @INC,realpath()."/libs";
}
use SaClient;
use Data::Dumper;

our $conf_file = "/var/spool/cron/root";
our $conf_tmp = '/tmp/crontab.tmp';

my @addCrons = (
'* * * * * cd /home/t/system/gaea-client;/usr/bin/perl gaea_minute_sche.pl >> logs/gaea_minute_sche.log 2>&1',
);

my $hostname = `hostname`;
chomp($hostname);
#my $res = &postGaeaApi('ApiRole','act=getcron&hostname='.$hostname);
$res = "1";
if($res eq "1")
{
	$res = '';
}elsif($res eq ''){
	&msgExit(1,"api err");
}

my @roleCrons = split /\n/,$res;
if(@roleCrons > 0){
	foreach my $line (@roleCrons) {
		push @addCrons,$line;
	}
}

`touch $conf_file`;

`/bin/cp -f $conf_file $conf_tmp`;
&putBlockContents('autoCron',$conf_tmp,@addCrons);
$tmp_key = `/usr/bin/md5sum $conf_tmp`;
my($tk,$tc) = split / /,$tmp_key;
$conf_key = `/usr/bin/md5sum $conf_file`;
my($ck,$cc) = split / /,$conf_key;
    
#make logs dir
chdir("/home/t/system/gaea-client");
mkdir("logs", 0755);
mkdir("tools/logs", 0755);

#report主机信息
run_script('server_info.sh','scripts','',1);


if(!($tk eq $ck))
{
	my $now = `date +%s`;
	chomp $now;
	my $bakCmd = '/bin/cp -f '.$conf_file.' /tmp/crontab_bak.'.$now;
	system($bakCmd);
	&putBlockContents('autoCron',$conf_file,@addCrons);

    &msgExit(0,$now." update crontab");
}



sub getBlockContents()
{
	my $blockName = shift;
	my $file = shift;
	my @conts = &file($file);
	my @contents = ();
	my $isRead = '0';
	foreach $line (@conts)
	{
		chomp $line;
		if($line =~ /^[#]* $blockName START/m){
			$isRead = '1';
			next;
		}
		last if($line =~ /^[#]* $blockName END/m);
		push @contents,$line if($isRead);
	}
	return @contents;
}
&msgExit(0,'ok');

sub putBlockContents()
{
	my $blockName = shift;
	my $file = shift;
	my @contents = @_;
	my $isPut = '1';
	my @newFile = ();
	my @conts = &file($file);
	my $isBlock = '0';
	foreach $line (@conts){
		if($line =~ /^[\#]* $blockName START/m){
			$isBlock = '1';
			last;
		}
	}
	if(!$isBlock)
	{
		push @conts,"###### $blockName START ######\n";
		push @conts,"###### $blockName END ######\n";
	}
	foreach $line (@conts)
	{
	chomp $line;
	if($line =~ /^[#]* $blockName START/m){
		push @newFile,$line;
		foreach $blockLine (@contents){
			push @newFile,$blockLine;
		}
		$isPut = '0';
		next;
	}
	$isPut = '1' if($line =~ /^[#]* $blockName END/m);
	push @newFile,$line if($isPut && !&in_array($line,@contents));
	}
	$txt = join "\n",@newFile;
	&file_put_contents($file,$txt."\n");
}

sub msgExit($$)
{
	$res = shift;
	$msg = shift;
	print $res."|".$msg."\n";
	exit($res);
}
