<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

use DB;
use Log;
use App\Components\Utils\LogUtil;
use App\Components\GitLab\GitLabHook;
//use App\Components\Jenkins\JenkinsHook;
use App\Components\Jenkins\CiCdConstants;
use Carbon\Carbon;
use App\Components\Utils\Paginator;

use App\Models\Gaea\CiProject;
use App\Models\Gaea\CiProjectChange;
use App\Models\Gaea\CiBuildProject;
use App\Models\Gaea\CiBuildProjectLog;
use App\Models\Gaea\CiBuildSteps;
use App\Models\Gaea\CiJenkinsJobs;
use App\Models\Gaea\OpsScripts;
//use App\Models\Gaea\CiDeployProject;
//use App\Models\Gaea\CiDeployProjectLog;
//use App\Models\Gaea\CiDiffFiles;
use App\Models\Gaea\CiTestReport;
//use App\Models\Gaea\CiDeployOperate;
use App\Models\Gaea\CiProjectHost;
use App\Models\Gaea\OpsK8sHost;

use App\Models\Gaea\CiGitMember;
use App\Models\Gaea\CiProjectMember;
use App\Models\Gaea\CiStepStateLog;

use App\Components\Utils\Constants;
use App\Components\Utils\MethodUtil;
use App\Components\Utils\XmlArrayHelp;
use App\Components\Jenkins\JenkinsHelp;
use App\Components\Jenkins\JenkinsDslHelper;
use App\Components\JobDone\JobDone;
use App\Components\Utils\ErrorCodes;
use App\Components\Utils\CurlUtil;

use JenkinsKhan\Jenkins;
//use App\Jobs\CiDeployJob;
use App\Jobs\CiBuildDockerImageJob;

use App\Components\Jenkins\DiffModel;
use App\Components\Utils\FileOperation;

class WebhookController extends Controller
{
    public function gitLab(Request $request)
    {/*{{{*/

        //dd(11);
        //$data = '{"object_kind":"push","before":"007199a9f2d83614134635f643b7c596d79f9b1d","after":"3e94eac1e2ba5de35151e16c61c61cead8fd206b","ref":"refs\\/heads\\/master","checkout_sha":"3e94eac1e2ba5de35151e16c61c61cead8fd206b","message":null,"user_id":2,"user_name":"liuliwei","user_email":"784456305@qq.com","project_id":1,"repository":{"name":"test_demo","url":"git@172.16.10.196:root\\/test_demo.git","description":"\\u6d4b\\u8bd5\\u7684demo","homepage":"http:\\/\\/172.16.10.196:8360\\/root\\/test_demo","git_http_url":"http:\\/\\/172.16.10.196:8360\\/root\\/test_demo.git","git_ssh_url":"git@172.16.10.196:root\\/test_demo.git","visibility_level":0},"commits":[{"id":"3e94eac1e2ba5de35151e16c61c61cead8fd206b","message":"fff\\n","timestamp":"2015-12-26T18:17:42+08:00","url":"http:\\/\\/172.16.10.196:8360\\/root\\/test_demo\\/commit\\/3e94eac1e2ba5de35151e16c61c61cead8fd206b","author":{"name":"liuliwei","email":"784456305@qq.com"}},{"id":"c36d391ddf17c8266e058ab791f83fdd6f3fae26","message":"fff\\n","timestamp":"2015-12-26T18:13:11+08:00","url":"http:\\/\\/172.16.10.196:8360\\/root\\/test_demo\\/commit\\/c36d391ddf17c8266e058ab791f83fdd6f3fae26","author":{"name":"liuliwei","email":"784456305@qq.com"}},{"id":"007199a9f2d83614134635f643b7c596d79f9b1d","message":"fff\\n","timestamp":"2015-12-26T18:09:03+08:00","url":"http:\\/\\/172.16.10.196:8360\\/root\\/test_demo\\/commit\\/007199a9f2d83614134635f643b7c596d79f9b1d","author":{"name":"liuliwei","email":"784456305@qq.com"}}],"total_commits_count":3}';
        //$data = '{"object_kind":"push","before":"dae35ac0b84e62a901d0984b67624617da8d4ef9","after":"4f0aea4615587a8a944921d1e39d2e69b3b69b3c","ref":"refs/heads/master","checkout_sha":"4f0aea4615587a8a944921d1e39d2e69b3b69b3c","message":null,"user_id":2,"user_name":"liuliwei","user_email":"784456305@qq.com","project_id":2,"repository":{"name":"test","url":"git@172.16.10.196:liuliwei/test.git","description":"demo","homepage":"http://172.16.10.196:8360/liuliwei      /test","git_http_url":"http://172.16.10.196:8360/liuliwei/test.git","git_ssh_url":"git@172.16.10.196:liuliwei/test.git","visibility_level":20},"commits":[      {"id":"4f0aea4615587a8a944921d1e39d2e69b3b69b3c","message":"update readme 测试修改4\n","timestamp":"2015-12-28T19:00:50+08:00","url":"http://172.16.10.196      :8360/liuliwei/test/commit/4f0aea4615587a8a944921d1e39d2e69b3b69b3c","author":{"name":"liuliwei","email":"784456305@qq.com"}} ],"total_commits_count":1}';
        //$data = json_decode($data);
        //dd($data);
        
        $data = Input::All();
        LogUtil::info('receive git hook',[json_encode($data)], LogUtil::LOG_CI);

        $gitLabHook = new GitLabHook(json_encode($data));

        $projectId = $gitLabHook->getProjectId();
        $row = CiProject::where('project_id', '=', $projectId)->first();
        //dd($row);
        if ($row == null) {
            $ciProject = new CiProject();
            {
                $ciProject->project_id        = $gitLabHook->getProjectId();
                $ciProject->project_name      = $gitLabHook->getProjectName();
                $ciProject->project_branchs   = $gitLabHook->getRefBranch();
                $ciProject->project_addr      = $gitLabHook->getProjectGitSshUrl();
                $ciProject->gitlab_website    = $gitLabHook->getGitlabWebSite();
                $ciProject->project_desc      = $gitLabHook->getProjectDescription();
                $ciProject->project_homepage  = $gitLabHook->getProjectHomePage();
                $ciProject->visibility_level  = 0;
                $ciProject->project_user_id   = $gitLabHook->getUserId();
                $ciProject->project_user_name = $gitLabHook->getUserName();
                $ciProject->listener_branchs  = $gitLabHook->getRefBranch();
            }
            //默认监听此提交分支；如果需要忽略，在gaea内部忽略
            //$listenBranch = [$gitLabHook->getRefBranch() => '1'];
            //$ciProject->listener_branchs = json_encode($listenBranch);
            $ciProject->save();
            LogUtil::info('save project info',['this project is first call;'.$gitLabHook->getProjectName()], LogUtil::LOG_CI); //默认添加两个jenkins job
            $jenkinsSteps = CiBuildSteps::where("project_id", "=", $gitLabHook->getProjectId())->first();
            if ($jenkinsSteps == null ) {
                //没有构建 流程配置的；默认增加流程
                $this->saveDefaultJobStep($gitLabHook);
                $this->createDefaultJob($gitLabHook);
            }
            //todo::发送叮叮；通知需要在gaea 中做一些配置，才可以正式使用
        }

        //保存gitlab log 记录
        $this->saveGitLabPushLog($gitLabHook);

        $jenkinsSteps = CiBuildSteps::where("project_id", "=", $gitLabHook->getProjectId())->get();
        if ($jenkinsSteps == null) {
            //步骤还没配置
            LogUtil::info('check build step error',['project is not set build steps, don\'t begin build'], LogUtil::LOG_CI);
            return;
        }

        if (!$this->isListenBranch($gitLabHook->getProjectId(),$gitLabHook->getRefBranch())) {
            LogUtil::info('check listen branch',['project branch is not listen by gaea, , don\'t begin build'], LogUtil::LOG_CI);
            return;
        }

        //自动运行构建
        LogUtil::info('begin build',['begin build project  build'], LogUtil::LOG_CI);

        $commitUser = CiGitMember::where('project_id', '=', $gitLabHook->getProjectId())
            ->where('member_id', '=', $gitLabHook->getUserId())->first();
        $gaeaBuildId = '';
        if ( isset($commitUser) ) {
            $gaeaBuildId = $this->buildJobs($gitLabHook->getProjectId(),$gitLabHook->getRefBranch(), $commitUser->gaea_user_id, $commitUser->gaea_user_name);
        } else {
            $gaeaBuildId = $this->buildJobs($gitLabHook->getProjectId(),$gitLabHook->getRefBranch());
        }

        //保存log
        $ciLog = new CiStepStateLog ();
        {
            $ciLog->project_id    = $gitLabHook->getProjectId();
            $ciLog->project_name  = $gitLabHook->getProjectName();
            $ciLog->gaea_build_id = $gaeaBuildId;
            //$ciLog->state         = CiCdConstants::LOG_GET_GIT_WEBHOOK;
            $ciLog->task_step    = CiCdConstants::CICD_TASK_STEP_BUILD;
            $ciLog->task_step_status  = CiCdConstants::LOG_GET_GIT_WEBHOOK;
            $ciLog->task_step_status_desc = CiCdConstants::$logStepStateDesc[CiCdConstants::LOG_GET_GIT_WEBHOOK];

            $ciLog->user_id       = isset($commitUser) ? $commitUser->gaea_user_id : 0 ;
            $ciLog->user_name     = isset($commitUser) ? $commitUser->gaea_user_name : $gitLabHook->getUserName();
            $ciLog->started_time     = Carbon::now();
            $ciLog->finished_time    = Carbon::now();
            $ciLog->save();
        }
        //保存log
        $ciLog = new CiStepStateLog ();
        {
            $ciLog->project_id    = $gitLabHook->getProjectId();
            $ciLog->project_name  = $gitLabHook->getProjectName();
            $ciLog->gaea_build_id = $gaeaBuildId;
            //$ciLog->state         = CiCdConstants::LOG_CREATE_BUILD_AUTO;
            $ciLog->task_step    = CiCdConstants::CICD_TASK_STEP_BUILD;
            $ciLog->task_step_status  = CiCdConstants::LOG_CREATE_BUILD_AUTO;
            $ciLog->task_step_status_desc = CiCdConstants::$logStepStateDesc[CiCdConstants::LOG_CREATE_BUILD_AUTO];
            $ciLog->user_id       = isset($commitUser) ? $commitUser->gaea_user_id : 0 ;
            $ciLog->user_name     = isset($commitUser) ? $commitUser->gaea_user_name : $gitLabHook->getUserName();
            $ciLog->started_time     = Carbon::now();
            $ciLog->finished_time    = Carbon::now();
            $ciLog->save();
        }
        return 'success';
    }/*}}}*/

