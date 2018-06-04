#!/bin/bash
target=gaea_targets
mkdir -p ${target}
rm -rf ${target}/*
src_name="${JOB_NAME}-src.tar.gz"
#tar -zcvf ${src_name} * --exclude=${target} --exclude=${src_name} --exclude=.env >> /dev/null 2>&1
#git archive -o ${src_name} HEAD >> /dev/null 2>&1
git archive --format=tar HEAD |gzip > ${src_name}
mv ${src_name} ${target}/
