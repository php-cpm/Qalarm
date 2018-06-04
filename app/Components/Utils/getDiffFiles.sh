#! /usr/local/bin/bash

check_status()
{
    if [ $1 != 0 ]
    then
        echo $1$2
        exit 1
    fi
}

#   读取old_path
if [ $1 = '--old_path' ] && [ $# -ge 2 ]
then
    shift
    OLD_PATH=$1
    shift
else
    echo '1'
    exit 1
fi

#   读取new_path
if [ $1 = '--new_path' ] && [ $# -ge 2 ]
then
    shift
    NEW_PATH=$1
    shift
else
    echo '2'
    exit 1
fi

#   读取过滤开关
if [ $1 = '--is_filter' ] && [ $# -ge 2 ]
then
    shift
    IS_FILTER=$1
    shift
else
    echo '3'
    exit 1
fi

#	读出文件
files=""
if [ $1 = '--file' ] && [ $# -ge 2 ]
then
    shift
    while [ $# -ne 0 ]
    do
        files="${files}${1}"$'\n'
        shift
    done
else
    echo '4'
    exit 1
fi

cd $NEW_PATH
check_status $?  "cd $NEW_PATH fail"

if [ $IS_FILTER = 'true' ]
then
    REGEX='(.*\.jpg$)|(.*\.doc$)|(.*\.docx$)|(.*\.gif$)|(.*\.png$)|(.*\.tmp$)|(.*\.log$)|(.*\.svn.*)'
else
    REGEX='(.*\.tmp$)|(.*\.log$)|(.*\.svn.*)'
fi

IFS=$'\n'
for file in $files
do
    IFS=$'\n'
    /usr/bin/find $file -regextype posix-extended -type f -not -regex $REGEX 2> /dev/null | while read line
    do
        /usr/bin/diff -Bb $OLD_PATH/$line $NEW_PATH/$line  > /dev/null 2>&1
        if [ $? != 0 ]
        then
            echo $line
        fi
    done
done
exit 0