    private function getDiffFilesList($gaeaBuildId, $jenkinsJobId, $jenkinsJobName)
    {/*{{{*/
        $path = storage_path().'/diff_package/'.$gaeaBuildId;
        $targetName = 'diff_patch.tar.gz';
        if (!is_dir($path)){
            exec(sprintf('mkdir -p %s', $path));
        }

        //下载md5 文件
        $downDiffFileMd5Url = CiBuildProject::getDownDiffFilesMd5Url($gaeaBuildId);
        $cmd = "cd $path && curl -o diff_file_md5.md $downDiffFileMd5Url";
        exec($cmd, $res, $ret);
        $diffFileMd5Str = @file_get_contents($path.'/diff_file_md5.md');
        $diffFileMd5Str = explode(' ', $diffFileMd5Str)[0];
        LogUtil::info('down md5 file', [$cmd]);

        if (!file_exists($path.'/'.$targetName)) {
            //文件不存在；下载文件
            $downDiffFileUrl = CiBuildProject::getDownDiffFilesUrl($gaeaBuildId);
            $cmd = "cd $path && curl -o $targetName $downDiffFileUrl && tar -zxvf $targetName";
            exec($cmd, $res, $ret);
            LogUtil::info('down file', [$cmd]);
        } else {
            //文件存在，但是md5 不同；需要从新下载数据
            $md5Str = md5_file($path.'/'.'diff_patch.tar.gz');
            if ($diffFileMd5Str != $md5Str) {
                $downDiffFileUrl = CiBuildProject::getDownDiffFilesUrl($gaeaBuildId);
                $cmd = "cd $path && curl -o $targetName $downDiffFileUrl && tar -zxvf $targetName";
                //dd($cmd);
                exec($cmd, $res, $ret);
                LogUtil::info('down file', [$cmd]);
            }
        }

        //读取构建文件时比对，不同文件的列表
        $diffFileListStr = @file_get_contents($path.'/tmp_need_patch_files');
        $diffFiles = explode("\n", $diffFileListStr);

        //dd($diffFiles);
        //print(count($diffFiles));
        $maxLength = 100;
        if (count($diffFiles) > $maxLength) {
            $diffFiles = array_slice($diffFiles, 0, $maxLength); 
        }
        
        LogUtil::info('diff files hooks',['gaea_build_id' => $gaeaBuildId, 'jenkins_job_id' => $jenkinsJobId]);

        $buildInfo = CiBuildProject::where('gaea_build_id', '=', $gaeaBuildId)->first();
        $result =[];
        foreach ( $diffFiles as $item )
        {
            if (empty(trim($item))){
                continue;
            }
            $arr = ['data' => $item, 'project_id' => $buildInfo->project_id, 'gaea_build_id' => $gaeaBuildId];
            $result[] = $arr;
        }
        return $result;
    }/*}}}*/

