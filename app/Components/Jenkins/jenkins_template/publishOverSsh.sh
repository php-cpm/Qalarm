#!/bin/bash

#--坑--;如果直接写在此写脚本；变量需要加双引号 "${变量名}"；
remote_path="gaea_remote_path/${JOB_NAME}"
mkdir -p $remote_path
cd "${remote_path}"
#build_history_path="${remote_path}/build_history/${BUILD_ID}"
build_history_path="${remote_path}/build_history/${gaea_build_id}"
mkdir -p "${build_history_path}"
diff_file='diff_patch.tar.gz'
md5sum last_build/gaeakjtargets/"$diff_file" >last_build/gaea_targets/diff_file_md5.md
cp -r last_build/gaea_targets/* "${build_history_path}"
