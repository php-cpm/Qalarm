<?php
/**
 * Created by PhpStorm.
 * User: weichen
 * Date: 15/9/25
 * Time: 下午3:21
 */

namespace App\Http\Controllers;

use Carbon\Carbon;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Components\Utils\Paginator;
use App\Components\Utils\ErrorCodes;

use Illuminate\Support\Facades\Input;

use App\Models\Gaea\CarNumbers;
use App\Models\Gaea\OpContent;

use App\Models\Gaea\AccountCheckSkip;
use App\Models\Gaea\AccountLicense;
use App\Models\Gaea\AccountCheckLicense;
use App\Components\Utils\Constants;

class AccountController extends Controller
{

    protected $accountCenterApi;

    function __construct()
    {/*{{{*/
        $this->accountCenterApi = app()->make('AccountCenterApiServer');
    }/*}}}*/


    /**
     * 账号信息列表
     * @param Request $request
     * @return mixed
     */
    public function accountList(Request $request)
    {/*{{{*/
        $arrSearch = array();
        $urlParams = Input::All();
        if (count($urlParams) > 0) {
            foreach ($urlParams as $key => $value) {
                if ($value != '') {
                    $arrSearch[$key] = trim($value);
                }
            }
        }

        $result = $this->accountCenterApi->accountList($arrSearch);
        if ($result['code'] == 0) {
            $ret = $this->formatResponseData($result);
            return response()->clientSuccess($ret);
        } else {
            return response()->clientError(ErrorCodes::ERR_FILTER_CURL, $result['code']);
        }
    }/*}}}*/


    /**
     * 账号信息详细
     * @return mixed
     */
    public function accountDetail()
    {/*{{{*/
        $urlParams = Input::All();
        if (count($urlParams) > 0) {
            foreach ($urlParams as $key => $value) {
                if ($value != '') {
                    $arrSearch[$key] = trim($value);
                }
            }
        }

        $result = $this->accountCenterApi->accountDetail($arrSearch);
        if ($result['code'] == 0) {
            $arr = $this->accountCenterApi->formatData($result['data'])[0];
            return response()->clientSuccess($arr);
        } else {
            return response()->clientError(ErrorCodes::ERR_FILTER_CURL, $result['code']);
        }
    }/*}}}*/


    /**
     * 账号信息实名认证
     * @return mixed
     */
    public function accountAuth()
    {/*{{{*/
        $urlParams = Input::All();
        if (count($urlParams) > 0) {
            foreach ($urlParams as $key => $value) {
                if ($value != '') {
                    $arrSearch[$key] = trim($value);
                }
            }
        }

        $result = $this->accountCenterApi->accountAuthList($arrSearch);
        if ($result['code'] == 0) {
            $ret = $this->formatResponseData($result);
            return response()->clientSuccess($ret);
        } else {
            return response()->clientError(ErrorCodes::ERR_FILTER_CURL, $result['code']);
        }
    }/*}}}*/


    /**
     * 更新账户  实名认证信息:更改真实姓名和身份证号
     * @param Request $request
     * @return mixed
     */
    public function accountAuthUpdate(Request $request)
    {/*{{{*/
        $urlParams = Input::All();
        $result = $this->accountCenterApi->accountAuthUpdate($urlParams);

        if ($result['code'] == 0) {
            return response()->clientSuccess([]);
        } else {
            return response()->clientError(ErrorCodes::ERR_FILTER_CURL, $result['code']);
        }
    }/*}}}*/


    //    /**
    //     * 获取车照或是驾驶证行驶证 状态 或 查询
    //     * @return mixed
    //     */
    //    function accountNotCheckCarImgLicense(){
    //
    //        $url_params = Input::All();
    //
    //        $result = $this->accountCenterApi->accountNotCheckCarImgLicense($url_params);
    //        if ($result['code'] == 0) {
    //            //找到跳过数据
    //            $accountSkips = AccountCheckSkip::all();
    ////            for($i=0; $i <count($result["data"]); $i++) {
    ////                print($result["data"][$i]["basic"]["id"].'<br/>');
    ////            }
    //            for($i=0; $i <count($result["data"]); $i++){
    //                $dataItem = $result["data"][$i];
    //                foreach($accountSkips as $item){
    //                    $account_id = $item->account_id;
    ////                print($dataItem["basic"]["id"].'  ====== '.$account_id.'<br/>');
    //                    if($account_id == $dataItem["basic"]["id"]){
    //                        //$result["data"][$i]["basic"]["ignore"] = "yes";
    //                        //删除数组中已经跳过的数据
    //                        //unset($result["data"][$i]);
    //                        array_splice($result["data"], $i, 1);
    //                        //echo $account_id;
    //                    }else{
    //                        //$result["data"][$i]["basic"]["ignore"] = "no";
    //                    }
    //                }
    //            }
    //
    ////            print('================== ========<br/>');
    ////            for($i=0; $i <count($result["data"]); $i++) {
    ////                print($result["data"][$i]["basic"]["id"].'<br/>');
    ////            }
    //            $ret = $this->formatResponseData($result);
    //            return response()->clientSuccess($ret);
    //        } else {
    //            return response()->clientError(ErrorCodes::ERR_FILTER_CURL, $result['code']);
    //        }
    //    }