    public function diffFiles (Request $request)
    {/*{{{*/
        $actions = MethodUtil::getActions();
        $this->validate($request, [
            'action'  => "required|in:$actions"
        ]);

        $action = $request->input('action');
        switch ($action) {
        case Constants::REQUEST_TYPE_GET:
            $this->validate($request, [
                //'project_id'     => "required",
                'gaea_build_id'  => "required",
                'diff_file_name' => "required"
            ]);
            $gaeaBuildId = $request->input('gaea_build_id');

            //$ciDiffFiles = CiDiffFiles::where('build_id', '=', $gaeaBuildId)->first();
            //$downDiffFileUrl = $this->getDownDiffFilesUrl($ciDiffFiles->project_name, $ciDiffFiles->jenkins_job_id);
        
            $path = storage_path().'/diff_package/'.$gaeaBuildId;
            //$targetName = 'patch.tar.gz';
            //if (!is_dir($path)){
                //exec(sprintf('mkdir -p %s', $path));
            //}
            //if (!file_exists($path.'/'.$targetName)) {
                //$cmd = "cd $path && curl -o $targetName $downDiffFileUrl && tar -zxvf $targetName";
                //exec($cmd, $res, $ret);
            //}

            $diffContent = @file_get_contents($path.'/'.$request->input('diff_file_name'));

            $diffResult = explode("\n", $diffContent);
            //dd($diffResult);
            //去掉第一 第二行 ;重置数组索引
            unset($diffResult[0], $diffResult[1]);
            $diffResult = array_values($diffResult);
            //dd($diffResult);
            $diff_model = new DiffModel();
            $data = $diff_model->parseDiff($diffResult);
            return response()->clientSuccess($data);
            break;
        case Constants::REQUEST_TYPE_LIST:
            $projectBuildInfo = CiBuildProject::where('gaea_build_id', '=', $request->input('gaea_build_id'))->first();
            $buildLog = CiBuildProjectLog::where('gaea_build_id', '=', $request->input('gaea_build_id'))
                ->where('jenkins_job_name', '=', 'build_'.$projectBuildInfo->project_name)->first();
            //$projectBuildInfo = CiBuildProject::where('gaea_build_id', '=', $request->input('gaea_build_id'))->first();
            //$projectBuildInfo = CiBuildProject::where('gaea_build_id', '=', $request->input('gaea_build_id'))-fr
                //->where('jenkins_job_name', '=', 'build'.$project_name)->first();
            $data = $this->getDiffFilesList($projectBuildInfo->gaea_build_id, $buildLog->jenkins_build_id, $buildLog->jenkins_job_name);
            return response()->clientSuccess($data);
        };
    }/*}}}*/

    public function test(Request $request)
    {/*{{{*/

        $gaeaBuildId = '573c3251963bf';
        $buildProject = CiBuildProject::where('gaea_build_id', '=', $gaeaBuildId)->first();

        //保存log
        $ciLog = new CiStepStateLog ();
        {
            $ciLog->project_id    = $buildProject->project_id;
            $ciLog->project_name  = $buildProject->project_name;
            $ciLog->gaea_build_id = $buildProject->gaea_build_id;
            //$ciLog->state         = CiCdConstants::LOG_BUILD_FAIL;
            $ciLog->task_step    = CiCdConstants::CICD_TASK_STEP_BUILD;
            $ciLog->task_step_status  = CiCdConstants::LOG_BUILD_FAIL;
            $ciLog->task_step_status_desc = CiCdConstants::$logStepStateDesc[CiCdConstants::LOG_BUILD_FAIL];
            //$ciLog->state         = 'build fail!!';
            //$ciLog->log_content   = '构建失败';
            $ciLog->user_id       = $buildProject->user_id;
            $ciLog->user_name     = $buildProject->user_name;
            $ciLog->started_time     = $buildProject->createtime;
            $ciLog->finished_time    = Carbon::now();
            $ciLog->save();
        }

    }/*}}}*/

