<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Log;
use App\Components\Utils\LogUtil;
use Carbon\Carbon;


use App\Models\Gaea\CiProject;
use App\Models\Gaea\CiProjectMember;
use App\Models\Gaea\CiGitMember;
use App\Models\Gaea\AdminUser;

class SyncGitUser extends Command
{
    const PROJECT_MEMBER_SOURCE_GIT    = 1; /*用户来源,git*/
    const PROJECT_MEMBER_SOURCE_GAEA   = 2; /*用户来源,gaea*/

    const PROJECT_MEMBER_MATCH_FAIL    = 0; /*匹配失败*/
    const PROJECT_MEMBER_MATCH_SUCCESS = 1; /*匹配通过*/

    const PROJECT_MEMBER_NO_MATCH      = 0; /*自动匹配失败, 需要手动解决，但是未解决的*/
    const PROJECT_MEMBER_AUTO_MATCH    = 1; /*接口自动匹配,  git 用户与gaea 用户*/
    const PROJECT_MEMBER_HAND_MATCH    = 2; /*人工匹配*/

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gaea:sync_git_user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步git用户信息';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {/*{{{*/
        $gitWebsite = sprintf('%s/api/v3/', env('CI_GITLAB_URL')); // gitlab url
        $gitToken = env('CI_GITLAB_ADMIN_TOKEN'); //admin token

        //$client = new \Gitlab\Client('http://git.yourdomain.com/api/v3/'); // change here
        //$client->authenticate('your_gitlab_token_here', \Gitlab\Client::AUTH_URL_TOKEN); // change here
        $client = new \Gitlab\Client($gitWebsite); // change here
        $client->authenticate($gitToken, \Gitlab\Client::AUTH_URL_TOKEN); // change here

        // 获取git 所有项目
        //$projectAll = $client->api('projects')->all();
        //获取gaea 中监听的项目数据
        $projectAll = CiProject::select('project_id as id','project_name as name')->get();
        //var_dump($projectAll);
        
        $projectMembers =[];
        foreach ( $projectAll as $project ) {
            //dd($project);
            $projectId   = $project['id'];
            $projectName = $project['name'];
            $members = $client->api('projects')->members($projectId);
            $projectMembers []=[
                'project_id'      => $projectId,
                'project_name'    => $projectName,
                'project_members' => $members
            ];
        }

        //CiGitMember::where([])->delete();
        foreach ($projectMembers as $pMs) {

            $projectMs   = $pMs['project_members'];
            $projectId   = $pMs['project_id'];
            $projectName = $pMs['project_name'];
            foreach ($projectMs as $member) {

                //用户是否已经存在于 ci_project_member 中;不在进行添加
                //$mem = CiProjectMember::where('member_username', '=', $member['username'])
                $mem = CiGitMember::where('member_username', '=', $member['username'])
                        ->where('project_id', '=', $projectId)->first();
                if (isset($mem)) {
                   continue; 
                }

                //根据git 用户信息查找gaea 用户；看是否存在
                $gaeaUserInfo = AdminUser::where('username', '=', $member['username'])->first();
                $memberModel = new CiGitMember();
                if (isset($gaeaUserInfo)) {
                    //git 用户信息与gaea 用户信息 匹配
                   $memberModel->gaea_user_id    = $gaeaUserInfo->id;
                   $memberModel->gaea_user_name  = $gaeaUserInfo->username;
                   $memberModel->mobile          = $gaeaUserInfo->mobile;
                   $memberModel->is_match        = self::PROJECT_MEMBER_MATCH_SUCCESS;
                   $memberModel->match_type      = self::PROJECT_MEMBER_AUTO_MATCH;

                } else {
                   // 不匹配aea 用户信息 匹配
                   $memberModel->is_match        = self::PROJECT_MEMBER_MATCH_FAIL;
                   $memberModel->match_type      = self::PROJECT_MEMBER_NO_MATCH;
                }
                $memberModel->project_id      = $projectId;
                $memberModel->project_name    = $projectName;
                $memberModel->member_id       = $member['id'];
                $memberModel->member_name     = $member['name'];
                $memberModel->member_username = $member['username'];
                $memberModel->state           = $member['state'];
                $memberModel->access_level    = $member['access_level'];
                $memberModel->member_source   = self::PROJECT_MEMBER_SOURCE_GIT;
                $memberModel->save();
                
                
                //自动匹配的数据；添加的 ci project member中
                $memberPro = new CiProjectMember();
                if (isset($gaeaUserInfo)) {
                    $mem2 = CiProjectMember::where('gaea_user_id', '=', $gaeaUserInfo->id)
                            ->where('project_id', '=', $projectId)->first();
                    if (!isset($mem2)) {
                        //git 用户信息与gaea 用户信息 匹配
                        $memberPro->project_id      = $projectId;
                        $memberPro->project_name    = $projectName;
                        $memberPro->gaea_user_id    = $gaeaUserInfo->id;
                        $memberPro->gaea_user_name  = $gaeaUserInfo->username;
                        $memberPro->state           = $member['state'] == 'active' ? CiProjectMember::MEMBER_ENABLE : CiProjectMember::MEMBER_DISABLE;
                        $memberPro->member_type     = CiProjectMember::MEMBER_TYPE_DEV;
                        $memberPro->operate_user_id = 0;
                        $memberPro->operate_user_name = 'sys';
                        $memberPro->save();
                    }
                }
            }
        }
    }/*}}}*/
}