    /**
     * 更改用户基本信息接口: 头像审核状态
     * @return mixed
     */
    public function accountBasicUpdate(Request $request)
    {/*{{{*/
        $urlParams = Input::All();
        if (array_key_exists('voice_del_reason', $urlParams)){
            $adminUserId    = Constants::getAdminId();
            $adminUserName  = Constants::getAdminName();
            $urlParams['op_info'] =  [
                "service"   => "gaea",
                "oper_id"   => $adminUserId,
                "oper_name" => $adminUserName,
                "remark"    => $request->input('voice_del_reason')
            ];
            $urlParams['voice_intro'] = [
                "url"   => "",
                "length" => 0
            ];
            unset($urlParams['voice_del_reason']);
        }
        $result = $this->accountCenterApi->accountBasicUpdate($urlParams);
        if ($result['code'] == 0) {
            return response()->clientSuccess([]);
        } else {
            return response()->clientError(ErrorCodes::ERR_FILTER_CURL, $result['code']);
        }
    }/*}}}*/

    public function accountHeadImgCheckList()
    {/*{{{*/
        $urlParams = Input::All();
        $result = $this->accountCenterApi->accountHeadImgCheckList($urlParams);
        if ($result['code'] == 0) {
            $ret = $this->formatResponseData($result);
            return response()->clientSuccess($ret);
        } else {
            return response()->clientError(ErrorCodes::ERR_FILTER_CURL, $result['code']);
        }
    }/*}}}*/

    /**
     * 证件,车照 审核列表
     * @return mixed
     */
    public function accountLicenseCheckList()
    {/*{{{*/
        $urlParams = Input::All();
        $result = $this->accountCenterApi->accountHeadImgCheckList($urlParams);
        if ($result['code'] == 0) {
            $ret = $this->formatResponseData($result);
            return response()->clientSuccess($ret);
        } else {
            return response()->clientError(ErrorCodes::ERR_FILTER_CURL, $result['code']);
        }
    }/*}}}*/

    /**
     * 驾驶信息修改
     * @return mixed
     */
    public function driverUpdate()
    {/*{{{*/
        $urlParams = Input::All();
        $result = $this->accountCenterApi->driverUpdate($urlParams);
        if ($result['code'] == 0) {
            return response()->clientSuccess([]);
        } else {
            return response()->clientError(ErrorCodes::ERR_FILTER_CURL, $result['code']);
        }
    }/*}}}*/

    /**
     * 车辆信息修改
     * @return mixed
     */
    public function carUpdate()
    {/*{{{*/
        $urlParams = Input::All();
        $result = $this->accountCenterApi->carUpdate($urlParams);
        if ($result['code'] == 0) {
            return response()->clientSuccess([]);
        } else {
            return response()->clientError(ErrorCodes::ERR_FILTER_CURL, $result['code']);
        }
    }/*}}}*/

    public function carBrand()
    {/*{{{*/
        $result = $this->accountCenterApi->carBrandList();
        $ret = array();
        $ret[] = array('id' => -1, 'name' => '全部');
        foreach ($result['data'] as $brand) {
            $ret[] = array('id' => $brand['brand_id'], 'name' => $brand['brand']);
        }
        return response()->clientSuccess($ret);
    }/*}}}*/

