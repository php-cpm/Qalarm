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


use App\Models\Gaea\MarketApp;
use App\Models\Gaea\AdminApp;
use App\Models\Gaea\Workflow;

class MyAppController extends Controller
{
    /**
     * fetchMyApps 获取个人的系统
     * @return Response
     */
    public function fetchMyApps(Request $request)
    {/*{{{*/
        $collection = AdminApp::where('admin_id', Constants::getAdminId())->get();

        $callee = 'export';
        $apps = $collection->map(function($item, $key) use ($callee) {
             return call_user_func([$item, $callee]);
        });

        if (empty($apps)) {
            $apps = [];
        }

        return response()->clientSuccess($apps);
    }/*}}}*/


    /**
     * @brief fetchAllApps 得到所有的应用列表和自己的开通状态
     * @param $request
     * @return 
     */
    public function fetchAllApps(Request $request)
    {/*{{{*/
        $collection = MarketApp::orderBy('order')->get();
        $callee = 'export';

        foreach ($collection as $item) {
            $raw = AdminApp::where('admin_id', Constants::getAdminId())
                ->whereIn('app_id', [$item->id])
                ->first();

            if ($raw != null) {
                $item->status = '已开';
            } else {
                $item->status = '未开';
            }

            $raw = Workflow::where('admin_id', Constants::getAdminId())
                ->where('type', Workflow::WF_MARKET_APP)
                ->where('status', '<>',  Workflow::WF_STATUS_REBACK)
                ->whereIn('buss_id', [$item->id])
                ->first();

            if ($raw != null && $item->status == '未开') {
                $item->status = '待开';
            }
        }
        
        $apps = $collection->map(function($item, $key) use ($callee) {
             return call_user_func([$item, $callee]);
        });

        return response()->clientSuccess($apps);
    }/*}}}*/
}
