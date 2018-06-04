#!/bin/bash

#直接应用于jeknins_job;需要反斜杠；
#如果通过base_dsl_job应用于jenskins_job；需要使用双反斜杠 

call_data="{"
call_data+=" \"build_number\":\"${BUILD_NUMBER}\""
call_data+=",\"build_id\":\"${BUILD_ID}\""
call_data+=",\"build_display_name\":\"${BUILD_DISPLAY_NAME}\""
call_data+=",\"job_name\":\"${JOB_NAME}\""
call_data+=",\"build_tag\":\"${BUILD_TAG}\""
call_data+=",\"executor_number\":\"${EXECUTOR_NUMBER}\""
call_data+=",\"node_name\":\"${NODE_NAME}\""
call_data+=",\"node_labels\":\"${NODE_LABELS}\""
call_data+=",\"workspace\":\"${WORKSPACE}\""
call_data+=",\"jenkins_home\":\"${JENKINS_HOME}\""
call_data+=",\"jenkins_url\":\"${JENKINS_URL}\""
call_data+=",\"build_url\":\"${BUILD_URL}\""
call_data+=",\"job_url\":\"${JOB_URL}\""
call_data+=",\"svn_revision\":\"${SVN_REVISION}\""
call_data+=",\"svn_url\":\"${SVN_URL}\""
call_data+=",\"git_revision\":\"${GIT_REVISION}\""
call_data+=",\"git_commit\":\"${GIT_COMMIT}\""
call_data+=",\"gaea_build_id\":\"${gaea_build_id}\""
call_data+="}"
curl -d "data=${call_data}" gaea_jenkins_callback
