#!/bin/bash

dir_new='/tmp/gaea/new_package'
dir_old='/tmp/gaea/old_package'
dir_diff='/tmp/gaea/diff_package'

if [ ! -d "$dir_new" ]; then
    mkdir -p "$dir_new"
else
    rm -rf "$dir_new"
    mkdir -p "$dir_new"
fi

if [ ! -d "$dir_old" ]; then
    mkdir -p "$dir_old"
else
    rm -rf "$dir_old"
    mkdir -p "$dir_old"
fi

if [ ! -d "$dir_diff" ]; then
    mkdir -p "$dir_diff"
else
    rm -rf "$dir_diff"
    mkdir -p "$dir_diff"
fi

#解压本次最新源码包
tar -zxvf "$WORKSPACE/gaea_targets/$JOB_NAME-src.tar.gz" -C "$dir_new" > /dev/null 2>&1

#获取历史构建包
#old_build_package=''
old_package_url="$old_build_package"
if [ ! "$old_package_url" ]; then
    echo '=== 首次构建，历史包地址为空 ==='
else
    wget -P "$dir_old" "$old_package_url" > /dev/null 2>&1
    cd "$dir_old"
    old_package_name=`basename "$old_package_url"`
    tar -zxvf "$old_package_name" > /dev/null 2>&1
    rm -rf "$old_package_name"
    echo '=== 解压成功 ==='
fi

#创建保存差异的文件到 dir_diff目录
diff_files_name='tmp_need_patch_files'
touch "$dir_diff/$diff_files_name"

#切换到本次构建的目录
cd "$dir_new"
files=`ls -AF "$dir_new"`

IS_FILTER=true
#todo:坑 此处无法解析 反斜杠；需要 双反斜杠；
if [ $IS_FILTER = 'true' ]
then
    REGEX='(.*\\.jpg$)|(.*\\.doc$)|(.*\\.docx$)|(.*\\.gif$)|(.*\\.png$)|(.*\\.tmp$)|(.*\\.log$)|(.*\\.svn.*)|(.*\\.git.*)|(.*\\.tar.gz$)'
else
    REGEX='(.*\\.tmp$)|(.*\\.log$)|(.*\\.svn.*)'
fi

for file in $files
do
    /usr/bin/find $file -regextype posix-extended -type f -not -regex "$REGEX" 2> /dev/null | while read line
    do
        /usr/bin/diff -Bb "$dir_old/$line" "$dir_new/$line"  > /dev/null 2>&1
        if [ $? != 0 ]
        then
            diff_file_dir=`dirname "$dir_diff/$line"`
            if [ ! -d "$diff_file_dir" ]; then
                mkdir -p "$diff_file_dir"
            fi
            `diff -L 'Old' -L 'New' -U 6 "$dir_old/$line" "$dir_new/$line" -N > "$dir_diff/$line" 2>&1`
            echo "$line" >> "$dir_diff/$diff_files_name"
        fi
    done
done

#打包差异文件包
cd "$dir_diff"
diff_patch_name=diff_patch.tar.gz
if [ -f "$diff_patch_name"  ];then
    rm -rf "$diff_patch_name"
fi
echo '================差异文件 start=============='
#patch_files=`cat "$dir_diff/$diff_files_name"`
#echo "$patch_files"
tar -zcvf "$diff_patch_name" * --exclude=build_before_sh.sh --exclude=ci_deploy_after.sh --exclude=gaea_targets
echo '================差异文件 end  =============='
target=gaea_targets
mkdir -p "$WORKSPACE/$target"
echo '复制diff包'
#cp ${dir_diff}/diff_patch.tar.gz ${dir_new}/${target}/diff_patch.tar.gz
cp "$dir_diff/diff_patch.tar.gz" "$WORKSPACE/$target/diff_patch.tar.gz"

rm -rf "$dir_diff"
rm -rf "$dir_new"
rm -rf "$dir_old"
