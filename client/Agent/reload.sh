#!/bin/bash

count=`ps aux|grep "stare.py" | grep -v grep | awk  '{print $2}' | wc -l`
if [ "$count" != "0" ]; then
    ps aux|grep "stare.py" | grep -v grep | awk  '{print $2}' |xargs kill
fi
while true; do
    /usr/bin/python2.6 stare.py >> stare.log 2>&1
    sleep 1
done