    /**
     * 操作车主证件号信息
     * @return
     */
    public function carNumbers(Request $request)
    {/*{{{*/
        $this->validate($request, [
            'action'              => 'required',
            'userid'              => 'required',
            'car_number'          => 'required_if:action,update',
            'car_identity_number' => 'required_if:action,update',
            'engine_number'       => 'required_if:action,update',
            'registered_at'       => 'required_if:action,update',
        ]);

        $carNumbers = CarNumbers::where('userid', $request->input('userid'))->first();
        if ($request->input('action') == 'get' && $carNumbers != null) {
            $data = [
                'car_number'          => $carNumbers->car_number,
                'car_identity_number' => $carNumbers->car_identity_number,
                'engine_number'       => (int)$carNumbers->engine_number,
            ];
            if (!is_null($carNumbers->registered_at)) {
                $data['registered_at'] = with(new Carbon())->timestamp($carNumbers->registered_at->timestamp)->format('Y-m-d');
            } else {
                $data['registered_at'] = '';
            }

            return response()->clientSuccess($data);
        } else {
            if ($carNumbers == null) $carNumbers = new CarNumbers();
            {
                $carNumbers->userid              = $request->input('userid');
                $carNumbers->car_number          = $request->input('car_number');
                $carNumbers->car_identity_number = $request->input('car_identity_number');
                $carNumbers->engine_number       = $request->input('engine_number');
                $carNumbers->registered_at       = $request->input('registered_at');
                $carNumbers->admin_user_id       = Constants::getAdminId();
                $carNumbers->admin_user_name     = Constants::getAdminName();
            }
            $carNumbers->save();
            return response()->clientSuccess();
        }
    }/*}}}*/

    /**
     * 格式化返回数据
     * @param array $result
     * @return mixed
     */
    private function formatResponseData($result = [])
    {/*{{{*/
        $ret['result'] = $this->accountCenterApi->formatData($result['data']);
        $ret['page'] = array(
            'size'     => $result['page']['size'],
            'total'    => $result['page']['total'],
            'count'    => $result['page']['count'],
            'has_more' => $result['page']['has_more'],
            //'from' =>   $result['page']['from'],
            //'to' =>   $result['page']['to'],
            'index'    => $result['page']['index']
        );
        return $ret;
    }/*}}}*/

    /**
     * 添加跳过审核记录
     * @param array $result
     * @return mixed
     */


    public function saveAccountCheckSkip(Request $request)
    {/*{{{*/
        $this->validate($request, [
            'account_id'       => 'required',
            'account_mobile'   => 'required'
        ]);

        $admin_user_id = Constants::getAdminId();
        $admin_user_name = Constants::getAdminName();

        $accountCheckSkip = new AccountCheckSkip();
        {
            $accountCheckSkip->account_id = $request->input('account_id');
            $accountCheckSkip->account_mobile = $request->input('account_mobile');
            $accountCheckSkip->user_id = $admin_user_id;
            $accountCheckSkip->user_name = $admin_user_name;
        }
        $accountCheckSkip->save();
        return response()->clientSuccess([]);
    }/*}}}*/

    //=========== 开始审核接口 ===========
    function AccountStartCheckData()
    {/*{{{*/

        $admin_user_id  = Constants::getAdminId();
        $admin_user_name = Constants::getAdminName();

        $count = AccountLicense::where('is_lock', '<>', 1)->count();
        //所有审核用户
        $accountLicenseAll = AccountLicense::all();
        $minLicenses = 1000;

        if ($count <= $minLicenses) {
            //todo:当数据量 < $minLicenses 插入数据;
            //todo:插入的数据;不能为第一次请求的数据;

            //目前数据量,一次性全部灌入
            $params = array('car_license_image_state' => 1);
            $result = $this->accountCenterApi->accountNotCheckCarImgLicense($params); //发请求

            for ($i = 0; $i < count($result["data"]); $i++) {
                $dataItem = $result["data"][$i];
                $accLicense = new AccountLicense();
                {
                    $accLicense->account_id = $dataItem['basic']['id'];
                    $accLicense->account_mobile = $dataItem['basic']['mobile'];
                    $accLicense->account_name = $dataItem['basic']['name'];
                    $accLicense->license_data = json_encode($dataItem);
                    $accLicense->is_lock = 0;
                    $accLicense->begin_lock_time = 0;
                    $accLicense->end_lock_time = 0;
                    $accLicense->user_id = '';
                    $accLicense->user_name = '';
                }

                $isnotexit = true;
                foreach ($accountLicenseAll as $item) {
                    if ($item['account_id'] == $dataItem['basic']['id']) {
                        $isnotexit = false;
                        break;
                    }
                }
                if ($isnotexit) {
                    $accLicense->save();
                }
            }
        }

        //查找当前用户跳过的ID
        $ackip = AccountCheckSkip::where('user_id', '=', $admin_user_id)
                ->select('account_id')->get();

        //首先查找但前用户锁定的数据
        $accountLicense = AccountLicense::where('is_lock', '=', 1)
            ->where('user_id', '=', $admin_user_id)
            ->whereNotIn('account_id', $ackip)
            ->orderBy('account_id', 'asc')->first();

        //没有用户锁定的数据;那就随机来一条数据
        if (!isset($accountLicense)) {
            //echo json_encode($affectedRow);
            $accountLicense = AccountLicense::where('is_lock', '<>', 1)
                ->orderBy('account_id', 'asc')->first();
        }

        if (isset($accountLicense)) {
            //查找到一个记录,然后修改状态
            //取出数据;修改当前数据状态
            $accountLicense->is_lock = 1;
            $accountLicense->user_id = $admin_user_id;
            $accountLicense->user_name = $admin_user_name;
            $accountLicense->save();

            $formArr = array(json_decode($accountLicense['license_data'], true));
            $ret['result'] = $this->accountCenterApi->formatData($formArr);
            return response()->clientSuccess($ret);
        } else {
            return response()->clientSuccess([]);
        }
    }/*}}}*/

