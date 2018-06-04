<?php
namespace App\Models\Gaea;

use Illuminate\Database\Eloquent\Model;

class CiProjectMember extends Gaea
{
    protected $table = 'ci_project_member';
    protected $primaryKey = 'id';

     //ci 用户状态： 是否启用 1 可用 2 禁用
     const MEMBER_ENABLE     = 1;
     const MEMBER_DISABLE    = 2;
     public static $memberStatusDesc = [
         self::MEMBER_ENABLE      => ['color' => 'text-success-dker', 'desc' => '正常'],
         self::MEMBER_DISABLE     => ['color' => 'text-danger-dker',  'desc' => '禁用'],
     ];


     //======================== 用户角色权限 start ================
     //
     const MEMBER_TYPE_ADMIN  = 1;
     const MEMBER_TYPE_DEV    = 2;
     const MEMBER_TYPE_TESTER = 3;
     const MEMBER_TYPE_LOOKER = 4;
     const MEMBER_TYPE_CI_SUPER_USER = 5; //特殊处理的；避免无法维护数据

     public static $memberTypeDesc = [
         self::MEMBER_TYPE_ADMIN  => '管理员',
         self::MEMBER_TYPE_DEV    => '开发人员',
         self::MEMBER_TYPE_TESTER => '测试人员',
         self::MEMBER_TYPE_LOOKER => '关注人员',
     ];

     const IS_CAN_EDIT_PROJECT        = 'can_edit_project';
     const IS_CAN_EDIT_PROJECT_HOST   = 'can_edit_project_host';
     const IS_CAN_EDIT_PROJECT_MEMBER = 'can_edit_project_member';
     const IS_CAN_BUILD_PROJECT       = 'can_build_project';

     const IS_CAN_APPLY_TEST          = 'can_apply_test';
     const IS_CAN_DEPLOY_PROJECT      = 'can_deploy_project';
     const IS_CAN_UPDATE_TEST_RESULT  = 'can_edit_test_result';

     public static $memberPermitList = [
         /*{{{*/
         self::MEMBER_TYPE_ADMIN  => [
            self::IS_CAN_EDIT_PROJECT        => 1, 
            self::IS_CAN_EDIT_PROJECT_HOST   => 1, 
            self::IS_CAN_EDIT_PROJECT_MEMBER => 1, 
            self::IS_CAN_BUILD_PROJECT       => 1, 
            self::IS_CAN_APPLY_TEST          => 1, 
            self::IS_CAN_DEPLOY_PROJECT      => 1,
            self::IS_CAN_UPDATE_TEST_RESULT  => 1,
         ],
         self::MEMBER_TYPE_DEV  => [
            self::IS_CAN_EDIT_PROJECT        => 1, 
            self::IS_CAN_EDIT_PROJECT_HOST   => 1, 
            self::IS_CAN_EDIT_PROJECT_MEMBER => 0, 
            self::IS_CAN_BUILD_PROJECT       => 1, 
            self::IS_CAN_APPLY_TEST          => 1, 
            self::IS_CAN_DEPLOY_PROJECT      => 1,
            self::IS_CAN_UPDATE_TEST_RESULT  => 0,
         ],
         self::MEMBER_TYPE_TESTER  => [
            self::IS_CAN_EDIT_PROJECT        => 0, 
            self::IS_CAN_EDIT_PROJECT_HOST   => 0, 
            self::IS_CAN_EDIT_PROJECT_MEMBER => 0, 
            self::IS_CAN_BUILD_PROJECT       => 0, 
            self::IS_CAN_APPLY_TEST          => 0, 
            self::IS_CAN_DEPLOY_PROJECT      => 1,
            self::IS_CAN_UPDATE_TEST_RESULT  => 1,
         ],
         self::MEMBER_TYPE_LOOKER  => [
            self::IS_CAN_EDIT_PROJECT        => 0, 
            self::IS_CAN_EDIT_PROJECT_HOST   => 0, 
            self::IS_CAN_EDIT_PROJECT_MEMBER => 0, 
            self::IS_CAN_BUILD_PROJECT       => 0, 
            self::IS_CAN_APPLY_TEST          => 0, 
            self::IS_CAN_DEPLOY_PROJECT      => 0,
            self::IS_CAN_UPDATE_TEST_RESULT  => 0,
         ],
         self::MEMBER_TYPE_CI_SUPER_USER  => [
            self::IS_CAN_EDIT_PROJECT        => 1, 
            self::IS_CAN_EDIT_PROJECT_HOST   => 1, 
            self::IS_CAN_EDIT_PROJECT_MEMBER => 1, 
            self::IS_CAN_BUILD_PROJECT       => 1, 
            self::IS_CAN_APPLY_TEST          => 1, 
            self::IS_CAN_DEPLOY_PROJECT      => 1,
            self::IS_CAN_UPDATE_TEST_RESULT  => 1,
         ],/*}}}*/
     ];
     //======================== 用户角色权限 end   ================
    
     //根据 ci member type 获取 权限；动作权限；非数据权限
    public static function getMemberPermitByMemberType ($memberType)
    {/*{{{*/
        $result = [];
        foreach (self::$memberPermitList as $key => $value) {
            if ($key == $memberType) {
                $result = $value;
                break;
            }
        }
        return $result;
    }/*}}}*/

    public static function fetchMemberByProjectId($projectId)
    {/*{{{*/
        $data = CiProjectMember::join('admin_user', 'ci_project_member.gaea_user_id', '=', 'admin_user.id')
            ->where('ci_project_member.project_id', '=', $projectId)
            ->select('ci_project_member.*', 'admin_user.id as user_id','admin_user.username as user_name','admin_user.nickname as nickname', 'admin_user.mobile as mobile') 
            ->orderBy('ci_project_member.member_type')
            ->get();

        return $data;
    }/*}}}*/

    public static function listMemberByProjectId($projectId)
    {/*{{{*/
        $members = CiProjectMember::join('admin_user', 'ci_project_member.gaea_user_id', '=', 'admin_user.id')
            ->where('ci_project_member.project_id', '=', $projectId)
            ->select('ci_project_member.*', 'admin_user.id as user_id','admin_user.username as user_name','admin_user.nickname as nickname', 'admin_user.mobile as mobile') 
            ->orderBy('ci_project_member.member_type')
            ->get();

        $calle = 'export';
        $data = $members->map(function($item, $key) use ($calle) {
            return call_user_func([$item, $calle]);
        });
        return $data;
    }/*}}}*/

    public function export()
     {/*{{{*/
         $data = [];
         $data['id']          = $this->id;
         $data['user_id']     = $this->user_id;
         $data['user_name']   = $this->user_name;
         $data['nick_name']   = $this->nickname;
         $data['mobile']      = $this->mobile;
         $data['state']       = $this->state;
         $data['status_desc']  = self::$memberStatusDesc[$this->state]['desc'];
         $data['status_color'] = self::$memberStatusDesc[$this->state]['color'];

         $data['member_type']       = $this->member_type;
         $data['member_type_desc']  = self::$memberTypeDesc[$this->member_type];

         return $data;;
     }/*}}}*/
    
    public static function isCiSuperUser ($userName)
    {/*{{{*/
        $userConf   = env('CI_SUPER_USERS');
        $superUsers = explode('|', $userConf);
        if (in_array($userName, $superUsers)) {
            return true;
        }
        return false;
    }/*}}}*/
    public function getCiSuperUsers()
    {/*{{{*/
        $userConf   = env('CI_SUPER_USERS');
        $superUsers = explode('|', $userConf);
        return $superUsers;
    }/*}}}*/
}