    public function jenkinsBuildFinishHook(Request $request)
    {/*{{{*/
        $url = $request->input('url'); 
        //$url="http://172.16.10.10:8080/job/build_test/162/";
        LogUtil::info('jenkins job finish hook',["data is $url"], LogUtil::LOG_CI);
        $arr = explode("/",$url); 
        $jenkinsBuildId = $arr[count($arr)-2]; 
        $jenkinsJobName = $arr[count($arr)-3];

        $buildInfo = JenkinsHelp::getJenkins()->getBuild ($jenkinsJobName, $jenkinsBuildId);
        $buildLog  = JenkinsHelp::getJenkins()->getConsoleTextBuild ($jenkinsJobName, $jenkinsBuildId);
        
        //判断时jenkins 中修改其它job 的基础job；然后记录log
        if($jenkinsJobName == CiCdConstants::BASE_JENKINS_DSL_JOB) {

            $baseJobLog = CiBuildProjectLog::where('jenkins_build_id', '=', $jenkinsBuildId)
                ->where('jenkins_job_name', $jenkinsJobName)
                ->first();

            if ($baseJobLog == null) {
                LogUtil::info('error jenkins run hook  not find data',["jenkinsBuildId=$jenkinsBuildId, jenkins_job_name=$jenkinsJobName"], LogUtil::LOG_CI);
                return 'fail';
            } else {
                $baseJobLog->status           = $buildInfo->getResult();
                $baseJobLog->finished         = Carbon::now();
                $baseJobLog->log              = $buildLog;
                $baseJobLog->jenkins_build_id = $jenkinsBuildId;
                $baseJobLog->save();
                return 'success';
            }
        }

        $buildRow = CiBuildProjectLog::where('jenkins_build_id', '=', $jenkinsBuildId)
            ->where('jenkins_job_name', '=', $jenkinsJobName)->first();
        $buildProject = CiBuildProject::where('gaea_build_id', '=', $buildRow->gaea_build_id)->first();

        if ($buildRow == null || $buildProject == null) {
            LogUtil::info('error jenkins finish hook not find data',["data is $url"], LogUtil::LOG_CI);
            return 'fail';
        }

        //保存job log
        $buildRow->status           = $buildInfo->getResult();
        $buildRow->finished         = Carbon::now();
        $buildRow->log              = $buildLog;
        $buildRow->jenkins_job_name = $jenkinsJobName;
        $buildRow->save();

        //根据当前jenkins job状态判断后续执行计划
        //if ($buildInfo->getResult() == 'FAILURE' || $buildInfo->getResult() == 'UNSTABLE') {
        if ($buildInfo->getResult() == CiCdConstants::JENKINS_JOB_STATUS_FAILURE
            || $buildInfo->getResult() == CiCdConstants::JENKINS_JOB_STATUS_UNSTABLE) {
            //设置gaea本次构建项目失败
            $buildProject->status              = CiCdConstants::CI_BUILD_STATUS_FAILURE;
            $buildProject->finishtime          = Carbon::now(); 
            $buildProject->save();
            LogUtil::info('save jenkins finish hook data success',["data is $url"], LogUtil::LOG_CI);

            //保存log
            $ciLog = new CiStepStateLog ();
            {
                $ciLog->project_id    = $buildProject->project_id;
                $ciLog->project_name  = $buildProject->project_name;
                $ciLog->gaea_build_id = $buildProject->gaea_build_id;
                //$ciLog->state         = CiCdConstants::LOG_BUILD_FAIL;
                $ciLog->task_step    = CiCdConstants::CICD_TASK_STEP_BUILD;
                $ciLog->task_step_status  = CiCdConstants::LOG_BUILD_FAIL;
                $ciLog->task_step_status_desc = CiCdConstants::$logStepStateDesc[CiCdConstants::LOG_BUILD_FAIL];
                //$ciLog->state         = 'build fail!!';
                //$ciLog->log_content   = '构建失败';
                $ciLog->user_id       = $buildProject->user_id;
                $ciLog->user_name     = $buildProject->user_name;
                $ciLog->started_time     = $buildProject->createtime;
                $ciLog->finished_time    = Carbon::now();
                $ciLog->save();
            }

            //发送通知消息
            $this->buildFinishSendMsg ($buildProject->project_id, $buildProject->project_name, $buildProject->gaea_build_id, $false);

            return 'success';
        }

        //优化:去掉 build_result_file
        /*//查找gaea 判断此 jenkins job 是否为产出 程序包文件 */
        /*//$isFileJob = CiJenkinsJobs::where('jenkins_job_type', '=', '1')*/
        /*//->where('jenkins_name', '=', $jenkinsJobName)->get();*/
        /*$isFileJob = CiBuildSteps::where('jenkins_job_type', '=', '2')*/
            /*->where('jenkins_job_name', '=', $jenkinsJobName)*/
            /*->where('project_name', '=', $buildRow->project_name)*/
            /*->first();*/

        /*//if($buildInfo->getResult() == 'SUCCESS' && $isFileJob != null) {*/
        /*if($buildInfo->getResult() == CiCdConstants::JENKINS_JOB_STATUS_SUCCESS && $isFileJob != null) {*/
            /*$buildProject = CiBuildProject::where('gaea_build_id', '=', $buildRow->gaea_build_id)->first();*/
            /*if ($buildProject != null) {*/
                /*//$buildProject->build_result_file   = "http://10.10.42.226:18599/fisher/test/$jenkinsJobName/build_history/$jenkinsBuildId/$jenkinsJobName.tar.gz";*/
                /*$envName = MethodUtil::getLaravelEnvName();*/
                /*$buildProject->build_result_file   = env('CI_GAEA_DWONFILE_ADDR')."/$envName/$jenkinsJobName/build_history/$jenkinsBuildId/$jenkinsJobName.tar.gz";*/
                /*$buildProject->save();*/
            /*}*/
        /*}*/

        //查找该项目此次构建所有job状态；如果都结束设置成功
        //todo:可以优化此处
        $jobSteps = CiBuildProjectLog::where('gaea_build_id', '=', $buildRow->gaea_build_id)->get();
        $isFinished = true;
        foreach ($jobSteps as $item) {
            //if ($item->status == 'WAITING' || $item->status == 'RUNNING') {
            if ($item->status == CiCdConstants::JENKINS_JOB_STATUS_WAITING
                || $item->status == CiCdConstants::JENKINS_JOB_STATUS_RUNNING) {
                $isFinished = false;
                break;
            }
        }

        if ($isFinished) {
            $isSuccess = true;
            foreach ($jobSteps as $item) {
                if ($item->status != CiCdConstants::JENKINS_JOB_STATUS_SUCCESS) {
                    $isSuccess = false;
                    break;
                }
            }
            if ($isSuccess) {
                $buildProject = CiBuildProject::where('gaea_build_id', '=', $buildRow->gaea_build_id)->first();
                if ($buildProject != null) {
                    $buildProject->status     = CiCdConstants::CI_BUILD_STATUS_SUCCESS;
                    $buildProject->finishtime = Carbon::now(); 
                    $buildProject->save();

                    //保存log
                    $ciLog = new CiStepStateLog ();
                    {
                        $ciLog->project_id    = $buildProject->project_id;
                        $ciLog->project_name  = $buildProject->project_name;
                        $ciLog->gaea_build_id = $buildProject->gaea_build_id;
                        //$ciLog->state         = CiCdConstants::LOG_BUILD_SUCCESS;
                        $ciLog->task_step    = CiCdConstants::CICD_TASK_STEP_BUILD;
                        $ciLog->task_step_status  = CiCdConstants::LOG_BUILD_SUCCESS;
                        $ciLog->task_step_status_desc = CiCdConstants::$logStepStateDesc[CiCdConstants::LOG_BUILD_SUCCESS];
                        //$ciLog->state         = 'build success';
                        //$ciLog->log_content   = '构建成功';
                        $ciLog->user_id       = $buildProject->user_id;
                        $ciLog->user_name     = $buildProject->user_name;
                        $ciLog->started_time     = $buildProject->createtime;
                        $ciLog->finished_time    = Carbon::now();
                        $ciLog->save();
                    }

                    // 发送通知消息
                    $this->buildFinishSendMsg ($buildProject->project_id, $buildProject->project_name, $buildProject->gaea_build_id, true);

                    // 调用构建docker镜像的job
                    $dockerImageName = null;
                    $hosts = CiProjectHost::fetchHostListByProjectId($buildProject->project_id);
                    foreach  ($hosts['default'] as $key=>$items) {  
                        foreach ($items as $hostInfo) {         
                            $hostType    = $hostInfo['host_type'];
                            $hostName    = $hostInfo['server_name'];

                            if ($hostType == CiProjectHost::HOST_TYPE_DOCKER) {    
                                $k8sHost = OpsK8sHost::where('ip', $hostName)->first();
                                $dockerImageName = $k8sHost->image_name;
                            }
                        }
                    }

                    // 拥有docker的机器列表才需要构建docker镜像
                    if (!empty($dockerImageName)) {
                        $project     = CiProject::where('project_id', $buildProject->project_id)->first();
                        $jobData = MethodUtil::assemblyJobData([
                            'image_name' =>$dockerImageName,
                            'version'    => $buildRow->gaea_build_id, 
                            'params'     => [
                                'ssh_user'         => empty($project->ssh_user) ? 'ttyc' : $project->ssh_user,
                                //'deploy_dir'       => empty($project->deploy_dir),
                                'deploy_dir'       => $project->deploy_dir,
                                'project_package'  => $project->project_name,
                                'package_url'      => CiBuildProject::getDownDeployFilesUrl($buildRow->gaea_build_id),
                            ]
                        ]);

                        $job = new CiBuildDockerImageJob($jobData);
                        $this->dispatch($job); 
                    }

                }
            }
        }
        //todo:暂时不调用其它job
    }/*}}}*/

