<?php
namespace App\Models\Gaea;

use Illuminate\Database\Eloquent\Model;
use App\Components\Utils\MethodUtil;
use App\Components\Jenkins\CiCdConstants;

use App\Models\Gaea\CiProjectMember;
use App\Models\Gaea\CiCdProject;
use DB;

class CiBuildProject extends Gaea
{
    protected $table = 'ci_build_project';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function ciBuildProjectLogs()
    {/*{{{*/
        return $this->hasMany('App\Models\Gaea\CiBuildProjectLog', 'gaea_build_id', 'gaea_build_id');
    }/*}}}*/

    public function ciProject()
    {/*{{{*/
        return $this->hasOne('App\Models\Gaea\CiProject', 'project_id', 'project_id');
    }/*}}}*/

    public function ciTestReport()
    {/*{{{*/
        return $this->hasOne('App\Models\Gaea\CiTestReport', 'gaea_build_id', 'gaea_build_id');
    }/*}}}*/

    public function export ()
    {/*{{{*/
        $ciProject  = $this->ciProject;
        //此次构建是否提测
        
        //=== 是否提测；测试报告 start =====
        $ttt = $this->CiTestReport;
        //0 未提测, 1,提测，未出结果； 2.已经测结束
        $testReport = [
            'is_test' => false, 
            'test_result_status' => -100,
            'test_result_status_desc' => '',
        ];

        $canTestReport = true; //提测
        $canWriteTestReport = true;

        if ( isset($ttt) ) {
            $canTestReport = false;

            $testResultStatusDesc = CiCdConstants::$testResultStatusDesc[$ttt->test_result_status];
            $testReport = [ 
                'is_test' => true, 
                'test_result_status' => $ttt->test_result_status,
                'test_result_status_desc' => $testResultStatusDesc,
            ];
        }

        //$isDeployInfo = CiDeployOperate::where('gaea_build_id', '=', $this->gaea_build_id)->first(); 
        ////dd($isDeployInfo);
        //if (isset($isDeployInfo)) {
            ////echo $isDeployInfo->status;
            ////echo '<br/>';
            //if (!in_array($isDeployInfo->status, CiCdConstants::$isCanWriteTestReport)) {
                //$canWriteTestReport = false;
            //}
        //}
        $isDeployInfo = CiCdProject::where('gaea_build_id', '=', $this->gaea_build_id)->first(); 
        if (isset($isDeployInfo)) {
            //if (!in_array($isDeployInfo->status, CiCdConstants::$isCanWriteTestReport)) {
            if ($isDeployInfo->deploy_step == CiCdConstants::DEPLOY_STEP_BETA) {
                $canWriteTestReport = false;
            }
        }

        //$canWriteTestReport = true;
        //=== 是否提测；测试报告 end =====

        $canDeploy = $this->status == CiCdConstants::CI_BUILD_STATUS_SUCCESS ? true : false;

        $ciBuildLogs = $this->ciBuildProjectLogs;
        $buildLogs = [];
        if ( isset ($this->ciBuildProjectLogs) ) {
            foreach ($this->ciBuildProjectLogs as $item) {
                //$statusDesc = CiCdConstants::$JenkinsJobStatusDesc[$item->status];
                $buildLogs[] = [
                    'id'  => $item->id,
                    'gaea_build_id'  => $item->gaea_build_id,
                    'project_id'  => $item->project_id,
                    'project_name'  => $item->project_name,
                    'update_id'  => $item->update_id,
                    'jenkins_build_id'  => $item->jenkins_build_id,
                    'jenkins_job_name'  => $item->jenkins_job_name,
                    'build_step_id'  => $item->build_step_id,     
                    'status'  => $item->status,
                    //'status_desc'  => $statusDesc
                ];
            }
        }

        $runTime = '';
        if ($this->createtime != null && $this->finishtime != null) {
            $a=strtotime($this->createtime);
            $b=strtotime($this->finishtime);
            $c=$b-$a;
            $minute = ceil($c%(60*60*24)/60);
            $second = ceil($c%(60*60*24)%60);
            $runTime = '耗时'.$minute.'分'.$second.'秒';
        }

        $projectBranch = $this->branch;
        $sonarUrl = env('CI_SONAR_URL').'/dashboard/index/'.$this->project_name.':'.$projectBranch;
        //$sonarUrl = env('CI_SONAR_URL').'?id='.$this->project_name;
        $data = [
                "id"                    => $this->id,
                "gaea_build_id"         => $this->gaea_build_id,
                "gaea_build_number"     => $this->gaea_build_number,
                "project_id"            => $this->project_id,
                "project_name"          => $this->project_name,
                "update_id"             => $this->update_id,
                "status"                => $this->status,
                "createtime"            => $this->createtime,
                "finishtime"            => $this->finishtime,
                //"build_result_file"     => $this->build_result_file,
                "branch"                => $this->branch,
                "sonar_url"             => $sonarUrl,
                //"ci_build_project_logs" => $ciBuildLogs,
                "ci_build_project_logs" => $buildLogs,
                //"ci_project_change"     => $projectChangeLogs,
                "run_time"              => $runTime,
                "deploy_status"         => $ciProject->deploy_status,

                "test_report"           => $testReport,
                "can_deploy"            => $canDeploy,
                "can_write_test_report" => $canWriteTestReport,

                "member_permits" => CiProjectMember::getMemberPermitByMemberType($this->member_type),
            ];
        return $data;
    }/*}}}*/

