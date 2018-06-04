#!/bin/bash

SAVE_DAYS=14

LOG_LIST=(`find ../ -name "*.log"| xargs stat -t |awk '{print $1":"$13}'`)

NOW_TIME=`date +%s`
for line in ${LOG_LIST[@]}
do
	FILE_TIME=`echo $line|awk -F: '{print $2}'`
	LOG_FILE=`echo $line|awk -F: '{print $1}'`
	FILE_NAME=${LOG_FILE##*/}
	DIR_NAME=${LOG_FILE%/*}
	if [[ ! "$FILE_NAME" =~ "[0-9]{8}" ]];then
		LOG_NAME=${FILE_NAME%%.*}
		LOG_TAG=`date +%Y%m%d`
		NEW_FILE="$DIR_NAME/${LOG_NAME}_"$LOG_TAG".log"
		#echo $FILE_NAME " not_time_tag"
		if [ ! -f $NEW_FILE ];then
			/bin/mv $LOG_FILE $NEW_FILE
		fi
	fi
	if [ $(( NOW_TIME - FILE_TIME )) -gt $((86400 * SAVE_DAYS)) ];then
		CLEAN_CMD="/bin/rm -f $LOG_FILE"
		echo `date +%F" "%T` $CLEAN_CMD
		$CLEAN_CMD
		#date -d @$FILE_TIME +%F" "%T
	fi
done