    public function jenkinsBuildStartHook (Request $request)
    {/*{{{*/
        //$data = '{ "build_number":"12","build_id":"12","build_display_name":"#12","job_name":"build_test_master","build_tag":"jenkins-build_test_master-12","executor_number":"19","node_name":"master","node_labels":"master","workspace":"/root/.jenkins/workspace/build_test_master","jenkins_home":"/root/.jenkins","jenkins_url":"http://10.10.42.226:2346/","build_url":"http://10.10.42.226:2346/job/build_test_master/12/","job_url":"http://10.10.42.226:2346/job/build_test_master/","svn_revision":"","svn_url":"","git_revision":"","git_commit":"6978339e06526274ec790415ae32737598409de1","gaea_build_id":"56e95de71d27d","ext":"" }';
        
        $data = $request->input("data");
        LogUtil::info('jenkins job begin runing',["callback data is $data"], LogUtil::LOG_CI);

        $jenkinsHookData = json_decode($data);
        $jenkinsBuildId = $jenkinsHookData->build_id;
        $jenkinsJobName = $jenkinsHookData->job_name;
        $gaeaBuildId    = $jenkinsHookData->gaea_build_id;

        $buildInfo = JenkinsHelp::getJenkins()->getBuild ($jenkinsJobName, $jenkinsBuildId);
        $buildLog  = JenkinsHelp::getJenkins()->getConsoleTextBuild ($jenkinsJobName, $jenkinsBuildId);

        //判断时jenkins 中修改其它job 的基础job；然后记录log
        if($jenkinsJobName == CiCdConstants::BASE_JENKINS_DSL_JOB) {

            $baseJobLog = CiBuildProjectLog::where('gaea_build_id', '=', $gaeaBuildId)
                ->where('jenkins_job_name', $jenkinsJobName)
                ->first();

            if ($baseJobLog == null) {
                LogUtil::info('error jenkins run hook  not find data',["gaeabuild=$gaeaBuildId, jenkins_job_name=$jenkinsJobName"], LogUtil::LOG_CI);
                return 'fail';
            } else {
                $baseJobLog->status           = $buildInfo->getResult();
                $baseJobLog->started          = Carbon::now();
                $baseJobLog->log              = $buildLog;
                $baseJobLog->jenkins_build_id = $jenkinsBuildId;
                $baseJobLog->save();
                return 'success';
            }
        }

        $buildRow = CiBuildProjectLog::where('gaea_build_id', '=', $gaeaBuildId)
            ->where('jenkins_job_name', $jenkinsJobName)
            ->first();
        $buildProject = CiBuildProject::where('gaea_build_id', '=', $buildRow->gaea_build_id)->first();

        if ($buildRow == null || $buildProject == null) {
            LogUtil::info('error jenkins run hook  not find data',["gaeabuild=$gaeaBuildId, jenkins_job_name=$jenkinsJobName"], LogUtil::LOG_CI);
            return 'fail';
        }

        $buildRow->status           = $buildInfo->getResult();
        $buildRow->started          = Carbon::now();
        $buildRow->log              = $buildLog;
        $buildRow->jenkins_build_id = $jenkinsBuildId;
        $buildRow->save();

        $buildProject->status = CiCdConstants::CI_BUILD_STATUS_RUNNING;
        $buildProject->save();

    }/*}}}*/

    //public function gitLabProject (Request $request)
    //{[>{{{<]
        //$actions = MethodUtil::getActions();
        //$this->validate($request, [
            //'action'  => "required|in:$actions"
            //]);

        //$action = $request->input('action');
        //switch ($action) {
        //case Constants::REQUEST_TYPE_UPDATE:
            //$this->validate($request, [
                //'id'               => "required",
                //'ssh_user'         => "required",
                //'language'         => "required",
                //'language_version' => "required",
                //'deploy_dir'       => "required",
                ////'project_branch' => "required"
                //'listener_branchs' => "required"
                //]);

            ////$deployFilesArray = explode('\n', $request->input("deploy_files"));
            ////$deployBlackFilesArray = explode('\n', $request->input("deploy_black_files"));

            //$ciGitLab = CiProject::where('id','=', $request->input('id'))->first();
            ////$ciGitLab->project_job_name = $projectJobName;
            ////$ciGitLab->project_addr     = $projectGitUrl;
            //$ciGitLab->build_before_sh    = $request->input('build_before_sh');
            //$ciGitLab->deploy_after_sh    = $request->input('deploy_after_sh');
            //$ciGitLab->ssh_user           = $request->input('ssh_user');
            //$ciGitLab->language           = $request->input('language');
            //$ciGitLab->language_version   = $request->input('language_version');
            //$ciGitLab->checkcode_dir      = $request->input('checkcode_dir');
            //$ciGitLab->deploy_dir         = $request->input('deploy_dir');
            ////$ciGitLab->project_branch   = $request->input('project_branch');
            //$ciGitLab->listener_branchs   = $request->input('listener_branchs');
            //$ciGitLab->deploy_files       = str_replace("\n", '|', $request->input('deploy_files'));
            //$ciGitLab->deploy_black_files = str_replace("\n", '|', $request->input('deploy_black_files'));
            ////$ciGitLab->deploy_files       = json_encode($deployFilesArray); 
            ////$ciGitLab->deploy_black_files = json_encode($deployBlackFilesArray); 
            //$ciGitLab->save();

            //$projectName = $ciGitLab->project_name;
            //$repo  = $ciGitLab->project_addr; 
            //$branch = CiCdConstants::JENKINS_PULL_BRANCHS;
            ////$branch = 'master';
            ////$buildBeforeCmd = $ciGitLab->build_before_sh;

            ////jenkins 不需要分支信息；构建时自动切换分支
            //////修改jenkins job 数据
            ////$result = app('cijob')->createAndUpdateJobs($projectName, $repo, $branch, $buildBeforeCmd);
            //$result = app('cijob')->createAndUpdateJobs($projectName, $repo, $branch);
            //logutil::info('修改cijob 系统数据', [$result]);
            //if (!$result) {
                //logutil::error('-1', ['update build job fail']);
            //}

            //$result2 = app('sonar')->createAndUpdateJobs($projectName, $repo, $branch);
            //logutil::info('修改sonar 系统数据', [$result2]);
            //if (!$result2) {
                //logutil::error('-1', ['update sonar job fail']);
            //}

            //// 删除构建步骤老数据；增加构建步骤新数据
            ////$jobs = CiJenkinsJobs::where([])->get();
            //CiBuildSteps::where('project_id', '=', $ciGitLab->project_id)->delete();
            //$buildSteps  = $request->input('build_steps');

            //$jobsList = JenkinsDslHelper::$gaeaSteps;
            ////jobs 前端传递 jobs Id 到后端；后端根据id 查询数据
            //foreach ($buildSteps as $value) {
                //$jobItem = $jobsList[$value];

                //$jenkinsJobName = $jobItem['name'].'_'.$ciGitLab->project_name;

                //$jenkinsStepsItem = new CiBuildSteps();
                //$jenkinsStepsItem->project_id       = $ciGitLab->project_id;
                //$jenkinsStepsItem->project_name     = $ciGitLab->project_name;
                //$jenkinsStepsItem->jenkins_job_id   = $jenkinsJobName;
                //$jenkinsStepsItem->jenkins_job_name = $jenkinsJobName;
                //$jenkinsStepsItem->jenkins_job_type = $jobItem['job_type'];
                //$jenkinsStepsItem->weight           = $jobItem['weight'];
                //$jenkinsStepsItem->job_name_pre     = $jobItem['name'];
                //$jenkinsStepsItem->dsl_config       = "dsl config 配置文件";
                //$jenkinsStepsItem->save();
            //}
            //return response()->clientSuccess([]);
            //break;
        //case Constants::REQUEST_TYPE_LIST:
            ////$filterParams = Input::All();
            ////根据当前用户；列出信息
            //$isCiSuperUser = CiProjectMember::isCiSuperUser(Constants::getAdminAccount());
            //$data = CiProject::getProjectListByAdminAccount(Constants::getAdminAccount(), $isCiSuperUser);
            //return response()->clientSuccess(['data' => $data ]);
            //break;
        //case Constants::REQUEST_TYPE_GET:
            //$this->validate($request, [ 'project_id' => "required" ]);
            //$data = CiProject::where('project_id', '=', $request->Input('project_id'))->first();
            //return response()->clientSuccess($data);
            //break;
        //};
    //}[>}}}<]

