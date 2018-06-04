<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Components\Utils\Paginator;
use App\Components\Utils\ErrorCodes;
use App\Models\Gaea\OpCouponDetail;
use App\Components\Utils\Constants;
use App\Components\Notice\Notice;
use App\Models\User;
use Log;
use DB;

use App\Components\Utils\LogUtil;

class CouponController extends Controller
{
    /**
     * @brief FetchCoupons
     * @Return
     */
    public function fetchCoupons(Request $request)
    {
        // \DB::listen(
        //     function ($sql, $bindings, $time) {
        //         \Log::info(__CLASS__.'->'.__FUNCTION__.'('.__LINE__.')'.': sqls -> '.$sql.': bindings -> '.json_encode($bindings));
        //     }
        // );
        $this->validate($request, [
             'buss_type' => 'required',
             'user_id' => 'required',
          ]);


        $query = OpCouponDetail::where('user_id', $request->input('user_id'));

        // buss_type = 0 表示全部业务类型优惠券
        $bussType       = $request->input('buss_type', 0);
        $bussTypeList   = explode('|', $bussType);
        if (!is_array($bussTypeList)) {
            $query->where('buss_type', 0);
        } else {
            $query->whereIn('buss_type', $bussTypeList);
        }
        // status == 0 表示所有
        $status = $request->input('status', 0);
        if ($status != 0) {
            $query->where('status', $status);
        }

        // 过滤掉超时失效的优惠券
        if ($status == OpCouponDetail::COUPON_DETAIL_STATUS_NOT_USE) {
            $query->where('valid_end', '>=', Carbon::now());
        }
        $query->orderBy('send_time', 'desc');

        $paginator = new Paginator($request);
        $coupons = $paginator->runQuery($query);

        return $this->responseList($paginator, $coupons);
    }

    public function fetchManualCoupons(Request $request)
    {
        $this->validate($request, [
             'send_reason_id' => 'required',
             'buss_type' => 'required',
             'city_id' => 'required',
          ]);

        $query = OpCouponDetail::where('id', '>', 0);

        $request->input('city_id', 0) != 0 ? $query->where('cityids', $request->input('city_id')) : '';
        $request->input('send_reason_id', 0) != 0 ? $query->where('send_reason_id', $request->input('send_reason_id')) : '';
        $request->input('buss_type', 0) != 0 ? $query->where('buss_type', $request->input('buss_type')) : '';
        $request->input('mobile', 0) != 0 ? $query->where('user_mobile', 'like', $request->input('mobile').'%') : '';

        $query->orderBy('send_time', 'desc');

        $paginator = new Paginator($request);
        $coupons = $paginator->runQuery($query);

        return $this->responseList($paginator, $coupons, 'exportManualCoupon');
    }

    /**
     * @brief fetchCouponById 根据id获取优惠券
     * @Param $request
     * @Return
     */
    public function fetchCouponById(Request $request)
    {
        $this->validate($request, [
             'coupon_id' => 'required',
        ]);

        $coupon = OpCouponDetail::where('id', $request->input('coupon_id'))->first();
        if (is_null($coupon)) {
            $data = [];
        } else {
            $data = $coupon->export();
        }

        return response()->clientSuccess($data);
    }

    /**
     * @brief exchangeCoupon 兑换优惠券
     * @Param $request
     * @Return
     */
    public function exchangeCoupon(Request $request)
    {
        $this->validate($request, [
             'coupon_code' => 'required',
             'user_id' => 'required',
          ]);

        $coupon = OpCouponDetail::where('coupon_code', $request->input('coupon_code'))
            ->where('user_id', 0)->first();
        if (is_null($coupon)) {
            return response()->clientError(ErrorCodes::ERR_COUPON_NOT_EXIST, '没有此优惠券');
        }

        // 优惠券过期
        if (($coupon->valid_end->timestamp - Carbon::now()->timestamp) < 0) {
            return response()->clientError(ErrorCodes::ERR_COUPON_EXPIRED, '优惠券已过期失效');
        }

        {
            $coupon->exchange_time = Carbon::now();
            $coupon->user_id = $request->input('user_id');
            $coupon->status = OpCouponDetail::COUPON_DETAIL_STATUS_NOT_USE;
        }
        $coupon->save();

        return response()->clientSuccess(['coupon_id' => $coupon->id]);
    }

