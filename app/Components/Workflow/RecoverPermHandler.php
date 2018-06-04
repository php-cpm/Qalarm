<?php
namespace App\Components\Workflow;

use DB;

use App\Components\Utils\LogUtil;
use app\Models\Common\Mail;
use App\Job\MailJob;
use App\Models\Gaea\Workflow;

use App\Components\JobDone\JobDone;

class RecoverPermHandler extends WorkflowHandler
{
    public function doing($workflow)
    {
        $attachment = json_decode($workflow->attachment, true);
        $recoverUsername = $attachment['recover_username'];

        // 1、删除服务器权限，（只做机器权限锁定，暂时不删除）
        $opsHosts = DB::table('ops_user_host_perm')
            ->select('ops_host.server_name', 'ops_host.ip')
            ->join('admin_user', function($join) use ($recoverUsername) {
                $join->on('ops_user_host_perm.user_id', '=', 'admin_user.id')
                     ->where('admin_user.username', '=', $recoverUsername);
            })
            ->leftJoin('ops_host', function($join) {
                $join->on('ops_user_host_perm.host_id', '=', 'ops_host.id');
            })
            ->get();

        $serverNames = [];
        foreach ($opsHosts as $h) {
            $serverNames[] = $h->server_name;
        }

        if (!empty($serverNames)) {
            LogUtil::info("RecoverUsername:$recoverUsername, recover hosts:", $serverNames);
        }
        return ['errno' => -434, 'errmsg' =>$hosts];

        if ($result['errno'] == 0) {
            parent::doing($workflow);
        }

        // 2、删除应用系统的权限（gaea权限）
        //
        // 3、注销vpn

        return $result;
    }
}
