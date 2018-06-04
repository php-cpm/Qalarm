#!/bin/bash

sysplan=`head -1 /etc/issue`
memtotal=`cat /proc/meminfo |grep MemTotal|awk '{print $2}'`
cpu=`grep "model name" /proc/cpuinfo |head -1|sed "s/.*CPU//g;s/ //g"`
cpu_num=`grep "physical id" /proc/cpuinfo |wc -l`
if [ $cpu_num == "0" ];then  
    cpu_num=`grep "processor" /proc/cpuinfo |wc -l`
fi
kernal=`uname -a |awk '{print $3}'`
disk=`fdisk -l 2>/dev/null|grep Disk|awk '{print $2$3}'|egrep -v "identifier|VolGroup"`
uptime=`cat /proc/uptime| awk -F. '{run_days=$1 / 86400;run_hour=($1 % 86400)/3600;run_minute=($1 % 3600)/60;run_second=$1 % 60;printf("%d天%d时%d分%d秒",run_days,run_hour,run_minute,run_second)}'`
mac=`/sbin/ifconfig eth0 2>/dev/null | sed -n '/HWaddr/ s/^.*HWaddr *//pg'`

ps aux |grep xenbus |grep -v grep > /dev/null
if [ $? -eq 0 ];then
VIRT="xen"
else
VIRT=""
fi

HARDINFO="$sysplan|$cpu|$cpu_num|$memtotal|$kernal|$disk|$uptime|$mac|$VIRT"
cd ..
/usr/bin/perl post_res.pl -c "$HARDINFO" HardWare 