    /**
     * @brief dispenseCoupon 发放优惠券
     * @Param $request
     * @Return
     */
    public function dispenseCoupon(Request $request)
    {
        $this->validate($request, [
            'money'          => 'required',
            'start_date'     => 'required',
            'end_date'       => 'required',
            'buss_type'      => 'required|in:'.implode(',', array_keys(Constants::$BUSS_TYPE)),
            'reason_id'      => 'required|in:'.implode(',', array_keys(Constants::$MANUAL_COUPON_TYPE)),
            'mobile'         => 'required',
            'notice_sms'     => 'required',
         ]);

        // FIXME 异步化
        $couponUsers    = array();
        $needSms        = $request->input('notice_sms');

        $couponType     = $request->input('coupon_type', Constants::SUB_SERVICE);
        if ($couponType != Constants::SUB_SERVICE) {
            $couponType = Constants::COUPON_TYPE_MANUAL;
        }

        // 如果user_id 不为空，则说明只有一个用户（此为程序接口调用)
        $userId = $request->input('user_id', '');
        if (empty($userId)) {
            $mobileArr = explode("\n", $request->input('mobile'));

            // 给所有的电话号码都设置默认的user_id和cityid
            foreach ($mobileArr as $m) {
                $couponUsers[$m] = array('userid' => 0, 'mobile' => $m, 'cityid' => '131');
            }

            // 设置user_id和cityid
            $users = User::whereIn('mobile', $mobileArr)
                         ->select('id', 'mobile', 'staycityid')
                         ->get();
            foreach ($users as $user) {
                $couponUsers[$user->mobile] = array('userid' => $user->id, 'mobile' => $user->mobile, 'cityid' => $user->staycityid);
            }
        } else {
            $user = User::where('id', $userId)->get()->first();
            $couponUsers[] = array('userid' => $userId, 'mobile' => $request->input('mobile'), 'cityid' => $user->staycityid);
        }

        // 发放的用户不存在
        if (count($couponUsers) == 0) {
            return response()->clientError(ErrorCodes::ERR_FAILURE, '用户不存在');
        }

        // 如果是手工发放类型，则使用remark字段存储优惠金额
        foreach ($couponUsers as $user) {
            $couponDetail = new OpCouponDetail();
            {
                $couponDetail->remark = $request->input('money');
                $couponDetail->coupon_type = $couponType;
                $couponDetail->buss_type = $request->input('buss_type');
                $couponDetail->status = OpCouponDetail::COUPON_DETAIL_STATUS_NOT_USE;
                $couponDetail->valid_begin = $request->input('start_date');
                $couponDetail->valid_end = $request->input('end_date');
                $couponDetail->send_time = $couponDetail->exchange_time = new Carbon();
                $couponDetail->user_mobile = $user['mobile'];
                $couponDetail->user_id = $user['userid'];
                $couponDetail->cityids = $user['cityid'];

                $couponDetail->send_reason_id = $request->input('reason_id');
                $couponDetail->send_reason = $request->input('reason', '');
                $couponDetail->admin_user_id = Constants::getAdminId();
                $couponDetail->admin_user_name = Constants::getAdminName();
            }

            $couponDetail->save();

            if ($needSms == '1') {
                #$smsContent     = sprintf('您好，您已收到一张金额%s元的优惠券，请及时登陆麦大大管家使用，点此访问 www.maidada.cn。', $couponDetail->remark);
                $smsContent     = sprintf('恭喜您获得一张%s元优惠券，关注微信号“麦大大管家”或下载 麦大大管家App http://www.maidada.cn/app/download.shtml 使用', $couponDetail->remark);
                app('notice')->sendSms($couponDetail->user_mobile, $smsContent, '15', Notice::NOTICE_CHANNEL, Notice::PUSH_APPID_MAGIC_CHEZHU);
                LogUtil::info('Coupon send success', ['coupon' => $couponDetail]);
            }
        }

        return response()->clientSuccess([]);
    }

