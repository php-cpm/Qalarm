<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Cookie;
use Exception;
use RuntimeException;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Carbon\Carbon;

use App\Components\Utils\Paginator; 
use App\Components\Utils\ErrorCodes;
use App\Components\Utils\Constants;

use App\Models\Gaea\OpsHost;
use App\Models\Gaea\MarketApp;
use App\Models\Gaea\AdminApp;
use App\Models\Gaea\AdminUser;
use App\Models\Gaea\Workflow;
use App\Models\Gaea\OpsUserHostPerm;

class WorkflowController extends Controller
{
    /**
     * applyServerPerm 申请服务器权限
     * @return Response
     */
    public function applyServerPerm(Request $request)
    {/*{{{*/
        $this->validate($request, [
            'group'    => 'required|in:normal,ttyc,root',
            'ips'      => 'required'
        ]);

        $groupPriority = ['normal' => 1, 'ttyc' => 2, 'root' => 3];

        $ips = explode("\n", $request->input('ips'));
        $group = $request->input('group');

        $invalidIps = [];
        $validHostnames = [];
        $existPerms = [];
        foreach ($ips as $ip) {
            // 如果是主机名
            if (preg_match("/[a-z]$/i", $ip)) {
                $host = OpsHost::where('server_name', $ip)->where('status', OpsHost::HOST_UP)->first();
                if (is_null($host)) $invalidIps[] = $ip;

            } else {
                $host = OpsHost::where('ip', $ip)->where('status', OpsHost::HOST_UP)->first();
                if (is_null($host)) $invalidIps[] = $ip;
            }

            if (!is_null($host)) {
                $validHostnames[] = $host->server_name;
                $perm = OpsUserHostPerm::where('host_id', $host->id)
                                       ->where('user_id', Constants::getAdminId())
                                       ->first();
                if (!is_null($perm)) {
                    // 如果申请不高于目前权限，则不用申请
                    if ($groupPriority[$group] <= $groupPriority[$perm->group]) {
                        $existPerms[] = ['host' => $host->server_name, 'group' => $perm->group];
                    }
                }
            }
        }

        // 非平台机器
        if (count($invalidIps) != 0) {
            return response()->clientError(ErrorCodes::ERR_PARAM_ERROR, '不存在这些机器: '.join(',', $invalidIps));
        }

        // 已拥有的权限
        if (count($existPerms) != 0) {
            $errmsg = '您已经拥有的权限:';
            foreach ($existPerms as $perm) {
                $errmsg .= $perm['host'] .'的'.$perm['group'].' 权限';
            }

            return response()->clientError(ErrorCodes::ERR_PARAM_ERROR, $errmsg);
        }
        
        $validHostnames = array_unique($validHostnames);

        $user = AdminUser::where('username', Constants::getAdminAccount())->first();
        $exploiter = 0;
        // 只要下面任意字段为空，说明此用户之前没有机器权限，任意生产一个key，之后提示用户修改密码
        if (empty($user->host_passwd_sha1) || empty($user->host_passwd_shadow)) {
            $exploiter = 1;
        }

        $workflow = Workflow::generateWorkflow(Workflow::WF_OP_DAILY_MACHINE_PERMIT,
            'hosts:'.join(',', $validHostnames).',group:'.$group,
            [
                'hosts'      => $validHostnames, 
                'group'      => $group, 
                'username'   => $user->username,
                'exploiter'  => $exploiter
            ]
        );

        return response()->clientSuccess(['id'=>$workflow->id]);
    }/*}}}*/

    public function applyVpn(Request $request)
    {/*{{{*/
        $user = Constants::getAdminAccount();

        $workflow = Workflow::generateWorkflow(Workflow::WF_OP_DAILY_APPLY_VPN,
            $user,
            ['user' => $user]
        );

        return response()->clientSuccess(['id'=>$workflow->id]);
    }/*}}}*/
    
    public function recoverPermission(Request $request)
    {/*{{{*/
        $this->validate($request, [
            'recover_username'     => 'required'
        ]);

        $recoverUsername = $request->input('recover_username');

        $user = AdminUser::where('username', $recoverUsername)->first();

        if (is_null($user)) {
            return response()->clientError(ErrorCodes::ERR_FAILURE, '不存在用户：'.$recoverUsername);
        }

        $workflow = Workflow::generateWorkflow(Workflow::WF_OP_DAILY_RECOVER_PERMISSION,
            $recoverUsername,
            ['recover_username' => $recoverUsername]
        );

        return response()->clientSuccess(['id'=>$workflow->id]);
    }/*}}}*/
    
    public function applyApp(Request $request)
    {/*{{{*/
        $this->validate($request, [
            'app_id'    => 'required',
        ]);

        $appId = $request->input('app_id');
        $app = MarketApp::where('id', $appId)->first();
        $workflow = Workflow::generateWorkflow(Workflow::WF_MARKET_APP,
            $app->name,
            ['appid' => $appId],
            $appId
        );

        return response()->clientSuccess(['id' => $workflow->id]);
    }/*}}}*/
    
    public function fetchWorkflows(Request $request)
    {/*{{{*/
        $typeIds = $request->input('type', '');
        // all 全部， one 自己的流程
        $scope = $request->input('scope', 'all');

        $query = Workflow::orderBy('created_at', 'desc');

        if ($scope != 'all')  {
            $query = $query->where('admin_id', Constants::getAdminId());
        }

        if (!empty($typeIds)) {
            $types = explode(',', $typeIds);
            $query = $query->whereIn('type', $types);
        }

        $collection = $query->get();
        
        $paginator = new Paginator($request);

        return $this->responseList($paginator, $collection);
    }/*}}}*/

    public function fetchJobWorkflows(Request $request)
    {/*{{{*/
        $collection = Workflow::where('alloc_id', Constants::getAdminId())
            ->where('status', Workflow::WF_STATUS_WAIT)
            ->get();
        
        $paginator = new Paginator($request);

        return $this->responseList($paginator, $collection);
    }/*}}}*/

    public function workflowStatusTransfer(Request $request)
    {/*{{{*/
        $this->validate($request, [
            'id'    => 'required',
            'action' => 'required|in:pass,done,fail,reback,retry',
        ]);

        $job = Workflow::where('id', $request->input('id'))->first();
        $result = $job->statusThransfer($request->input('action'), $output);
        if ($result) {
            return response()->clientSuccess([], '操作成功');
        } else {
            return response()->clientError($output['errno'], $output['errmsg']);
        }
    }/*}}}*/
    
    protected function responseList($paginator, $collection, $callee='export')
    {/*{{{*/
        return response()->clientSuccess([
            'page'     => $paginator->info($collection),
            'results'  => $collection->map(function($item, $key) use ($callee) {
                return call_user_func([$item, $callee]);
            }),
        ]);
    }/*}}}*/

}