    //public function exportOne ()
    //{[>{{{<]
        //$ciBuildLogs        = $this->ciBuildProjectLogs;
        //$projectChangeLogs  = $this->ciProjectChange;
        //$ciProject  = $this->ciProject;
        
        //$runTime = '';
        //if ($this->createtime != null && $this->finishtime != null) {
            //$a=strtotime($this->createtime);
            //$b=strtotime($this->finishtime);
            //$c=$b-$a;
            //$minute = ceil($c%(60*60*24)/60);
            //$second = ceil($c%(60*60*24)%60);
            //$runTime = '耗时'.$minute.'分'.$second.'秒';
        //}

        //$projectBranch = 'master';
        //$sonarUrl = env('CI_SONAR_URL').'/dashboard/index/'.$this->project_name.':'.$projectBranch;
        ////$sonarUrl = env('CI_SONAR_URL').'?id='.$this->project_name;
        //$data = [
                //"id"                    => $this->id,
                //"gaea_build_id"         => $this->gaea_build_id,
                //"gaea_build_number"     => $this->gaea_build_number,
                //"project_id"            => $this->project_id,
                //"project_name"          => $this->project_name,
                //"update_id"             => $this->update_id,
                //"status"                => $this->status,
                //"createtime"            => $this->createtime,
                //"finishtime"            => $this->finishtime,
                //"build_result_file"     => $this->build_result_file,
                //"branch"                => $this->branch,
                //"sonar_url"             => $sonarUrl,
                //"ci_build_project_logs" => $ciBuildLogs,
                //"ci_project_change"     => $projectChangeLogs,
                //"run_time"              => $runTime,
                //"deploy_status"         => $ciProject->deploy_status,
            //];
        //return $data;
    //}[>}}}<]

    public static function downDiffFilesUrl($jenkinsJobName, $gaeaBuildId)
    {/*{{{*/
        $envName = MethodUtil::getLaravelEnvName();
        $downDiffFileUrl = sprintf("%s/%s/%s/build_history/%s/%s", env('CI_GAEA_DWONFILE_ADDR'), $envName,$jenkinsJobName,$gaeaBuildId,'diff_patch.tar.gz');
        return $downDiffFileUrl;
    }/*}}}*/

    public static function getDownDeployFilesUrl($gaeaBuildId)
    {/*{{{*/
        $data = CiBuildProject::where('gaea_build_id', '=', $gaeaBuildId)->first();
        if (!isset($data)) {
            return null;
        }
        $ciGaeaDownFileAddr = env('CI_GAEA_DWONFILE_ADDR');
        $jenkinsJobName     = 'build_'.$data->project_name;
        $gaeaBuildId        = $data->gaea_build_id;
        $envName            = MethodUtil::getLaravelEnvName();
        $patchName          = 'build_'.$data->project_name.'.tar.gz';
        //$diffPatchName      = 'diff_patch.tar.gz';
        $downDiffFileUrl    = sprintf("%s/%s/%s/build_history/%s/%s", $ciGaeaDownFileAddr, $envName,$jenkinsJobName,$gaeaBuildId,$patchName);
        return $downDiffFileUrl;
    }/*}}}*/

    public static function getDownDiffFilesUrl($gaeaBuildId)
    {/*{{{*/
        $data = CiBuildProject::where('gaea_build_id', '=', $gaeaBuildId)->first();
        if (!isset($data)) {
            return null;
        }
        $ciGaeaDownFileAddr = env('CI_GAEA_DWONFILE_ADDR');
        $jenkinsJobName     = 'build_'.$data->project_name;
        $gaeaBuildId        = $data->gaea_build_id;
        $envName            = MethodUtil::getLaravelEnvName();
        $diffPatchName      = 'diff_patch.tar.gz';
        $downDiffFileUrl    = sprintf("%s/%s/%s/build_history/%s/%s", $ciGaeaDownFileAddr, $envName,$jenkinsJobName,$gaeaBuildId,$diffPatchName);
        return $downDiffFileUrl;
    }/*}}}*/

    public static function getDownDiffFilesMd5Url($gaeaBuildId)
    {/*{{{*/
        $data = CiBuildProject::where('gaea_build_id', '=', $gaeaBuildId)->first();
        if (!isset($data)) {
            return null;
        }
        $ciGaeaDownFileAddr = env('CI_GAEA_DWONFILE_ADDR');
        $jenkinsJobName     = 'build_'.$data->project_name;
        $gaeaBuildId        = $data->gaea_build_id;
        $envName            = MethodUtil::getLaravelEnvName();
        $diffPatchName      = 'diff_file_md5.md';
        $downDiffFileMd5Url = sprintf("%s/%s/%s/build_history/%s/%s", $ciGaeaDownFileAddr, $envName,$jenkinsJobName,$gaeaBuildId,$diffPatchName);
        return $downDiffFileMd5Url;
    }/*}}}*/

