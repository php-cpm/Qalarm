<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

use DB;
use Log;
use App\Components\Utils\LogUtil;
use App\Components\Utils\Paginator;
use App\Components\Utils\Constants;
use App\Components\Utils\MethodUtil;
use App\Components\Jenkins\JenkinsDslHelper;
use Carbon\Carbon;

use App\Components\Jenkins\CiCdConstants;
use App\Models\Gaea\CiProject;
use App\Models\Gaea\CiBuildSteps;
use App\Models\Gaea\CiProjectMember;

class CiProjectController extends Controller
{
    //public function gitLabProject (Request $request)
    public function ciProject (Request $request)
    {/*{{{*/
        $actions = MethodUtil::getActions();
        $this->validate($request, [
            'action'  => "required|in:$actions"
            ]);

        $action = $request->input('action');
        switch ($action) {
        case Constants::REQUEST_TYPE_UPDATE:
            $this->validate($request, [
                'id'               => "required",
                'ssh_user'         => "required",
                'language'         => "required",
                'language_version' => "required",
                'deploy_dir'       => "required",
                //'project_branch' => "required"
                'listener_branchs' => "required"
                ]);

            //$deployFilesArray = explode('\n', $request->input("deploy_files"));
            //$deployBlackFilesArray = explode('\n', $request->input("deploy_black_files"));

            $ciGitLab = CiProject::where('id','=', $request->input('id'))->first();
            //$ciGitLab->project_job_name = $projectJobName;
            //$ciGitLab->project_addr     = $projectGitUrl;
            $ciGitLab->build_before_sh    = $request->input('build_before_sh');
            $ciGitLab->deploy_after_sh    = $request->input('deploy_after_sh');
            $ciGitLab->ssh_user           = $request->input('ssh_user');
            $ciGitLab->language           = $request->input('language');
            $ciGitLab->language_version   = $request->input('language_version');
            $ciGitLab->checkcode_dir      = $request->input('checkcode_dir');
            $ciGitLab->deploy_dir         = $request->input('deploy_dir');
            //$ciGitLab->project_branch   = $request->input('project_branch');
            $ciGitLab->listener_branchs   = $request->input('listener_branchs');
            $ciGitLab->deploy_files       = str_replace("\n", '|', $request->input('deploy_files'));
            $ciGitLab->deploy_black_files = str_replace("\n", '|', $request->input('deploy_black_files'));
            //$ciGitLab->deploy_files       = json_encode($deployFilesArray); 
            //$ciGitLab->deploy_black_files = json_encode($deployBlackFilesArray); 
            $ciGitLab->save();

            $projectName = $ciGitLab->project_name;
            $repo  = $ciGitLab->project_addr; 
            $branch = CiCdConstants::JENKINS_PULL_BRANCHS;

            //jenkins 不需要分支信息；构建时自动切换分支
            ////修改jenkins job 数据
            $result = app('cijob')->createAndUpdateJobs($projectName, $repo, $branch);
            logutil::info('修改cijob 系统数据', [$result]);
            if (!$result) {
                logutil::error('-1', ['update build job fail']);
            }

            $result2 = app('sonar')->createAndUpdateJobs($projectName, $repo, $branch);
            logutil::info('修改sonar 系统数据', [$result2]);
            if (!$result2) {
                logutil::error('-1', ['update sonar job fail']);
            }

            // 删除构建步骤老数据；增加构建步骤新数据
            //$jobs = CiJenkinsJobs::where([])->get();
            CiBuildSteps::where('project_id', '=', $ciGitLab->project_id)->delete();
            $buildSteps  = $request->input('build_steps');

            $jobsList = JenkinsDslHelper::$gaeaSteps;
            //jobs 前端传递 jobs Id 到后端；后端根据id 查询数据
            foreach ($buildSteps as $value) {
                $jobItem = $jobsList[$value];

                $jenkinsJobName = $jobItem['name'].'_'.$ciGitLab->project_name;

                $jenkinsStepsItem = new CiBuildSteps();
                $jenkinsStepsItem->project_id       = $ciGitLab->project_id;
                $jenkinsStepsItem->project_name     = $ciGitLab->project_name;
                $jenkinsStepsItem->jenkins_job_id   = $jenkinsJobName;
                $jenkinsStepsItem->jenkins_job_name = $jenkinsJobName;
                $jenkinsStepsItem->jenkins_job_type = $jobItem['job_type'];
                $jenkinsStepsItem->weight           = $jobItem['weight'];
                $jenkinsStepsItem->job_name_pre     = $jobItem['name'];
                $jenkinsStepsItem->dsl_config       = "dsl config 配置文件";
                $jenkinsStepsItem->save();
            }
            return response()->clientSuccess([]);
            break;
        case Constants::REQUEST_TYPE_LIST:
            //$filterParams = Input::All();
            //根据当前用户；列出信息
            $isCiSuperUser = CiProjectMember::isCiSuperUser(Constants::getAdminAccount());
            $data = CiProject::getProjectListByAdminAccount(Constants::getAdminAccount(), $isCiSuperUser);
            return response()->clientSuccess(['data' => $data ]);
            break;
        case Constants::REQUEST_TYPE_GET:
            $this->validate($request, [ 'project_id' => "required" ]);
            $data = CiProject::where('project_id', '=', $request->Input('project_id'))->first();
            $ciBuildSteps = $data->ciBuildSteps()->get();
            $data->ci_build_steps = $ciBuildSteps;
            //dd(json_encode($ciBuildSteps));
            return response()->clientSuccess($data);
            break;
        };
    }/*}}}*/

}