    public function buildProject (Request $request)
    {/*{{{*/
        $actions = MethodUtil::getActions();
        $this->validate($request, [
            'action'  => "required|in:$actions"
        ]);

        $action = $request->input('action');
        switch ($action) {
        case Constants::REQUEST_TYPE_UPDATE:
            break;
        case Constants::REQUEST_TYPE_LIST:
            $projectId = $request->input('project_id', '');
            $status    = $request->input('build_status') == 'ALL' ? '' : $request->input('build_status', '');
            $isCiSuperUser = CiProjectMember::isCiSuperUser(Constants::getAdminAccount());
            $query = CiBuildProject::getCiBuildListByAdminAccount(Constants::getAdminAccount(), $projectId, $status, $isCiSuperUser);
            $paginator = new Paginator($request);
            $data = $paginator->runQuery($query);
            return $this->responseList($paginator, $data);
        };
    }/*}}}*/

    //获取构建log 数据
    public function buildProjectLog (Request $request)
    {/*{{{*/
        $actions = MethodUtil::getActions();
        $this->validate($request, [
            'action'  => "required|in:$actions"
        ]);

        $action = $request->input('action');
        switch ($action) {
        case Constants::REQUEST_TYPE_GET:
            $this->validate($request, [
                'id'  => "required"
            ]);
            $data = CiBuildProjectLog::where('id' , '=', $request->Input('id'))->first();
            if (!isset($data)) {
                return response()->clientError(-1, 'not find data');
            }
            return response()->clientSuccess($data);
        };
    }/*}}}*/

    public function gaeaJenkinsJob (Request $request)
    {/*{{{*/
        $actions = MethodUtil::getActions();
        $this->validate($request, [
            'action'  => "required|in:$actions"
        ]);

        $action = $request->input('action');
        switch ($action) {
        case Constants::REQUEST_TYPE_UPDATE:
            break;
        case Constants::REQUEST_TYPE_LIST:
            $data = CiJenkinsJobs::where([])
                ->orderBy('id', 'desc')->get();



            $jobs = array();
            //if (empty($request->input('type'))) {
            //$types[] = array('id'=>0, 'name'=>'全部');
            //}
            //if ($request->input('project_id')) {
            //$projectInfo = CiProject::where('project_id', '=', $request->input('project_id'))->first();
            //foreach ($data as $item) { 
            //$jobs[] = [ 'id'=> $item->job_name_pre.'_'.$projectInfo->project_name.'_'.$projectInfo->project_branch, 'name'=>$item->sname ] ;
            //}
            //} else {

            //foreach ($data as $item) { 
            //$jobs[] = [ 'id'=> $item->job_name_pre.'_'.$projectInfo->project_name.'_'.$projectInfo->project_branch, 'name'=>$item->sname ] ;
            //}
            ////foreach ($data as $item) {
            //////$jobs[] = [ 'id'=> $item->jenkins_name, 'name'=>$item->sname ] ;
            ////$jobs[] = [ 'id'=> $item->job_name_pre, 'name'=>$item->sname ] ;
            //////$types[] = array('id'=>$type, 'name'=>$info['mname'].'-'.$info['sname']);
            ////}
            //}
            foreach ($data as $item) {
                //$jobs[] = [ 'id'=> $item->jenkins_name, 'name'=>$item->sname ] ;
                $jobs[] = [ 'id'=> $item->job_name_pre, 'name'=>$item->sname ] ;
                //$types[] = array('id'=>$type, 'name'=>$info['mname'].'-'.$info['sname']);
            }

            return response()->clientSuccess($jobs);
            break;
        };
    }/*}}}*/

    public function buildSteps (Request $request)
    {/*{{{*/
        $actions = MethodUtil::getActions();
        $this->validate($request, [
            'action'  => "required|in:$actions"
        ]);

        $action = $request->input('action');
        switch ($action) {
        case Constants::REQUEST_TYPE_UPDATE:
            break;
        case Constants::REQUEST_TYPE_GET:
            $this->validate($request, [
                'project_id'  => "required"
            ]);
            //根据项目id查询数据
            $data = CiBuildSteps::where('project_id', '=', $request->input('project_id'))->get();
            return response()->clientSuccess($data);
            break;
        case Constants::REQUEST_TYPE_LIST:
            //$data = CiJenkinsJobs::where([])
            //->orderBy('id', 'desc')->get();

            //$jobs = array();
            ////if (empty($request->input('type'))) {
            ////$types[] = array('id'=>0, 'name'=>'全部');
            ////}
            ////dd($data);
            //foreach ($data as $item) {
            //$jobs[] = [ 'id'=> $item->jenkins_name, 'name'=>$item->sname.'-'.$item->jenkins_job_type ] ;
            ////$types[] = array('id'=>$type, 'name'=>$info['mname'].'-'.$info['sname']);
            //}
            ////dd($jobs);
            //return response()->clientSuccess($jobs);
            ////$ret['data'] = $data;
            ////return response()->clientSuccess($ret);
            break;
        };
    }/*}}}*/

    public function createBuild(Request $request)
    {/*{{{*/
        $this->validate($request, [
            'project_id' => "required",
            'branch'     => "required"
        ]);
        $projectId = $request->input('project_id');
        $branch    = $request->input('branch');
        $userId    = Constants::getAdminId();
        $userName  = Constants::getAdminName();

        $projectInfo = CiProject::where('project_id', '=', $projectId)->first();
        if (!isset($projectInfo)) {
            LogUtil::info('build jobs method',["is not find data project info ; $projectId"], LogUtil::LOG_CI);
            return response()->clientError(-1, 'not find data');
        }

        $gaeaBuildId = $this->buildJobs($projectId, $branch, $userId, $userName);
        $ciLog = new CiStepStateLog ();
        {
            $ciLog->project_id    = $projectInfo->project_id;
            $ciLog->project_name  = $projectInfo->project_name;
            $ciLog->gaea_build_id = $gaeaBuildId;
            //$ciLog->state         = CiCdConstants::LOG_CREATE_BUILD_HANDLE;
            $ciLog->task_step    = CiCdConstants::CICD_TASK_STEP_BUILD;
            $ciLog->task_step_status  = CiCdConstants::LOG_CREATE_BUILD_HANDLE;
            $ciLog->task_step_status_desc = CiCdConstants::$logStepStateDesc[CiCdConstants::LOG_CREATE_BUILD_HANDLE];
            $ciLog->user_id       = $userId;
            $ciLog->user_name     = $userName;
            $ciLog->started_time     = Carbon::now();
            $ciLog->finished_time    = Carbon::now();
            $ciLog->save();
        }

        return response()->clientSuccess([]);
    }/*}}}*/