    public static function getOldBuildSrcUrl ($gaeaBuildId)
    {/*{{{*/
            //return '';
        //查询本次构建数据，然后在查询上次构建
        $currentBuild = CiBuildProject::where('gaea_build_id', '=', $gaeaBuildId)->first();
        $oldBuild = CiBuildProject::where('project_id', '=', $currentBuild->project_id)
            ->where('status', '=', CiCdConstants::CI_BUILD_STATUS_SUCCESS)
            ->where('branch', '=',  $currentBuild->branch)
            //->where('project_name', '=', $currentBuild->project_name)
            ->where('id', '<', $currentBuild->id)
            ->orderBy('id', 'desc')->first();
        if (!isset($oldBuild)){
            return '';
        }

        $ciGaeaDownFileAddr = env('CI_GAEA_DWONFILE_ADDR');
        $jenkinsJobName     = 'build_'.$oldBuild->project_name;
        $gaeaBuildId        = $oldBuild->gaea_build_id;
        $envName            = MethodUtil::getLaravelEnvName();
        $diffPatchName      = 'build_'.$oldBuild->project_name.'-src.tar.gz';
        $oldBuildUrl        = sprintf("%s/%s/%s/build_history/%s/%s", $ciGaeaDownFileAddr, $envName,$jenkinsJobName,$gaeaBuildId,$diffPatchName);
        return $oldBuildUrl;
    }/*}}}*/

    public static function getOldBuildDeployPackageUrl ($gaeaBuildId)
    {/*{{{*/
            //return '';
        //查询本次构建数据，然后在查询上次构建
        $currentBuild = CiBuildProject::where('gaea_build_id', '=', $gaeaBuildId)->first();
        $oldBuild = CiBuildProject::where('project_id', '=', $currentBuild->project_id)
            ->where('status', '=', CiCdConstants::CI_BUILD_STATUS_SUCCESS)
            ->where('branch', '=',  $currentBuild->branch)
            //->where('project_name', '=', $currentBuild->project_name)
            ->where('id', '<', $currentBuild->id)
            ->orderBy('id', 'desc')->first();
        if (!isset($oldBuild)){
            return '';
        }

        $ciGaeaDownFileAddr = env('CI_GAEA_DWONFILE_ADDR');
        $jenkinsJobName     = 'build_'.$oldBuild->project_name;
        $gaeaBuildId        = $oldBuild->gaea_build_id;
        $envName            = MethodUtil::getLaravelEnvName();
        $diffPatchName      = 'build_'.$oldBuild->project_name.'.tar.gz';
        $oldBuildUrl        = sprintf("%s/%s/%s/build_history/%s/%s", $ciGaeaDownFileAddr, $envName,$jenkinsJobName,$gaeaBuildId,$diffPatchName);
        return $oldBuildUrl;
    }/*}}}*/

    public static function getCiBuildListByAdminAccount ($account, $projectId, $status, $isCiSuperUser = false)
    {/*{{{*/
        $query = null;
        if ($isCiSuperUser) {
            //超级管理员逻辑
            $selectStr = sprintf('*, %s as member_type', CiProjectMember::MEMBER_TYPE_CI_SUPER_USER);
            $query = self::select(DB::raw($selectStr));
            if (!empty($projectId)) {
                $query->where('project_id', $projectId);
            }
            if (!empty($status)) {
                $query->where('status', $status);
            }
            //empty($status) == false ? $query->where('status', $status) : '';
            $query->orderBy('id', 'desc');
        } else {
            $query = self::join('ci_project_member', function ($join){
                    $join->on('ci_build_project.project_id', '=', 'ci_project_member.project_id');
                })->select('ci_build_project.*', 'ci_project_member.member_type');

            if (!empty($projectId)) {
                $query->where('ci_build_project.project_id', $projectId);
            }
            if (!empty($status)) {
                $query->where('ci_build_project.status', $status);
            }
            $query->where('ci_project_member.gaea_user_name', '=', $account);
            $query->distinct();
            $query->orderBy('ci_build_project.id', 'desc');
        }
        return $query;
    }/*}}}*/

    public static function getBuildUser($projectId, $buildId)
    {/*{{{*/
        $data = CiBuildProject::join('admin_user', 'ci_build_project.user_id', '=', 'admin_user.id')
            ->where('ci_build_project.project_id', '=', $projectId)
            ->where('ci_build_project.gaea_build_id', '=', $buildId)
            ->select('ci_build_project.project_id', 'ci_build_project.project_name','admin_user.id as user_id','admin_user.username as user_name','admin_user.nickname as nickname', 'admin_user.mobile as mobile') 
            ->first();
        return $data;
    }/*}}}*/
}
