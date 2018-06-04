#!/bin/bash

ps aux|grep "alarm:merge" | grep -v grep | awk  '{print $2}' |xargs kill >> /dev/null 2>&1
ps aux|grep "alarm:manual" | grep -v grep | awk  '{print $2}' |xargs kill >> /dev/null 2>&1
ps aux|grep "alarm:file" | grep -v grep | awk  '{print $2}' |xargs kill >> /dev/null 2>&1
nohup php /Users/willas/Src/Qalarm/artisan alarm:merge &  
nohup php /Users/willas/Src/Qalarm/artisan alarm:manual &  
nohup php /Users/willas/Src/Qalarm/artisan alarm:file & 