    private function buildJobs($projectId = 0,$branch='master', $buildUserId=0, $buildUserName='')
    {/*{{{*/
        //$projectId = $project_id;
        $projectInfo = CiProject::where('project_id', '=', $projectId)->first();
        if ($projectInfo == null) {
            LogUtil::info('build jobs method',["is not find data project info ; $projectId"], LogUtil::LOG_CI);
            return response()->clientError(-1, 'not find data');
        }
        //$projectChange = CiProjectChange::where('project_id', '=', $projectInfo->project_id)->first();
        //取出最新 push来的 sha id
        $projectChange = CiProjectChange::where('project_id', '=', $projectInfo->project_id)
            ->where('branch', '=', $branch)->orderby('id','desc')
            ->first();
        $checkOutSha = $projectChange->checkout_sha;
        $gaeaBuildId = uniqid();
        $gaeaBuildNumber = CiBuildProject::where('project_id', '   = ', $projectInfo->project_id)->max('gaea_build_number') + 1;
        $ciBuildProject = new CiBuildProject();
        $ciBuildProject->gaea_build_id     = $gaeaBuildId;
        $ciBuildProject->gaea_build_number = $gaeaBuildNumber;
        $ciBuildProject->project_id        = $projectInfo->project_id;
        $ciBuildProject->project_name      = $projectInfo->project_name;
        $ciBuildProject->update_id         = $checkOutSha;
        $ciBuildProject->status            = CiCdConstants::CI_BUILD_STATUS_WAITING;
        $ciBuildProject->createtime        = Carbon::now();
        $ciBuildProject->branch            = $branch;
        $ciBuildProject->user_id           = $buildUserId;
        $ciBuildProject->user_name         = $buildUserName;
        $ciBuildProject->save();

        //插入数据等待回调;根据项目Id ；查出当前项目的构建流程；插入构架log表
        $projectBuildSteps = CiBuildSteps::where("project_id", "=", $projectInfo->project_id)->get();

        foreach ($projectBuildSteps as $subItems) {
            //插入数据等待回调
            $ciBuildLog = new CiBuildProjectLog();
            $ciBuildLog->project_id       = $projectInfo->project_id;
            $ciBuildLog->project_name     = $projectInfo->project_name;
            $ciBuildLog->gaea_build_id    = $gaeaBuildId;
            $ciBuildLog->jenkins_job_name = $subItems['jenkins_job_name'];
            $ciBuildLog->weight           = $subItems['weight'];
            $ciBuildLog->status           = CiCdConstants::JENKINS_JOB_STATUS_WAITING;
            $ciBuildLog->branch            = $branch;
            $ciBuildLog->created          = Carbon::now();
            $ciBuildLog->save();
        }
        $deployFiles = '';
        $deployFilesArr = explode('|', $projectInfo->deploy_files);
        foreach($deployFilesArr as $item) {
            if (!empty($item)) {
                $deployFiles.= sprintf(' %s ',$item);
            }
        }

        $deployBlackFiles = '';
        $deployBlackFilesArr = explode('|', $projectInfo->deploy_black_files);
        foreach($deployBlackFilesArr as $item) {
            if (!empty($item)) {
                $deployBlackFiles.= sprintf(' --exclude=%s ',$item);
            }
        }

        $oldBuildPackage = CiBuildProject::getOldBuildSrcUrl($gaeaBuildId);

        foreach ($projectBuildSteps as $item) {
            if ($item->jenkins_job_name == 'build_'.$item->project_name) {
                $postJenkinsData = [
                    CiCdConstants::BUILD_PARAMS_DEPLOY_AFTER_SH    => $projectInfo->deploy_after_sh,
                    CiCdConstants::BUILD_PARAMS_BUILD_BEFORE_SH    => $projectInfo->build_before_sh,
                    CiCdConstants::BUILD_PARAMS_PROJECT_BRANCH     => $branch,
                    CiCdConstants::BUILD_PARAMS_SSH_USER           => $projectInfo->ssh_user,
                    CiCdConstants::BUILD_PARAMS_DEPLOY_FILES       => trim($deployFiles), //  去空格
                    CiCdConstants::BUILD_PARAMS_DEPLOY_BLACK_FILES => trim($deployBlackFiles), //  去空格
                    CiCdConstants::BUILD_PARAMS_OLD_BUILD_PACKAGE  => $oldBuildPackage,
                    CiCdConstants::BUILD_PARAMS_GAEA_BUILD_ID      => $gaeaBuildId
                ];

                $run = app('cijob')->launchJobs($projectInfo->project_name,$branch, $postJenkinsData, $gaeaBuildId);
                if (!$run) {
                    LogUtil::info('launch jenkins build job error',["data is  ".json_encode($postJenkinsData)], LogUtil::LOG_CI);
                }
                LogUtil::info('launch jenkins build job success',["data is  ".json_encode($postJenkinsData)], LogUtil::LOG_CI);
            }

            if ($item->jenkins_job_name == 'checkcode_'.$item->project_name) {
                //执行代码检查job
                $run = app('sonar')->launchJobs(
                    $projectInfo->project_name,$branch, $checkOutSha, $projectInfo->checkcode_dir, $gaeaBuildId
                );
                if (!$run) {
                    LogUtil::info('launch jenkins check code job error',[$projectInfo->project_name, $checkOutSha, $projectInfo->checkcode_dir, $gaeaBuildId], LogUtil::LOG_CI);
                }
                LogUtil::info('launch jenkins check code job success',[$projectInfo->project_name, $checkOutSha, $projectInfo->checkcode_dir, $gaeaBuildId], LogUtil::LOG_CI);
            }
        }

        return $gaeaBuildId;
    }/*}}}*/

    public function gaeaCreateJenkins(Request $request)
    {/*{{{*/
        $projectId     = $request->input('project_id');
        $createJobName = $request->input('job_name');

        $data = CiBuildSteps::where('project_id', '=', $projectId)
            ->where('jenkins_job_name', '=', $createJobName)->first();

        //dd($data);
        if (JenkinsHelp::gaeaCreateJenkinsJob2($createJobName, $data->dsl_config) == true) {
            return response()->clientSuccess([]);
        }
        return response()->clientError(-1, 'create job error');
    }/*}}}*/

    public function ciStepStateLog (Request $request)
    {/*{{{*/
        $actions = MethodUtil::getActions();
        $this->validate($request, [
            'action'  => "required|in:$actions"
        ]);

        $action = $request->input('action');
        switch ($action) {
        case Constants::REQUEST_TYPE_UPDATE:
            break;
        case Constants::REQUEST_TYPE_GET:
            $this->validate($request, [
                'gaea_build_id' => "required"
            ]);
            //根据gaea_build_id查询数据
            $data = CiStepStateLog::where('gaea_build_id', '=', $request->input('gaea_build_id'))->get();
            $result = [];
            foreach ( $data as $item ) {

                //$logContent = CiCdConstants::$logStepStateDesc[$item->state];
                $logContent = $item->task_step_status_desc;
                $item['log_content'] = $logContent;
                $runTime = '';
                if ($item->started_time != null && $item->finished_time != null) {
                    $a=strtotime($item->started_time);
                    $b=strtotime($item->finished_time);
                    $c=$b-$a;
                    $minute = ceil($c%(60*60*24)/60);
                    $second = ceil($c%(60*60*24)%60);
                    $runTime = '耗时'.$minute.'分'.$second.'秒';
                }
                $item['run_time'] = $runTime;

                $result[] = $item;
            }
            return response()->clientSuccess($result);

            ////return response()->clientSuccess($data);
                    //$data->map(function ($item) {
                        //return call_user_func($item );
                    //}),
            //foreach ( $data as $item ) {
                //$logConteng = CiCdConstants::LogStepStateDesc[$item->state];
            //}
            //return response()->clientSuccess($data);
            break;
        case Constants::REQUEST_TYPE_LIST:
            break;
        };

        //$projectId     = $request->input('project_id');
        //$createJobName = $request->input('job_name');

        //$data = CiBuildSteps::where('project_id', '=', $projectId)
            //->where('jenkins_job_name', '=', $createJobName)->first();

        ////dd($data);
        //if (JenkinsHelp::gaeaCreateJenkinsJob2($createJobName, $data->dsl_config) == true) {
            //return response()->clientSuccess([]);
        //}
        //return response()->clientError(-1, 'create job error');
    }/*}}}*/

