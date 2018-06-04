#!/bin/bash


chmod -R 777 bootstrap/
chmod -R 777 storage/
chmod -R 777 app/

ALARM_LOG=/var/wd/wrs/logs/alarm/
if [ ! -d  $ALARM_LOG ];then
    mkdir -p $ALARM_LOG
    chmod -R 777 $ALARM_LOG
    chmod -R 777 $ALARM_LOG/*
fi

#php artisan route:cache

hostname=`hostname`
#count=`ps aux|grep "alarm:merge" | grep -v grep | awk  '{print $2}' | wc -l`
#if [ "$count" != "0" ]; then
#    ps aux|grep "alarm:merge" | grep -v grep | awk  '{print $2}' |xargs kill
#fi


if [ "$hostname" == "CDVM-213025131" ];then
    ps aux|grep "supervisord" | grep -v grep | awk  '{print $2}' |xargs kill >> /dev/null 2>&1
    ps aux|grep "alarm:check_redis" | grep -v grep | awk  '{print $2}' |xargs kill >> /dev/null 2>&1
    ps aux|grep "alarm:merge" | grep -v grep | awk  '{print $2}' |xargs kill >> /dev/null 2>&1
    ps aux|grep "alarm:kafka" | grep -v grep | awk  '{print $2}' |xargs kill >> /dev/null 2>&1
    ps aux|grep "queue:work" | grep -v grep | awk  '{print $2}' |xargs kill >> /dev/null 2>&1
    ps aux|grep "page:kafka" | grep -v grep | awk  '{print $2}' |xargs kill >> /dev/null 2>&1
    /usr/bin/python /usr/bin/supervisord -c /var/wd/wrs/webroot/qalarm/deploy/supervisord.conf
fi

#count=`ps aux|grep "alarm:kafka" | grep -v grep | awk  '{print $2}' | wc -l`
#if [ "$count" != "0" ]; then
#    ps aux|grep "alarm:kafka" | grep -v grep | awk  '{print $2}' |xargs kill
#fi
#
#if [ "$hostname" == "CDVM-213025131" ];then
#    nohup php artisan alarm:kafka >> storage/logs/alarm_kafka.log 2>&1 &
#fi
