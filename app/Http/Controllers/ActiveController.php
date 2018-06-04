<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Carbon\Carbon;

use App\Components\Utils\Paginator; 
use App\Components\Utils\ErrorCodes;
use App\Models\OpActive;

class ActiveController extends Controller
{

    /**
     * @brief fetchActives  获取active列表
     * @Param $request
     * @Return  
     */
    public function fetchActives(Request $request)
    {
        $query = OpActive::where('id', '!=', 0);
        if (!empty($request->input('active_type'))) {
            $query = OpActive::where('active_type', $request->input('active_type'));
        }

        $paginator = new Paginator($request);
        $collection = $paginator->runQuery($query);

        return $this->responseList($paginator, $collection);
    }


    /**
     * @brief responseList 组装数据
     * @Param $paginator
     * @Param $collection
     * @Return  
     */
    protected function responseList($paginator, $collection, $callee='export')
    {
        return response()->clientSuccess([
            'page'     => $paginator->info($collection),
            'results'  => $collection->map(function($item, $key) use ($callee) {
                return call_user_func([$item, $callee]);
            }),
        ]);
    }
}
