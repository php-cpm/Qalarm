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
use App\Models\Gaea\OpsHost;
use App\Models\Gaea\OpsScripts;
use App\Models\Gaea\OpsScriptsExec;
use App\Models\Gaea\OpsUserHostPerm;

use App\Jobs\ScriptExecResultJob;

class HostController extends Controller
{
    public function changeHostPasswd(Request $request)
    {/*{{{*/
        $this->validate($request, [
            'captcha'       => 'required',
            'new_shadow'    => 'required',
            'passwd'        => 'required',
            'new_slice'     => 'required'
        ]);

        $user = AdminUser::where('username', Constants::getAdminAccount())->first();


        $mobile = $user->mobile;
        $key = RedisModel::GAEA_CAPTCHA_REDIS_KEY_PREFIX . $mobile;
        $captcha = Redis::get($key);

        if ($captcha == null || $captcha != $request->input('captcha')) {
            return response()->clientError(ErrorCodes::ERR_EMPTY_PARAM, '无效验证码');
        }

        // 与原密码一样
        if ($request->input('new_shadow') == $user->host_passwd_sha1) {
            return response()->clientError(ErrorCodes::ERR_EMPTY_PARAM, '不能与原密码相同');
        }

        $magicPasswd = $request->input('passwd');
        $passwd = base64_decode($magicPasswd);
        $passwd = str_replace("\000", '', $passwd);

        $newSlice = $user->host_passwd_slice;
        if (!empty($newSlice)) {
            $slices  = explode('|', $newSlice);
            foreach ($slices as $s) {
                if (strpos($passwd, $s) !== false) {
                    return response()->clientError(ErrorCodes::ERR_EMPTY_PARAM, '与原密码不能太相似');
                }
            }
        }

        DB::connection('gaea')->beginTransaction(); 
        try {
            $user->host_passwd_sha1   = $request->input('new_shadow', '');
            $result = app('jobdone')->fetchShadowByPasswd($passwd);
            if ($result['errno'] != 0) {
                return response()->clientError($result['errno'], $result['errmsg']);
            }

            $user->host_passwd_shadow =  $result['errmsg'];
            $user->host_passwd_slice  = $request->input('new_slice');
            $user->save();

            // 得到当前用户的所有机器列表 (去掉已经下线的机器)
            $rawHosts = OpsUserHostPerm::where('user_id', Constants::getAdminId())->get();

            $hosts = [];
            foreach ($rawHosts as $userHost) {
                if ($userHost->host->status == OpsHost::HOST_UP) {
                    $hosts[] = $userHost->host->server_name;
                }
            }

            // 没有机器
            if (count($hosts) == 0) {
                throw new ApiException('您目前没有任何线上机器权限');
            }

            // 生成刷新任务job
            $script = OpsScripts::where('owner', OpsScripts::OWNER_OPS)
                ->where('scriptdir', 'users')
                ->where('scriptname', 'rekey.sh')
                ->first();

            // 处理参数
            $params['username'] = Constants::getAdminAccount();
            $params['key']      = $user->host_passwd_shadow;

            $jid = app('jobdone')->launchGaeaScriptExec($script, $hosts, $params);

            DB::connection('gaea')->commit();     

            $jobData = MethodUtil::assemblyJobData(['jid' => $jid]);
            $job = (new ScriptExecResultJob($jobData))->delay(1);
            $this->dispatch($job);

        } catch(\Exception $e) { 
            DB::connection('gaea')->rollback(); 
            throw $e;
        }

        return response()->clientSuccess([$passwd]);
    }/*}}}*/

    public function fetchHosts(Request $request)
    {/*{{{*/
        $query = OpsHost::orderBy('server_name', 'asc');
        $query->where('status', '<>', OpsHost::HOST_DESTORY);

        $keyword = $request->input('keyword');

        if (!empty($keyword)) {
            $query->where('server_name', 'LIKE', "%$keyword%");
            $query->Orwhere('ip', 'LIKE', "%$keyword%");
        }

        $paginator = new Paginator($request);
        $scripts   = $paginator->runQuery($query);

        return $this->responseList($paginator, $scripts);
    }/*}}}*/

    public function updateHost(Request $request)
    {/*{{{*/
        $this->validate($request, [
            'action'   => 'required|in:up,down,destory',
            'id'       => 'required'
        ]);

        $action = $request->input('action');
        $id     = $request->input('id');

        $host = OpsHost::where('id', $id)->first();
        if (is_null($host)) {
            return response()->clientError(ErrorCodes::ERR_FAILURE, '主机不存在');
        }

        if ($action == 'up') {
            $host->status = OpsHost::HOST_UP;
        }

        if ($action == 'down') {
            $host->status = OpsHost::HOST_DOWN;
        }

        if ($action == 'destory') {
            $host->status = OpsHost::HOST_DESTORY;
        }

        $host->save();

        return response()->clientSuccess([]);
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
