#!/usr/bin/perl -w

use strict;
BEGIN{
    push @INC, "/home/t/system/gaea-client/libs";
}
use JSON;
use LWP;
use Encode;
use Digest::MD5;
use Fcntl qw(:flock SEEK_END);
use Digest::SHA qw(sha1_hex);
use POSIX qw(strftime); 

sub getScriptHost { return "10.10.161.60"; }
sub getApiName { return "api.gaea.ttyongche.com"; }
sub getApiIp { return "10.10.135.97:12810"; }
#sub getApiName { return "test.gaea.ttyongche.com:12810"; }

sub toolKitPath { 
    my $type = shift;
    my %paths = (
        1 => ['GAEA_CLIENT_SCRIPT', '/home/t/system/gaea-client/scripts'],
        2 => ['JOBDONE_OPS', '/data/ops/scripts'],
        3 => ['JOBDONE_DBA', '/data/ops/scripts/dba'],
    );

    return $paths{$type};
}

sub alarmRecivers { return '13658364971'; }

sub sendSms
{
    my $message = 'ip:'.&getips().'|time:'.strftime("%Y-%m-%d %H:%M:%S", localtime(time()));
    $message .= shift;
    my $mobile = &alarmRecivers();
    $mobile = &urlencode($mobile);
    $message = &urlencode($message);
    my $secret = 'ab37655347122540857b44950fca0ba3';
    my $timestamp = time();
    my $rand = int(rand(999999));
    my $sign = sha1_hex($secret.$timestamp.$rand);
    my $url = "http://10.10.135.97:12800/sms/send?from=gaea-client&mobiles=$mobile&content=$message";
    $url .= "&timestamp=$timestamp&rand=$rand&sign=$sign";
    `/usr/bin/curl -H"Host: notice.ttyongche.com" -s "$url"`;
}

sub sendMail
{
    my $to = shift;
    my $subject = shift;
    my $data = shift;
    my $txt = "From: root
    To: ".$to."
    Subject: $subject

    $data
    ";
    `echo "$txt"|/usr/sbin/sendmail "$to"`;
}

sub checklock{
	my $lockfile = shift;
	open(our $lock_fh,'>',$lockfile.".lock") or die($!);
	if(!flock($lock_fh,  LOCK_EX | LOCK_NB)){
		#print "$lockfile locked\n";
		exit;
	}
}

sub file_get_contents
{
	my ($file) = @_;
	open FD, '<', $file or die("$file open failed");
	local $/;
	my $contents = <FD>;
	close FD;
	return $contents;
}

sub file_put_contents
{
	my $file = shift;
	my $txt = shift;
	open(FH,">$file") or die("$file open failed");
	print FH $txt;
	close(FH);
}

sub file
{
	my $file = shift;
	my $i = 0;
	my @con;
	open(FH,$file) or die("$file open failed");
	while(<FH>)
	{
	$con[$i] = $_;
	$i++;
	}
	close(FH);
	return @con;
}

sub md5_file
{
	my $file = shift;
	open(FILE, $file) or die "Can't open '$file': $!";
	binmode(FILE);
	my $key = Digest::MD5->new->addfile(*FILE)->hexdigest;
	return $key;
	close(FILE);
}

sub urlencode()
{
	my $query = shift;
	$query =~ s/(\W)/sprintf("%%%02x", unpack("C", $1))/eg;
	return $query;
}

sub urldecode()
{
	my $query = shift;
	$query =~ tr/+/ /;
	$query =~ s/%([a-fA-F0-9][a-fA-F0-9])/pack("C", hex($1))/eg;
	return $query;
}

sub in_array
{
	my $val = shift;
	my @array = @_;
	foreach my $line(@array) {
		return 1 if($line eq $val);
	}
	return 0;
}

sub getips()
{
    my @ips = ();
    my @txt = `/sbin/ifconfig`;
    foreach my $line (@txt)
    {
        if ($line =~ m"addr:((\d+\.){3}\d+)")
        {
            next if($1 eq "127.0.0.1");
            push(@ips,$1);
        }
    }
    return join('|', @ips);
}

# api post
sub postGaeaApi()
{
    my %apis = (
        client_version  => 'api/v1/ops/clientversion',
        client_report   => 'api/v1/ops/clientreport',
    );
    my $apiName = shift;
    my $content = shift;
    my $postUrl = 'http://'.&getApiIp.'/'.$apis{$apiName};

    my $ua = LWP::UserAgent->new;
    $ua->timeout(10);
    $ua->agent("GaeaClient/1.0 ");
    my $req = HTTP::Request->new(POST => $postUrl);
    $req->content_type('application/x-www-form-urlencoded');
    $req->header("Host" => &getApiName);
    $req->content($content);
    my $res = $ua->request($req);
    my $errmsg = '';
    if ($res->is_success) {
        my $raw = decode_json($res->content);
        if ($raw->{'errno'} == 0) {
            return $raw->{'data'};
        } 

        $errmsg = '|errno:'.$raw->{'errno'}.'|errmsg:'.$raw->{'errmsg'}.'|'.$postUrl."\n";
    }

    if ($errmsg eq '') {
        $errmsg = '|errno:'.$res->status_line.'|errmsg:'.$errmsg.'|'.$postUrl."\n";
    }
    &sendSms($errmsg);
    print $errmsg;
    return 0;
}

1;
