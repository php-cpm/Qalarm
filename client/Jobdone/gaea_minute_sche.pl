#!/usr/bin/perl
#
require('global.pl');
use Cwd qw(realpath);
BEGIN{
    push @INC,realpath()."/libs";
}
use SaClient;

# main
if(ifRunTime('* * * * *'))
{
}

if(ifRunTime('*/5 * * * *'))
{
     run_script('gaea_update.pl','tools','',1);
     run_script('monitor_salt.sh','tools','',1);
}
if(ifRunTime('59 23 * * *'))
{
     run_script('clean_log.sh','tools','',1);
}

if(ifRunTime('15 3 * * *'))
{
    sleep(rand(50));
    run_script('server_info.sh','scripts','',1);
}
