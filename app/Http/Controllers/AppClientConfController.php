<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Components\Utils\ErrorCodes;
use Illuminate\Support\Facades\Input;

use App\Models\Gaea\AppClientConf;
use App\Components\Utils\Constants;

use Redis;

use DB;

class AppClientConfController extends Controller
{

    function __construct()
    {

    }

    public function idnex(Request $request)
    {
        //TODO:暂时不做数据过滤,默认读取前20条记录
        $filterParams = Input::All();
        $appClientConf = AppClientConf::where([])
            ->orderBy('conf_state', 'desc')
            ->orderBy('id', 'desc')
            ->limit(100)->get();

        $ret['data'] = $appClientConf;
        return response()->clientSuccess($ret);
    }

    public function insert(Request $request)
    {
        $this->validate($request, [
            'app_id'          => 'required|in:magic,pinche',
            'app_name'        => 'required|in:车管家,顺风车',
            'version'         => 'required',
            'app_os'          => 'required|in:android,ios',
            'app_type'        => 'required|in:normal,test,beta',
            'app_type_name'   => 'required|in:正式版本,测试版本,灰度版本',
            'function_type'   => 'required|in:config,patch,update',
            'function_type_name'   => 'required|in:版本配置,补丁配置,强制更新配置',
            'conf_key'        => 'required',
            'conf_value'      => 'required'
        ]);


        $admin_user_id  = Constants::getAdminId();
        $admin_user_name = Constants::getAdminName();

        $appClientConf = new AppClientConf();
        {
            $appClientConf->app_id        = $request->input('app_id');
            $appClientConf->app_name      = $request->input('app_name');
            $appClientConf->app_os        = $request->input('app_os');
            $appClientConf->version       = $request->input('version');
            $appClientConf->app_type      = $request->input('app_type');
            $appClientConf->app_type_name = $request->input('app_type_name');
            $appClientConf->function_type = $request->input('function_type');
            $appClientConf->function_type_name = $request->input('function_type_name');
            $appClientConf->conf_key      = $request->input('conf_key');
            $appClientConf->conf_value    = $request->input('conf_value');
            $appClientConf->conf_state    = 0; //默认是不发布的状态
            $appClientConf->user_id       = $admin_user_id;
            $appClientConf->user_name     = $admin_user_name;
            $appClientConf->create_time   = date("Y-m-d H:i:s", time());
            $appClientConf->remark        = $request->input('remark');
        }

        if ($appClientConf->save()) {
            $ret['data'] = $appClientConf;
            return response()->clientSuccess($ret);
        } else {
            return response()->clientError([]);
        }
    }

    public function update(Request $request)
    {
        $this->validate($request, [
//            'id'          => 'required',
//            'conf_key'    => 'required',
//            'conf_value'  => 'required'
            'id'              => 'required',
            'app_id'          => 'required|in:magic,pinche',
            'app_name'        => 'required|in:车管家,顺风车',
            'version'         => 'required',
            'app_os'          => 'required|in:android,ios',
            'app_type'        => 'required|in:normal,test,beta',
            'app_type_name'   => 'required|in:正式版本,测试版本,灰度版本',
            'function_type'   => 'required|in:config,patch,update',
            'function_type_name'   => 'required|in:版本配置,补丁配置,强制更新配置',
            'conf_key'        => 'required',
            'conf_value'      => 'required'
        ]);
       
        $selectRowData = AppClientConf::where('id', '=', $request->input('id'))->first();
        if (isset($selectRowData)) {
            $selectRowData->app_id      = $request->input('app_id');
            $selectRowData->app_name    = $request->input('app_name');
            $selectRowData->app_os      = $request->input('app_os');
            $selectRowData->version     = $request->input('version');
            $selectRowData->app_type    = $request->input('app_type');
            $selectRowData->app_type_name = $request->input('app_type_name');
            $selectRowData->function_type = $request->input('function_type');
            $selectRowData->function_type_name = $request->input('function_type_name');
            $selectRowData->conf_key    = $request->input('conf_key');
            $selectRowData->conf_value  = $request->input('conf_value');
            if ($selectRowData->save()) {
                return response()->clientSuccess([]);
            } else {
                return response()->clientError([]);
            }
        } else {
            return response()->clientError([]);
        }
    }


    public function releaseDataState(Request $request)
    {
        $this->validate($request, [
            'id'          => 'required',
            'conf_state'  => 'required|in:0,1,2'
        ]);

        $id = $request->input('id');
        $confState = $request->input('conf_state');
        $selectRowData = AppClientConf::where('id', '=', $id)->first();

        if (!isset($selectRowData)) {
            return response()->clientError([]);
        }

        $admin_user_id  = Constants::getAdminId();
        $admin_user_name = Constants::getAdminName();

        DB::connection('gaea')->beginTransaction();
        try {

            $affectedRows = AppClientConf::where('app_id', '=', $selectRowData->app_id)
                ->where('version',          '=', $selectRowData->version)
                ->where('app_os',           '=', $selectRowData->app_os)
                ->where('app_type',         '=', $selectRowData->app_type)
                ->where('function_type',    '=', $selectRowData->function_type)
                ->where('conf_state',       '=', '1')
                ->update(['conf_state' => 2]);

            $selectRowData1 = AppClientConf::where('id', '=', $id)->first();
            $selectRowData1->conf_state = $confState;
            $selectRowData1->release_user_id = $admin_user_id;
            $selectRowData1->release_user_name = $admin_user_name;
            $selectRowData1->release_time = date("Y-m-d H:i:s", time());

            if ($selectRowData1->save()) {
                DB::connection('gaea')->commit();

                $key = $selectRowData1->conf_key;
                $ret = Redis::set($key, $selectRowData1->conf_value);
                $redisVal = Redis::get($key);

                $arr['redis_key'] = $key;
                $arr['redis_val'] = $redisVal;
                return response()->clientSuccess($arr);
            } else {
                return response()->clientError([]);
            }
        } catch(\Exception $e) {
            DB::connection('gaea')->rollback();
            throw $e;
        }
    }

}
