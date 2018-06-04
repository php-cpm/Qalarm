#!/bin/bash

if [ ! -f /usr/bin/salt-minion ];then
	exit
fi

ps -ef|grep salt-minion |grep -v grep >/dev/null 2>&1
if [ $? -ne 0 ];then
	if [ -f /etc/salt/master ];then
		exit
	fi
	if [ -f /etc/salt/pki/minion/minion_master.pub ];then
		/bin/rm -f /etc/salt/pki/minion/minion_master.pub
	fi
	/usr/bin/salt-minion -d
	echo "salt-minion start "$?
fi
