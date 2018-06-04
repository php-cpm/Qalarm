#!/usr/bin/perl

require('global.pl');

my $ids = $ARGV[0];
my $taskCode = $ARGV[1];
exit if(!$ids);

$url = "http://".&getApiHost."/ApiGetAuthKey/?ids=$ids";
$cmd = "curl -s $url -H 'Host: ".&getApiName."'";
$res = `$cmd`;
my @files = split /\n/,$res;

if(!@files)
{
    print time()."$taskCode $ids null \n";
    exit;
}
foreach my $line (@files) {
    chomp($line);
    my @info = split /:/,$line;
    my @data = split / /,$info[1];
    my $checkDir = "/home/$info[0]/.ssh";
    my $authfile = $checkDir."/authorized_keys";
    if ( -d "/home/$info[0]") {
        if(! -d "$checkDir"){
            `mkdir $checkDir && chown $info[0].$info[0] $checkDir`;
        }
    }else{
        $txt .= "$info[0] user not found\n";
        next;
    }
    if( ! -e $authfile) {
        `> $authfile && chmod 600 $authfile && chown $info[0].$info[0] $authfile`;
    }

    my @keys = &getUserKyes($info[0]);
    if(&in_array($data[2],@keys)) {
        $txt .= "$data[2] key exists\n";
    } else {
        my $cmd = "echo \"$info[1]\" >> $authfile";
        $res = system($cmd);
        if(!$res) {
            $txt .= "$data[2] key add succeed\n";
        }
    }
}

#system("/usr/bin/perl post_res.pl -c \"$txt\" $taskCode") if($taskCode);
print $txt;

sub getUserKyes {
    my $user = shift;
    my $authfile = "/home/$user/.ssh/authorized_keys";
    my @exists;
    my @keys = &file($authfile);
    foreach my $sline (@keys) {
        my @sdata = split / /,$sline;
        chomp($sdata[2]);
        push(@exists,$sdata[2]);
    }
    return @exists;
}

