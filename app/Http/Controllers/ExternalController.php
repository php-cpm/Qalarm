<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Redis;
use Cookie;
use Exception;
use RuntimeException;
use App\Exceptions\ApiException;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Carbon\Carbon;

use App\Components\Utils\Paginator; 
use App\Components\Utils\ErrorCodes;
use App\Components\Utils\Constants;
use App\Components\Utils\MethodUtil;
use App\Components\Utils\LogUtil;
use App\Components\JobDone\JobDone;

use App\Models\RedisModel;
use App\Models\Gaea\AdminUser;
use App\Models\Gaea\MarketApp;
use App\Models\Gaea\AdminApp;
use App\Models\Gaea\OpsHost;
use App\Models\Gaea\OpsScripts;
use App\Models\Gaea\OpsScriptsExec;
use App\Models\Gaea\OpsUserHostPerm;

use App\Jobs\ScriptExecResultJob;

class ExternalController extends Controller
{
    /**
     * fetchMyApps 获取个人的系统
     * @return Response
     */
    public function fetchGaeaClientVersion(Request $request)
    {/*{{{*/
        $version = ['version' => env('GAEA_CLIENT_VERSION', '1.0')];
        return response()->clientSuccess($version);
    }/*}}}*/


    /**
     * @brief reportClientInfo 处理客户端上报数据
     * @param $request
     * @return []
     */
    public function reportClientInfo(Request $request)
    {/*{{{*/
        $this->validate($request, [
            'report_type'    => 'required'
        ]);

        $reportType = $request->input('report_type');

        // $sysplan|$cpu|$cpu_num|$memtotal|$kernal|$disk|$uptime|$mac|$VIRT
        if ($reportType == "HardWare") {
            list($sysVersion, $cpu, $cpuNum, $mem, $kernel, $disk, $uptime, $mac, $vm) = explode('|', $request->input('params', ''));
            if ($mem > 0) {
                $mem = $mem / 1024 ;
            }
            $hostname = $request->input('hostname');
            $ips = $request->input('ips');

            // 只要有主机名就可以工作
            if (empty($mac)) {
                $mac = $ips;
            }

            if (empty($mac)) {
                $mac = $hostname;
                // 说明ip也为空，需要赋值为主机名
                $ips = $hostname;
            }

            // 使用mac做vm的唯一标示，可能还有更好地方法，但是暂时想不到
            // FIXME
            $mac = trim($mac);
            if (empty($mac)) {
                LogUtil::error('mac null', ['params' => $request->input('params')]);
                return response()->clientSuccess([]);
            }
            $host = OpsHost::where('mac', $mac)->first();
            // 新机器
            if ($host == null) {
                $host = new OpsHost();
                $host->status = OpsHost::HOST_INIT;
                $oldMd5 = '';
            }

            $newMd5 = md5(join('', [$sysVersion, $cpu, $cpuNum, $mem, $kernel, $disk, $uptime, $mac, $vm, $hostname, $ips]));
            $oldMd5 = md5(join('', [
                $host->version,
                $host->cpu,
                $host->cpu_num,
                $host->memory,
                $host->kernel,
                $host->disk,
                $host->uptime,
                $host->mac,
                $host->vm,
                $host->server_name,
                $host->ip,
            ]));

            if ($newMd5 == $oldMd5) {
                return response()->clientSuccess([]);
            }
            $host->version    = $sysVersion;
            $host->cpu        = $cpu;
            $host->cpu_num    = $cpuNum;
            $host->memory     = $mem;
            $host->kernel     = $kernel;
            $host->disk       = $disk;
            $host->uptime     = $uptime;
            $host->mac        = $mac;
            $host->vm         = $vm;
            $host->server_name= $hostname;
            $host->ip         = $ips;

            $host->save();
            
            return response()->clientSuccess(['id' => $host->id]);
        }

        return response()->clientSuccess([]);
    }/*}}}*/


    /**
     * @brief fetchAllHostNames 
     * @param $request
     * @return ['init' => []]
     */
    public function fetchHostNames(Request $request)
    {/*{{{*/
        $this->validate($request, [
            'type'    =>  'required|in:'.join(',', OpsHost::getHostStatuses())
            ]);

        $ret = array();
        $type = $request->input('type');
        $query = OpsHost::orderBy('server_name', 'desc');
        if ($type != OpsHost::HOST_ALL) {
            $query->where('status', $type);
        }
        $hosts = $query->get();

        foreach ($hosts as $host) {
            if (!empty($host->ip)) {
                $ips = explode('|', $host->ip);
                foreach ($ips as $ip) {
                    $ret[$ip] = $host->server_name;
                }
            }
        }

        return response()->clientSuccess($ret);
    }/*}}}*/

    /**
     * @brief fetchSystemAuthStatus 查询用户是否有应用系统的使用权限
     * @param $request
     *
     * @return [true|false]
     */
    public function fetchSystemAuthStatus(Request $request)
    {/*{{{*/
        $this->validate($request, [
            'username'    =>  'required',
            'appname'     =>  'required'
        ]);

        $ret = ['authorization' => true]; 

        $user = AdminUser::where('username', $request->input('username'))->first();

        if ($user == null) {
            return response()->clientError(ErrorCodes::ERR_FAILURE, '用户不存在');
        }

        $app = MarketApp::where('name', $request->input('appname'))->first();
        if ($app == null) {
            return response()->clientError(ErrorCodes::ERR_FAILURE, '应用不存在');
        }

        $userApp = AdminApp::where('admin_id', $user->id)->where('app_id', $app->id)->first();
        if ($userApp == null) {
            $ret = ['authorization' => false]; 
        }

        return response()->clientSuccess($ret);
    }/*}}}*/
}
