<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Exception;
use RuntimeException;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Carbon\Carbon;

use App\Components\Utils\Paginator; 
use App\Components\Utils\ErrorCodes;
use App\Components\Utils\Constants;
use App\Components\JobDone\JobDone;

use App\Components\DNSPod\DNSPod;

use App\Models\Gaea\OpNotice;
use App\Models\User;

use App\Jobs\NoticeManageJob;

use JenkinsKhan\Jenkins;
use App\Models\Gaea\CiProjectHost;



use App\Components\Kubernetes\Kubernetes;

class DevelopController extends Controller
{
    /**
     * 添加通知任务
     * @return Response
     */
    public function index(Request $request)
    {

        $dnspod = new DNSPod(env('DNSPOD_USERNAME'), env('DNSPOD_PASSWORD'));
        // dd($dnspod->domainList());
        dd($dnspod->recordList('11709446'));
        // dd(CiProjectHost::fetchHostListByProjectId(24));
        $k8s = Kubernetes::getClientInstance();
        // $repo = Kubernetes::createNamespace();

        $projectName = 'ci-24-test4';
        $version = 'v1.0';
        
        $result = Kubernetes::createOrUpdateService($projectName, ['12810']);

        var_export($result, true);

        // $result = Kubernetes::deleteService($projectName);
        var_export($result, true);
        
        $result = Kubernetes::createOrUpdateRc($projectName, ['12810'], ['/run.sh'], ['/data'], 4, $version);

        var_dump($result);

        // $result = Kubernetes::deleteRc($projectName, $version);
        //
        $result = Kubernetes::getPodsByRc($projectName, $version);

        $result = Kubernetes::getCurrentRc($projectName);

        $result = Kubernetes::updateRcReplica($result, 3);

        dd($result);

        // dd($repo);

        // jenkins
        $url = 'http://172.16.10.160:8080/jenkins';
        $url = 'http://172.16.10.10:8080/';
        $url = 'http://172.16.10.196:2346/';
        $jenkins = new Jenkins($url);
        $views = ($jenkins->getViews());

        foreach ($views as $view) {
            var_dump($view->getName());
            foreach ($view->getJobs() as $job) {
                if ($job == false) {
                    continue;
                    echo 'this is error data';
                }
                var_dump($job->getBuilds());
                var_dump($job->getColor());
            }
        }

        $createJobTemplate = <<<STR
<?xml version='1.0' encoding='UTF-8'?>
<project>
  <actions/>
  <description></description>
  <keepDependencies>false</keepDependencies>
  <properties/>
  <scm class="hudson.plugins.git.GitSCM" plugin="git@2.4.1">
    <configVersion>2</configVersion>
    <userRemoteConfigs>
      <hudson.plugins.git.UserRemoteConfig>
        <url>http://172.16.10.196:8360/root/test_demo.git</url>
        <credentialsId>29257ecb-3d0f-4d7e-aa5e-107c795fc2bb</credentialsId>
      </hudson.plugins.git.UserRemoteConfig>
    </userRemoteConfigs>
    <branches>
      <hudson.plugins.git.BranchSpec>
        <name>*/master</name>
      </hudson.plugins.git.BranchSpec>
    </branches>
    <doGenerateSubmoduleConfigurations>false</doGenerateSubmoduleConfigurations>
    <submoduleCfg class="list"/>
    <extensions/>
  </scm>
  <canRoam>true</canRoam>
  <disabled>false</disabled>
  <blockBuildWhenDownstreamBuilding>false</blockBuildWhenDownstreamBuilding>
  <blockBuildWhenUpstreamBuilding>false</blockBuildWhenUpstreamBuilding>
  <triggers/>
  <concurrentBuild>false</concurrentBuild>
  <builders/>
  <publishers/>
  <buildWrappers/>
</project>
STR;
        //$jenkins->createJob('llw_cccccc',$createJobTemplate);
        echo file_get_contents('jenkins_temlate/create_job.xml');
        $data = simplexml_load_file('jenkins_temlate/create_job.xml');
        var_dump($data);

        var_dump($data->scm->branches);
        exit;



        // jobdon 
        $cmd = app('jobdone')->fetchJobDoneCommand('1_1_1', ['num1'=>1, 'num2'=>2]);

        $result = app('jobdone')->doJob(JobDone::API_CMD_EXEC, [
            'tgt'   => 'data01v.corp.ttyc.com',
            'cmd'   => $cmd,
            'sync'  => 1,
        ]);
        dd($result);
        #'cmd'   => 'cd /home/t/system/gaea-client;perl saltTask.pl checkdisk_usage.sh gaea %22%22 180 67eaa6cf5990393d30818eb26fda2c95 0',
        
        $job = [
            [
            'tgt'     => 'data01v.corp.ttyc.com',
            'cmd'     => 'uname -r',
            'timeout' => '180'
            ],
            [
            'tgt'     => 'data01v.corp.ttyc.com',
            'cmd'     => 'ifconfig',
            'timeout' => '180'
            ],
        ];
        $result = app('jobdone')->doJob(JobDone::API_GROUP_EXEC, [
            'tasks' => json_encode($job),
        ]);
        dd($result);

        DB::connection('gaea')->beginTransaction(); 
        try {
            $notice = new OpNotice();
            {
                $notice->notice_type       = 1;
                $notice->active_id         = 0;
                $notice->notice_link       = '';
                $notice->notice_time       = Carbon::now();  
                $notice->notice_way        = 1;
                $notice->sms_channel       = 1;
                $notice->notice_content    = 'a';
                $notice->notice_remark     = $request->input('push_remark', '');
                $notice->notice_mobiles    = '13658364971';
                $notice->notice_userids    = '';
                $notice->notice_count      =1;
                $notice->completed_count   = 0;

                $notice->started_at        = Carbon::now();

                //$notice->admin_user_id  = isset(Constants::$admin['admin_id'])?Constants::$admin['admin_id']:'';
                //$notice->admin_user_name= isset(Constants::$admin['admin_name'])?Constants::$admin['admin_name']:'';
                //llw update
                $notice->admin_user_id   = Constants::getAdminId();
                $notice->admin_user_name = Constants::getAdminName();
            }
            $notice->save();

            $job = new NoticeManageJob($notice);
            $this->dispatch($job);

            DB::connection('gaea')->commit();
        } catch(\Exception $e) {
            DB::connection('gaea')->rollback();
            throw $e;
        }

        return response()->clientSuccess(['id'=>$notice->id]);
    }
}
