#!/bin/bash
workspace='/home/liuliwei/src/test_git/test'
old_package_url='http://10.10.42.226:18599/gaea/packages/test/build_test/build_history/5/build_test-src.tar.gz'
#dir_diff='/tmp/gaea/diff_package'
dir_old='/tmp/gaea/pre_package'
dir_new=${workspace}
mkdir -p ${dir_old} 
#mkdir -p ${dir_diff} 
#rm -rf *
wget -P ${dir_old} ${old_package_url} 
cd ${dir_old}
old_package_name=`basename ${old_package_url}`
tar -zxvf ${old_package_name}
echo '=== 解压成功 ==='
rm ${old_package_name}
cd ${workspace}

files=`ls -AF ${dir_new}`
#diff_files=`source ./getDiffFiles.sh --old_path ${dir_old} --new_path ${dir_new} --is_filter true --file ${files}`
diff_files=`source /home/liuliwei/src/gaea/app/Components/Jenkins/shell/getDiffFiles.sh --old_path ${dir_old} --new_path ${dir_new} --is_filter true --file ${files}`
#cd ${dir_new} 
#tar -zcvf diff_files.tar.gz ${diff_files}
#echo '================='
dir_diff=${workspace}/gaea_target/diff_files
mkdir -p ${dir_diff}
rm -rf ${dir_diff} && rm -rf .* 
for f in ${diff_files}; do
    echo ${f}

    f_dir=`dirname ${f}`
    #echo ${f_dir}
    if [ ! -d ${dir_diff}/${f_dir} ]; then
        mkdir -p ${dir_diff}/${f_dir}
    fi

    if [ ! -f ${dir_old}/${f} ]; then
        if [ -f ${dir_new}/${f} ]; then
            cp ${dir_diff}/${f}
            continue
        fi
    fi
    #`diff -L 'Old' -L 'New' -U 6 ${dir_old}/${f} ${dir_new}/${f} > ${dir_diff}/${f} 2>&1`
done
curl -d "gaea_build_id=56de4393d4d84&jenkins_job_id=456&diff_files=${diff_files}" http://172.16.10.30:12815/api/v1/ci/jenkinsdifffiles
