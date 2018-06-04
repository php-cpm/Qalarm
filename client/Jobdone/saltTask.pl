#!/usr/bin/perl

require('global.pl');
use Cwd qw(realpath);
BEGIN{
    push @INC,realpath()."/libs";
}
use Digest::MD5;
use Data::Dumper;
use MIME::Base64; 
use JSON;
use Cwd;
$num = @ARGV;
if($num < 2)
{
	&usage();
}

my %params = &getParams();

our $workPath = &toolKitPath($params{'owner'});

if($params{'timeout'} > 0)

{
	my $PID = $$;
	$check_cmd = '/usr/bin/perl saltTaskCheck.pl '.$PID.' '.$params{'timeout'}.' >/dev/null 2>&1 &';
	system($check_cmd);
}
$res = &run_script($params{'name'},$params{'path'},$params{'sargv'},$params{'filekey'});
exit $res;
sub usage()
{
	print "Usage: $0 [filename] [filepath] [argv] [timeout] [filekey] rstcode:2";
	exit;
}

sub getParams()
{
	my %file;
    $file{'owner'} = $ARGV[0];
	$file{'path'}  = $ARGV[1];
	$file{'name'}  = $ARGV[2];
	$file{'sargv'} = $ARGV[3];
	$file{'timeout'} = $ARGV[4];
	$file{'filekey'} = $ARGV[5];
	return %file;
}

sub getSaltMaster()
{
	$master = `grep "^master:" /etc/salt/minion 2>/dev/null`;
	chomp $master;
	$master =~ s/ //g;
	@list = split ':',$master;
	if ( $list[1] eq "")
	{
		return '10.10.161.60';
	}
	return $list[1];
}

sub syncFile()
{
	our $workPath;
	$filepath = shift;
	$filename = shift;

	$rsync_mod = @$workPath[0];
	$filepath =~ s#^/|/+$##g;
	$rsync_path = $rsync_mod.'/*';
	my $scriptDir = @$workPath[1].'/';

	if( ! -d $scriptDir )
	{
		$cmd2 = '/bin/mkdir -p '.$scriptDir;
		$res = system($cmd2);
	}
	$cmd = '/usr/bin/rsync -ar --exclude=.*.swp --exclude=.svn --timeout=20 '.&getSaltMaster.'::'.$rsync_path.' '.$scriptDir.' >/dev/null 2>&1';
	$res = system($cmd);
	if($res)
	{
		&set_log_msg($cmd.' '.$res);
        sleep rand(5);
        $res = system($cmd);
        &set_log_msg($cmd.' '.$res);
        return 0 if ($res);
	}
	return 1;
}

sub get_runner
{
	$file = shift;
	my %runer = (
        	'sh'=>'/bin/bash',
        	'php'=>'/usr/local/bin/php',
        	'php2'=>'/usr/local/php/bin/php',
        	'pl'=>'/usr/bin/perl',
	);
	my @list = split(/\./,$file);
	my $name = $list[0];
        my $suffix = $list[-1];
	my $exer = $runer{$suffix};
	if(("$suffix" eq "php") && (! -e $exer))
	{
		$exer = $runer{'php2'};
	}
	if(( "$exer" eq "") || (! -e $exer))
	{
		return ''
	}
	return $exer;
}

sub md5_file
{
        my $file = shift;
        open( FILE, $file ) || return '';
        binmode(FILE);
        my $md5 = Digest::MD5->new;
        map { $md5->add($_) } <FILE>;
        close(FILE);
        return $md5->hexdigest;
}

sub check_file
{
	my $file = shift;
	my $dir = shift;
	my $md5key = shift;
	my $realfile = $dir."/".$file;

	return 0 if(! -e $realfile);
	return 2 if("$md5key" eq "");
	my $key = &md5_file($realfile);
	return 1 if(($key) && ("$key" eq "$md5key"));
	return 0;
}

sub run_script
{
	our $workPath;
	push @_,'' while(@_ < 4);
	my($file,$dir,$canshu,$filekey) = @_;
	$canshu = decode_base64($canshu);
	$dir =~ s#[/]+$##g;
	$scriptDir = @$workPath[1].'/'.$dir;
	
    $backdir = getcwd();
	my $str_time = time();
	if(&check_file($file,$scriptDir,$filekey) == 0) {
        # rsync files
        $res = &syncFile($dir, $file);
        if($res == 0)
        {
            &set_log_msg("rsync file $params{'name'} failed");
            print "rsync scripts failed rstcode:2";
            exit;
        }
	}
	
    if(&check_file($file,$scriptDir,$filekey) == 0) {
        print "$file md5 check failed rstcode:1";
        exit;
	}
	chdir($scriptDir);
	my $exer = &get_runner($file);
	if("$exer" eq "")
	{
		`chmod 755 $file`;
		$file = './'.$file;
		$exer = '';
	}
	my $cmd = "$exer $file $canshu 2>&1";
	my $res = system($cmd);
	if($res == 9)
	{
		print 'rstcode:'.$res;
	}else{
		$res = $res >> 8;
		print 'rstcode:'.$res;
	}
	my $end_time = time();
	my $use_time = $end_time - $str_time;
	chdir($backdir);
	my $msg = "useTime:".$use_time."s rstcode:$res ". $scriptDir .' '.$cmd;
	&set_log_msg($msg);
	return $res;
}

sub set_log_msg()
{
	my ($msg) = @_;
	my $runtime = `date +%F" "%T`;
	my $logtag = `date +%Y%m%d`;
	chomp $logtag;
	chomp $runtime;
	`echo "$runtime $msg" >> ./logs/saltTask_$logtag.log`;
}