    function AccountCheckLicense(Request $request)
    {/*{{{*/

        $admin_user_id  = Constants::getAdminId(); 
        $admin_user_name= Constants::getAdminName();

        $this->validate($request, [
            'account_id'        => 'required',
            'account_mobile'    => 'required',
            'check_state'       => 'required',
            //            'user_id'               => 'required',
            //            'user_name'             => 'required'
        ]);

        $accountId = $request->input('account_id');
        //修改Ucenter数据,同时删除gaea db 内的此条记录
        $accCheckLicense = new AccountCheckLicense();
        {
            $accCheckLicense->account_id = $accountId;//$request->input('account_id');
            $accCheckLicense->account_mobile = $request->input('account_mobile');
            $accCheckLicense->account_name = $request->input('account_name');
            $accCheckLicense->check_time = date("Y-m-d H:i:s", time());
            $accCheckLicense->check_state = $request->input('check_state');
            $accCheckLicense->user_id = $admin_user_id;
            $accCheckLicense->user_name = $admin_user_name;
        }
        $accCheckLicense->save();

        //查找到审核的数据;删除数据;根据 Ucenter 的id删除数据;不是gaea的userid
        $affectedRow = AccountLicense::where('account_id', '=', $accountId)->delete();
        return response()->clientSuccess([]);
    }/*}}}*/

    public function photos(Request $request)
    {/*{{{*/
        $arrSearch = array();
        $urlParams = Input::All();
        if (count($urlParams) > 0) {
            foreach ($urlParams as $key => $value) {
                if ($value != '') {
                    $arrSearch[$key] = trim($value);
                }
            }
        }

        $result = $this->accountCenterApi->photos($arrSearch);
        if ($result['code'] == 0) {
            return response()->clientSuccess($result);
        } else {
            return response()->clientError(ErrorCodes::ERR_FILTER_CURL, $result['code']);
        }
    }/*}}}*/

    public function photosDel(Request $request)
    {/*{{{*/
        $this->validate($request, [
            'user_id'       => 'required',
            'photos'        => 'required',
            'reason'        => 'required'
        ]);

        $adminUserId    = Constants::getAdminId();
        $adminUserName  = Constants::getAdminName();

        $arr = [
            "user_id"   =>  $request->input('user_id'),
            "photos"    =>  [$request->input('photos')],
            "op_info"   =>  [
                "service"   => "gaea",
                "oper_id"   => $adminUserId,
                "oper_name" => $adminUserName,
                "remark"    => $request->input('reason')
            ]
        ];

        $result = $this->accountCenterApi->photoDel($arr);
        if ($result['code'] == 0) {
            return response()->clientSuccess($result);
        } else {
            return response()->clientError(ErrorCodes::ERR_FILTER_CURL, $result['code']);
        }
    }/*}}}*/

    public function photosDelLogs(Request $request)
    {/*{{{*/
        $this->validate($request, [
            'user_id'       => 'required'
        ]);

        $arr = [
            "user_id" => $request->input('user_id'),
            "service" => "gaea",
            "obj"     =>  "album"
        ];

        $result = $this->accountCenterApi->photosDelLogs($arr);
        if ($result['code'] == 0) {
            return response()->clientSuccess($result);
        } else {
            return response()->clientError(ErrorCodes::ERR_FILTER_CURL, $result['code']);
        }
    }/*}}}*/

    public function voiceDelLogs(Request $request)
    {/*{{{*/
        $this->validate($request, [
            'user_id'       => 'required'
        ]);

        $arr = [
            "user_id" => $request->input('user_id'),
            "service" => "gaea",
            "obj"     =>  "voice_intro"
        ];

        $result = $this->accountCenterApi->voiceDelLogs($arr);
        if ($result['code'] == 0) {
            return response()->clientSuccess($result);
        } else {
            return response()->clientError(ErrorCodes::ERR_FILTER_CURL, $result['code']);
        }
    }/*}}}*/
}
