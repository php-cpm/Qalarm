<?php
namespace App\Models\Gaea;

use Illuminate\Database\Eloquent\Model;
use DB;

use App\Components\Jenkins\CiCdConstants;
use App\Models\Gaea\CiProjectMember;

class CiCdProject extends Gaea
{
    protected $table = 'ci_cd_project';
    protected $primaryKey = 'id';

    protected $fillable = [
        'deploy_id', 'gaea_build_id', 'project_id', 'project_name', 'deploy_step',
        'deploy_action', 'deploy_status', 'started_time', 'title',
        'desc', 'user_id', 'user_name'
    ];

    public function ciBuildProject()
    {/*{{{*/
        return $this->hasOne('App\Models\Gaea\CiBuildProject', 'gaea_build_id', 'gaea_build_id');
    }/*}}}*/

    public function ciTestReport()
    {/*{{{*/
        return $this->hasOne('App\Models\Gaea\CiTestReport', 'gaea_build_id', 'gaea_build_id');
    }/*}}}*/

    public static function getCiDeployListByAdminAccount ($account, $projectId, $status, $isCiSuperUser = false)
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
                $query->where('deploy_status', $status);
            }
            $query->orderBy('id', 'desc');
        } else {
            $query = self::join('ci_project_member', function ($join){
                    $join->on('ci_cd_project.project_id', '=', 'ci_project_member.project_id');
                })->select('ci_cd_project.*', 'ci_project_member.member_type');
            if (!empty($projectId)) {
                $query->where('ci_cd_project.project_id', $projectId);
            }
            if (!empty($status)) {
                //$query->where('ci_cd_project.status', $status);
                $query->where('ci_cd_project.deploy_status', $status);
            }
            $query->where('ci_project_member.gaea_user_name', '=', $account);
            $query->distinct();
            $query->orderBy('ci_cd_project.id', 'desc');
        }
        return $query;
    }/*}}}*/

    public function export ()
    {/*{{{*/

        //构建信息
        $ciBuildInfo  = $this->ciBuildProject;
        $ciTestReport = $this->ciTestReport;

        $runTime = '';
        if ($this->started_time != null && $this->finished_time != null) {
            $a=strtotime($this->started_time);
            $b=strtotime($this->finished_time);
            $c=$b-$a;
            $minute = ceil($c%(60*60*24)/60);
            $second = ceil($c%(60*60*24)%60);
            $runTime = '耗时'.$minute.'分'.$second.'秒';
        }

        // A_Level 成功；可以发布线上
        // 发布线上按钮 和 slave 环境测试结果
        //deployStatusBeta
        //$canCdALevel = $this->status == CiCdConstants::$deployStatusBeta[CiCdConstants::KEY_SUCCESS] ? true : false;
        //$canCdBLevel = $this->status == CiCdConstants::$deployStatusALevel[CiCdConstants::KEY_SUCCESS]    ? true : false;
        //$canRollBack = true;
        
        $canCdALevel = false;
        if ($this->deploy_step   == CiCdConstants::DEPLOY_STEP_BETA     &&
            $this->deploy_action == CiCdConstants::DEPLOY_ACTION_DEPLOY &&
            $this->deploy_status == CiCdConstants::DEPLOY_STATUS_SUCCESS) {
                $canCdALevel = true;
        }

        $canCdBLevel = false;
        if ($this->deploy_step   == CiCdConstants::DEPLOY_STEP_A_LEVEL  &&
            $this->deploy_action == CiCdConstants::DEPLOY_ACTION_DEPLOY &&
            $this->deploy_status == CiCdConstants::DEPLOY_STATUS_SUCCESS) {
                $canCdBLevel = true;
        }

        $canRollBack = true;

        $data = [
            "id"                     => $this->id,
            "deploy_id"              => $this->deploy_id,
            "gaea_build_id"          => $this->gaea_build_id,
            "project_id"             => $this->project_id,
            "project_name"           => $this->project_name,
            "status"                 => $this->deploy_status,
            //"status_desc"            => CiCdConstants::$deployStatusDesc[$this->status],
            //"status_desc"            => $this->deploy_status,
            //"status_desc"            => $this->deploy_step . '_' . $this->deploy_action . '_' . $this->deploy_status,
            "status_desc"            => CiCdConstants::getDeployStepACtionStatusDesc($this->deploy_step, $this->deploy_action, $this->deploy_status),
            "started_time"           => $this->started_time,
            "finished_time"          => $this->finished_time,
            "title"                  => $this->title,
            "desc"                   => $this->desc,
            "user_id"                => $this->user_id,
            "user_name"              => $this->user_name,
            "checkout_sha"           => $this->checkout_sha,
            "run_time"               => $runTime,
            "update_id"              => $ciBuildInfo->update_id,

            //"is_can_deploy_online"   => $isCanDeployOnline,
            //"is_can_a_level_test"   => $isCanALevelTest,

            "can_cd_a_level"   => $canCdALevel,
            "can_cd_b_level"   => $canCdBLevel,
            "can_rollback"     => $canRollBack,
            //"update_id"              => $ciTestReport,
            //"ci_deploy_project_logs" => $deployProjectLogs,
            "member_permits" => CiProjectMember::getMemberPermitByMemberType($this->member_type),
        ];
        return $data;
    }/*}}}*/

    public static function getDownRollbackDeployFilesUrl($productId, $deployStep, $deployId)
    {/*{{{*/

        $data = CiCdProject::where('deploy_id', '=', $deployId)->first();
        if (!isset($data)) {
            return '';
        }

        //获取比当前部署id 小；切成功的最大id 数据
        $res = CiCdProject::where('project_id',     '=' , $productId)
                            ->where('deploy_step',   '=' , $deployStep)
                            ->where('deploy_action', '=' , 'deploy')
                            ->where('deploy_status', '=' , 'success')
                            ->where('id',            '<' , $data->id)
                            ->orderBy('id', 'desc')
                            ->first();

        if (!isset($res)) {
            return '';
        }

        return $res->gaea_build_id;
    }/*}}}*/
}
