#!/bin/bash
ps aux|grep "reload.sh" | grep -v grep | awk  '{print $2}' |xargs kill         

sh reload.sh &
exit 0
