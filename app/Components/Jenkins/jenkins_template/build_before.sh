#!/bin/bash

#build_sh_name='build_before_sh.sh'
#echo "${build_before_sh}" > ${build_sh_name} 
#sh ${build_sh_name}
#echo "${deploy_after_sh}" > ci_deploy_after.sh
#target=gaea_targets
#src_name="${JOB_NAME}.tar.gz"
##tar -zcvf ${src_name} * --exclude=${target} --exclude=${src_name} --exclude=.env >> /dev/null 2>&1
#tar -zcvf ${src_name} ${deploy_files} ${deploy_black_files} --exclude=${target} --exclude=${src_name} >> /dev/null 2>&1
#mkdir -p ${target}
#mv ${src_name} ${target}/

build_sh_name='build_before_sh.sh'
echo "$build_before_sh" > $build_sh_name
sh "$build_sh_name"
file_name_deploy_after='ci_deploy_after.sh'
echo "$deploy_after_sh" > "$file_name_deploy_after" 
echo '项目构建完成'
#echo "$deploy_after_sh" > ci_deploy_after.sh
target='gaea_targets'
if [ ! -d "$target" ]; then
    mkdir -p "$target"
fi
src_name="$JOB_NAME".tar.gz
if [ ! -d "$deploy_files" ]; then
    #tar -zcvf ${src_name} * ${deploy_black_files} --exclude=${target} --exclude=${src_name} >> /dev/null 2>&1
    tar -zcvf ${src_name} ${deploy_black_files} --exclude=${target} --exclude=${src_name} --exclude=.git `ls -A`  >> /dev/null 2>&1
    mv ${src_name} ${target}/
else
    #cd 'target' 
    #tar -zcvf ${src_name} * ${deploy_black_files} --exclude=${src_name} >> /dev/null 2>&1
    #tar -zcvf ${src_name} ${deploy_black_files} --exclude=${src_name} --exclude=.git `ls -A`>> /dev/null 2>&1
    tar -zcvf ${src_name} ${deploy_black_files} --exclude=${src_name} --exclude=.git ${deploy_files} ${file_name_deploy_after}>> /dev/null 2>&1
    mv "$src_name" "$WORKSPACE/$target/"
    cd "$WORKSPACE"
fi
echo '压缩程序包完成'