    //将默认流程插入数据库；创建job step
    private function saveDefaultJobStep(GitLabHook $gitLabHook)
    {/*{{{*/
        //默认添加两个jenkins job
        $jenkinsSteps = CiBuildSteps::where("project_id", "=", $gitLabHook->getProjectId())->first();
        if ($jenkinsSteps == null ) {
            $jobsList = JenkinsDslHelper::$gaeaSteps;

            //jobs 前端传递 jobs Id 到后端；后端根据id 查询数据
            foreach ($jobsList as $jobItem) {
                $jenkinsJobName = $jobItem['name'].'_'.$gitLabHook->getProjectName(); 
                $jenkinsStepsItem = new CiBuildSteps();
                $jenkinsStepsItem->project_id       = $gitLabHook->getProjectId();
                $jenkinsStepsItem->project_name     = $gitLabHook->getProjectName();
                $jenkinsStepsItem->jenkins_job_id   = $jenkinsJobName;
                $jenkinsStepsItem->jenkins_job_name = $jenkinsJobName;
                $jenkinsStepsItem->jenkins_job_type = $jobItem['job_type'];
                $jenkinsStepsItem->weight           = $jobItem['weight'];
                $jenkinsStepsItem->job_name_pre     = $jobItem['name'];
                $jenkinsStepsItem->dsl_config       = "dsl config 配置文件";
                $jenkinsStepsItem->save();
            }
        }
        LogUtil::info('success',["save build step success; project is  $porjectNmae"], LogUtil::LOG_CI);
    }/*}}}*/

    private function createDefaultJob(GitLabHook $gitLabHook)
    {/*{{{*/
        //创建job;此处 jenkins 分支；用* 代替；构建时，手动check out 分支 
        $projectName    = $gitLabHook->getProjectName();
        $repo           = $gitLabHook->getProjectGitSshUrl();
        $branch         = CiCdConstants::JENKINS_PULL_BRANCHS;
        //$branch         = $gitLabHook->getRefBranch();
        $buildBeforeCmd = '';

        //$result = app('cijob')->createAndUpdateJobs($projectName, $repo, $branch, $buildBeforeCmd);
        $result = app('cijob')->createAndUpdateJobs($projectName, $repo, $branch);
        if (!$result) {
            LogUtil::info('error',["create jenkins build job $porjectNmae is fail"], LogUtil::LOG_CI);
        } else {
            LogUtil::info('success',["create jenkins build job $porjectNmae is success"], LogUtil::LOG_CI);
        }


        $job = app('sonar')->createAndUpdateJobs($projectName, $repo, $branch);
        logutil::info('创建代码检查job', [$job]);
        if (!$job) {
            LogUtil::info('error',["create jenkins checkcode job $porjectNmae is fail"], LogUtil::LOG_CI);
        } else {
            LogUtil::info('success',["create jenkins checkcode job $porjectNmae is success"], LogUtil::LOG_CI);
        }
    }/*}}}*/

    private function saveGitLabPushLog(GitLabHook $gitLabHook)
    {/*{{{*/
        $ciProjectChange = new CiProjectChange();
        $ciProjectChange->project_id          = $gitLabHook->getProjectId();
        $ciProjectChange->project_name        = $gitLabHook->getProjectName();
        $ciProjectChange->checkout_sha        = $gitLabHook->getCheckoutSha();
        $ciProjectChange->push_before         = $gitLabHook->getBefore();
        $ciProjectChange->Push_after          = $gitLabHook->getAfter();
        $ciProjectChange->object_kind         = $gitLabHook->getObjectKind();
        $ciProjectChange->branch              = $gitLabHook->getRefBranch();
        $ciProjectChange->commits             = $gitLabHook->getCommits();
        $ciProjectChange->total_commits_count = $gitLabHook->getTotalCommitsCount();
        $ciProjectChange->change_user_id      = $gitLabHook->getUserId();
        $ciProjectChange->change_user_name    = $gitLabHook->getUserName();
        $ciProjectChange->push_time           = Carbon::now(); 
        $ciProjectChange->save();
    }/*}}}*/

    protected function responseList($paginator, $collection, $callee = 'export')
    {/*{{{*/ 
        return response()->clientSuccess([
            'page' => $paginator->info($collection),
                'results' => $collection->map(function ($item, $key) use ($callee) {
                    return call_user_func([$item, $callee]);
                }),
            ]);
    }/*}}}*/

    private function isListenBranch($projectId, $branch = 'master') 
    {/*{{{*/ 
        $result = false;
        $data = CiProject::where('project_id', '=', $projectId)->first();
        if($data == null) {
            return $result;
        }

        $branchStr = $data->listener_branchs; 
        if (empty($branchStr)) {
            return $result;
        } else {
            LogUtil::info('hook 分支', [$branch]);
            LogUtil::info('被监听的分支', [$branchStr]);
            $listenerBranchs = [];
            $listenerBranchs = explode('|', $branchStr);
            foreach ($listenerBranchs as $val) {
                if ($val == $branch){
                    $result = true;
                    break;
                } 
            }
        }

        return $result;
    }/*}}}*/

    private function buildFinishSendMsg ($projectId, $projectName,$gaeaBuildId, $isSuccess) 
    {/*{{{*/ 
        $userData = CiBuildProject::getBuildUser($projectId, $gaeaBuildId);
        LogUtil::info('build user info', ["$userData"], LogUtil::LOG_CI);
        if (!isset ($userData)) {
            LogUtil::info(' Send Dindin error', ["build finish not find user mobiles "], LogUtil::LOG_CI);
            return false;
        }

        $content =  $this->getSendDingDingContent($projectName, $isSuccess, $userData->nickname);

        $result = app('notice')->sendDindin($userData->mobile, $content);
        if ($result == false) {
            LogUtil::warning('Send Dindin error', ["mobiles" => $userData->mobile, "content" => $content], LogUtil::LOG_JOB);
            LogUtil::info('Send Dindin error', ["request dingding error, mobiles= $userData->mobile, content= $content"], LogUtil::LOG_CI);
        }

        return $result;
    }/*}}}*/

    private function getSendDingDingContent ($projectName, $isSuccess, $nickName) 
    {/*{{{*/ 
        $content = '';
        $content .= "项目构建 \n";
        $content .= "[项目名称]：%s \n";
        $content .= "[发 布 人]：%s \n";
        $content .= $isSuccess ? "[构建结果]: 成功 \n" : "[构建结果]: 失败 \n" ;
        $content .= "[完成时间]：%s \n";
        $content = sprintf( $content, $projectName, $nickName, Carbon::now());
        return $content;
    }/*}}}*/
}

