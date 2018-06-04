<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Gaea\OpCoupon;
use App\Components\Utils\Paginator;

class AwardsController extends Controller
{

    /**
     * @brief fetchAwards 获取奖品
     * @Param $request
     * @Return  
     */
    public function fetchAwards(Request $request)
    {
        $this->validate($request, [
            'buss_type'     => 'required',
            'coupon_type'   => 'required',
            'city_id'       => 'required',
        ]);

        $query = OpCoupon::where('coupon_type', $request->input('coupon_type'))
                         ->where('buss_type', $request->input('buss_type'));
                         // ->whereIn('city_id', 
        //
        $paginator = new Paginator($request);  
        $awards = $paginator->runQuery($query);

        return $this->responseList($paginator, $awards);
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