    public function batchDispenseCoupon(Request $request)
    {
        $this->validate($request, [
            'data'     => 'required',
        ]);

        $data           = $request->input('data');
        if (is_null($data) || !is_array($data)) {
            return response()->clientError(ErrorCodes::ERR_FAILURE, '参数错误');
        }

        $couponList = null;

        foreach($data as $item) {
            if (is_null($item)) {
                 continue;
            }

            if (!isset($item['coupon_type'])) {
                continue;
            }

            $couponType = $item['coupon_type'];
            if ($couponType != Constants::SUB_SERVICE) {
                $couponType = Constants::COUPON_TYPE_MANUAL;
            }

            // 如果user_id 不为空，则说明只有一个用户（此为程序接口调用)
            $userId = isset($item['user_id']) ? $item['user_id'] : null;
            if (!isset($item['mobile'])) {
                continue;
            }
            $mobile = $item['mobile'];
            if (is_null($userId)) {
                $user = User::whereIn('mobile', $mobile)
                    ->select('id', 'mobile', 'staycityid')
                    ->first();
                $user = array('userid' => $user->id, 'mobile' => $user->mobile, 'cityid' => $user->staycityid);
            } else {
                $user = User::where('id', $userId)->get()->first();
                $user = array('userid' => $userId, 'mobile' => $item['mobile'], 'cityid' => $user->staycityid);
            }

            // 如果是手工发放类型，则使用remark字段存储优惠金额
            if (!isset($item['amount']) || !isset($item['money']) || !isset($item['buss_type']) || !isset($item['reason']) ||
                 !isset($item['start_date']) || !isset($item['end_date']) || !isset($item['reason_id'])) {
                continue;
            }

            try {
                DB::connection('gaea')->beginTransaction();

                $amount =  $item['amount'];
                for ($i = 0; $i < $amount; $i = $i + 1) {
                    $couponDetail = new OpCouponDetail();
                    {
                        $couponDetail->remark      = $item['money'];
                        $couponDetail->coupon_type = $couponType;
                        $couponDetail->buss_type   = $item['buss_type'];
                        $couponDetail->status      = OpCouponDetail::COUPON_DETAIL_STATUS_NOT_USE;
                        $couponDetail->valid_begin = $item['start_date'];
                        $couponDetail->valid_end   = $item['end_date'];
                        $couponDetail->send_time   = $couponDetail->exchange_time = new Carbon();
                        $couponDetail->user_mobile = $user['mobile'];
                        $couponDetail->user_id     = $user['userid'];
                        $couponDetail->cityids     = $user['cityid'];

                        $couponDetail->send_reason_id = $item['reason_id'];
                        $couponDetail->send_reason   = $item['reason'];
                        $couponDetail->admin_user_id = Constants::getAdminId();
                        $couponDetail->admin_user_name = Constants::getAdminName();
                    }
                    $couponDetail->save();
                    $one = $couponDetail->export();
                    $one['coupon_mdd_id']   = isset($item['coupon_mdd_id']) ? $item['coupon_mdd_id'] : 0;
                    $couponList[] = $one;
                }
                DB::connection('gaea')->commit();
            } catch (\Exception $e) {
                DB::connection('gaea')->rollBack();
                Log::info('优惠券发放失败', [
                    'file: ' => $e->getFile(),
                    'line: ' => $e->getLine()
                ]);
                return response()->clientError(ErrorCodes::ERR_FAILURE, '优惠券发放失败');
            }
        }

//        Log::info('[batchDispenseCoupon]', ['$couponList' => $couponList]);
        return response()->clientSuccess($couponList);
    }

    /**
     * @brief playCoupon 操作优惠券
     *  状态转移：
     *  1  --> 2  开始使用
     *  2  --> 3  使用完成
     *  1  --> 4  过期失效
     *  2  --> 1  退回
     * @Param $request
     * @Param $coupon_id
     * @Return
     */
    public function playCoupon(Request $request)
    {
        $this->validate($request, [
            'coupon_id' => 'required',
            'user_id' => 'required',
            'action' => 'required|in:use,done,reback',
          ]);

        $action = $request->input('action');
        $coupon = OpCouponDetail::where('id', $request->input('coupon_id'))
                                 ->where('user_id', $request->input('user_id'))
                                 ->first();
        if (is_null($coupon)) {
            return response()->clientError(ErrorCodes::ERR_COUPON_NOT_EXIST, 'can not find coupon');
        }

        $result = $coupon->statusThransfer($action);
        if ($result == true) {
            return response()->clientSuccess([]);
        } else {
            return response()->clientError(ErrorCodes::ERR_COUPON_STATUS_NOT_MATCH, 'status not valid');
        }
    }

    /**
     * @brief responseList 组装数据
     * @Param $paginator
     * @Param $collection
     * @Return
     */
    protected function responseList($paginator, $collection, $callee = 'export')
    {
        return response()->clientSuccess([
            'page' => $paginator->info($collection),
            'results' => $collection->map(function ($item, $key) use ($callee) {
                return call_user_func([$item, $callee]);
            }),
        ]);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function correlateUser(Request $request)
    {
        $this->validate($request, [
            'user_id'       => 'required|numeric',
            'user_mobile'   => 'required|numeric',
        ]);
        $user_id        = $request->input('user_id');
        $user_mobile    = $request->input('user_mobile');
        OpCouponDetail::where('user_id', 0)
            ->where('user_mobile', $user_mobile)->update(['user_id' => $user_id]);
        return response()->clientSuccess([]);
    }
}
