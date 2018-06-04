package SaClient;
require Exporter;

our @ISA  = qw(Exporter);
our @EXPORT  = qw(ifRunTime run_script);
our @VERSION = 1.00;
our %runer = (
	'sh'=>'/bin/bash',
	'php'=>'/usr/local/bin/php',
	'pl'=>'/usr/bin/perl',
	'py'=>'/usr/bin/python',
);
our $RUN_FAIL = 1;

sub ifRunTime
{
	my $now = time();
	@list_time = split / /,`date -d \@$now "+%H %M %S"`;
	local($hour,$min,$sec)=@list_time;
	chomp($sec);
	$crontime = shift;
	local @list = split(/ /,$crontime);
	if(@list != 5) {
		print "error @list\n";
		return 0;
	}
	local ($fm,$fh,$fd,$fy,$fw) = @list;
	local $rfm = $rfh = 0;
	local $rfd = $rfw = $rfy = 1;
	$rfm = &checkTime($fm,$min);
	$rfh = &checkTime($fh,$hour);
	if($rfm && $rfh && $rfd && $rfw && $rfy) {
		#print "$hour:$min:$sec $crontime run\n";
		return 1;
	} else {
		return 0;
	}
}

sub checkTime
{
	local $fm = shift;
	local $min = shift;
	local $rfm = 0;
	if(($fm eq '*') || ($fm eq '*/1')) {
		$rfm = 1;
	} elsif($fm =~ "/") {
		local($xing,$runm) = split(/\//,$fm);
		$rfm = 1 if(0 == ($min % $runm));
	} elsif($fm =~ /^\d+$/) {
		$rfm = 1 if($fm == $min);
	} elsif($fm =~ /,/) {
		local @fm_arr = split(/,/,$fm);
		foreach $temp (@fm_arr) {
			$rfm = 1 if($temp == $min);
		}
	} elsif($fm =~ /-/) {
		local($start,$end) = split(/-/,$fm);
		$rfm = 1 if(($min >= $start) && ($min <= $end));
	} else {
		$rfm = 0;
	}
	return $rfm;
}

#function
#argv: 1:script name 2:dir 3:arguments 4:bg run[1|0] 5 logtag
sub run_script
{
	push @_,'' while(@_ < 5);
	my($file,$dir,$canshu,$bg,$logtag) = @_;
	$dir =~ s#[/]+$##g;
	my $backdir = $dir;
	$backdir =~ s#\w+#\.\.#g;
	my $runtime = `date +%F" "%T`;
	my $str_time = time();
	chomp($runtime);
	my @list = split(/\./,$file);
	my $name = $list[0];
	my $suffix = $list[-1];
	if(!length($dir)) {
		$dir = './';
		$backdir = './';
	}
	if($bg) {
		$bg = '&';
	} else {
		$bg = '';
	}
	if(! -e $dir."/".$file) {
		print $runtime.'|0s|'.$RUN_FAIL.'|'."$dir $file not found\n";
		chdir($backdir);
		return $RUN_FAIL;
	}
	chdir($dir);
	if( ! -d './logs' ) {
		mkdir 'logs';
		`chown ttyc.ttyc logs`;
	}
	my $exer = $runer{$suffix};
	if(! -e $exer) {
		print $runtime.'|0s|'.$RUN_FAIL.'|'."$dir/$file\n";
		chdir($backdir);
		return $RUN_FAIL;
	}
	if ( "$logtag" eq "")
	{
		$logtag = `date +%Y%m%d`;
		chomp $logtag;
		$logtag = '_'.$logtag;
	}
	my $cmd = "$exer $file $canshu >> ./logs/$name$logtag.log 2>&1 $bg";
	my $res = system($cmd);
	my $end_time = time();
	my $use_time = $end_time - $end_time;
	print $runtime.'|'.$use_time.'s|'.$res.'|'."$cmd\n";
	chdir($backdir);
	return $res;
}

1;
