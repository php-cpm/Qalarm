<?php
namespace App\Components\Workflow;

use App\Components\Utils\LogUtil;
use App\Components\Utils\MethodUtil;
use App\Components\JobDone\JobDone;

use app\Models\Common\Mail;
use App\Models\Gaea\Workflow;
use App\Models\Gaea\AdminUser;
use App\Models\Gaea\OpsScripts;
use App\Models\Gaea\OpsUserHostPerm;
use App\Models\Gaea\OpsHost;

use App\Jobs\ScriptExecResultJob;

class ServerPermHandler extends WorkflowHandler
{
    // done 只用做通知
    //
    public function doing($workflow)
    {/*{{{*/
        $attachment = json_decode($workflow->attachment, true);

        $hosts     = $attachment['hosts'];
        $username  = $attachment['username'];
        $group     = $attachment['group'];
        $exploiter = $attachment['exploiter'];

        $user = AdminUser::where('username', $username)->first();

        if ($exploiter == 1) {
            $passwd = MethodUtil::getUniqueId();
            $result = app('jobdone')->fetchShadowByPasswd($passwd);

            // 把密码添加到attachment中，用于完成时通知用户
            $attachment['passwd'] = $passwd;
            $workflow->attachment = json_encode($attachment);
            $workflow->save();

            if ($result['errno'] != 0) {
                return response()->clientError($result['errno'], $result['errmsg']);
            }

            $user->host_passwd_sha1   = sha1($passwd);
            $user->host_passwd_shadow = $result['errmsg'];
            $user->save();
        }

        $script = OpsScripts::where('owner', OpsScripts::OWNER_OPS)
            ->where('scriptdir', 'users')
            ->where('scriptname', 'addandroot.sh')
            ->first();

        $params['username']  = $username;
        $params['key']       = $user->host_passwd_shadow;
        if ($group == 'root') {
            $params['group'] = $group;
        }

        $jid = app('jobdone')->launchGaeaScriptExec($script, $hosts, $params); 

        // 插入异步队列
        $jobData = MethodUtil::assemblyJobData(['jid' => $jid]);
        $job = (new ScriptExecResultJob($jobData))->delay(1);
        $this->dispatch($job);

        parent::doing($workflow);
    }/*}}}*/

    public function done($workflow)
    {/*{{{*/
        $attachment = json_decode($workflow->attachment, true);
        $hosts    = $attachment['hosts'];
        $username = $attachment['username'];
        $group    = $attachment['group'];
        $exploiter= $attachment['exploiter'];
        if ($exploiter == 1) {
            $passwd   = $attachment['passwd'];
        }

        $user = AdminUser::where('username', $username)->first();

        // 入库，通知
        foreach ($hosts as $host) {
            $perm = new OpsUserHostPerm();
            {
                $opsHost = OpsHost::where('server_name', $host)->first();
                $perm->host_id = $opsHost->id;
                $perm->user_id = $user->id;
                $perm->group   = $group;
            }
            $perm->save();
        }

        parent::done($workflow);

        // 提醒修改密码
        if ($exploiter == 1) {
            $content = sprintf('系统检测到您是第一次申请线上机器权限，为您提供了默认密码为：%s, 如果需要修改请在Gaea上(/服务栏-其他服务-修改服务器密码)自助操作。', $passwd);
            parent::notice($username, $content);
        }
    }/*}}}*/
}
