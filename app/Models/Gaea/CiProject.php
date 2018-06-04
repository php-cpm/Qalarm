<?php
namespace App\Models\Gaea;

use Illuminate\Database\Eloquent\Model;
use App\Components\Jenkins\JenkinsDslHelper;
use App\Models\Gaea\CiProjectMember;

use DB;

class CiProject extends Gaea
{
    protected $table = 'ci_project';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function ciBuildSteps()
    {
        return $this->hasMany('App\Models\Gaea\CiBuildSteps', 'project_id', 'project_id');
    }

    public function export ()
    {/*{{{*/
        //$ciBuildStep = $this->ci_build_steps;
        $ciBuildSteps = $this->ciBuildSteps;
        $jobs = [];
        foreach (JenkinsDslHelper::$gaeaSteps as $key=>$item) {
            $jobs[] = [
                //'id'     => $item['name'].'_'.$this->project_name,
                'id'     => $key,
                'name'     => $item['name'],
                'sname'   => $item['sname'],
                'weight' => $item['weight'],
                'job_type' => $item['job_type']
            ];
        }
 
        //gaea 监听的构建分支
        $listenerBranchs = [];
        if (!empty($this->listener_branchs)) {
            $lBranchs = explode('|', $this->listener_branchs);
            foreach ($lBranchs as $key=>$val) {
                $listenerBranchs[] = ['id' => $val, 'name' => $val];
            }
        }

        //每个项目的所有分支
        $projectBranchs = [];
        if (!empty($this->project_branchs)) {
            $lBranchs = explode('|', $this->project_branchs);
            foreach ($lBranchs as $key=>$val) {
                $projectBranchs[] = ['id' => $val, 'name' => $val];
            }
        }

        $data = [
            "id"                => $this->id,
            "project_id"        => $this->project_id,
            "project_name"      => $this->project_name,
            "project_addr"      => $this->project_addr,
            "project_desc"      => $this->project_desc,
            "project_homepage"  => $this->project_homepage,
            "visibility_level"  => $this->visibility_level,
            "project_user_id"   => $this->project_user_id,
            "project_user_name" => $this->project_user_name,
            "project_job_name"  => $this->project_job_name,
            "gitlab_website"    => $this->gitlab_website,
            "build_step"        => $this->build_step,
            "build_before_sh"   => $this->build_before_sh,
            "deply_after_sh"    => $this->deply_after_sh,
            "ssh_user"          => $this->ssh_user,
            "language"          => $this->language,
            "language_version"  => $this->language_version,
            "checkcode_dir"     => $this->checkcode_dir,
            "deploy_dir"        => $this->deploy_dir,
            "deploy_after_sh"   => $this->deploy_after_sh,
            //"project_branch"    => $this->project_branch,
            "ci_build_steps"    => $ciBuildSteps,
            "jobs"              => $jobs,
            "listener_branchs"  => $listenerBranchs,
            "project_branchs"   => $projectBranchs,
            "deploy_files"      => str_replace('|', "\n", $this->deploy_files),
            "deploy_black_files"  => str_replace('|', "\n",$this->deploy_black_files),
            //"deploy_files"      => $deployFiles,
            //"deploy_black_files"  => $deployBlackFiles,
            "member_permits" => CiProjectMember::getMemberPermitByMemberType($this->member_type),
        ];
        return $data;
    }/*}}}*/

    public static function getProjectListByAdminAccount ($account, $isCiSuperUser=false)
    {/*{{{*/
        if ($isCiSuperUser) {
            //超级管理员逻辑
            $selectStr = sprintf('*, %s as member_type', CiProjectMember::MEMBER_TYPE_CI_SUPER_USER);
            $data = self::select(DB::raw($selectStr))
                ->orderBy('id', 'desc')->get();
            //dd($data);
            ////手动增加member_type = 5
            //$data = $data->map(function($item) {
                      //$item->member_type = CiProjectMember::MEMBER_TYPE_CI_SUPER_USER; 
                      //return $item; 
                  //});
        } else {
            $data = self::join('ci_project_member', function ($join){
                    $join->on('ci_project.project_id', '=', 'ci_project_member.project_id');
                })
                ->select('ci_project.*', 'ci_project_member.member_type')
                ->where('ci_project_member.gaea_user_name', '=', $account)
                ->distinct()
                ->orderBy('ci_project.id', 'desc')->get();
        }

        $callee='export';
        $result = $data->map(function($item, $key) use ($callee) {
                      return call_user_func([$item, $callee]);
                  });
        return $result;
    }/*}}}*/

    //用于下拉框 ;只返回id和name
    public static function getProjectsByAdminAccount ($account, $isCiSuperUser=false)
    {/*{{{*/
        $data = [  ];
        if ($isCiSuperUser) {
            //超级管理员逻辑
            $selectStr = sprintf('*, %s as member_type', CiProjectMember::MEMBER_TYPE_CI_SUPER_USER);
            $data = self::select(DB::raw($selectStr))
                ->orderBy('id', 'desc')->get();
        } else {
            $data = self::join('ci_project_member', function ($join){
                    $join->on('ci_project.project_id', '=', 'ci_project_member.project_id');
                })
                //->select('ci_project.project_id, ci_project.project_name')
                ->select('ci_project.*')
                //->where('ci_project_member.member_username', '=', $account)
                ->where('ci_project_member.gaea_user_name', '=', $account)
                ->distinct()
                ->orderBy('ci_project.id', 'desc')->get();
        }

        return $data;
    }/*}}}*/
}
