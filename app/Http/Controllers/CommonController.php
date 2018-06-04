<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Redis;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Components\Utils\Constants;

use App\Models\City;
use App\Models\CarModel;
use App\Models\Gaea\AdminUser;
use App\Models\Gaea\CiProject;
use App\Models\Gaea\CiProjectMember;

use App\Models\RedisModel;

class CommonController extends Controller
{

    const REALTIME_SERVICE_REDIS_KEY='gaea_realtime_service_addr';

    /**
     * @brief fetchCouponTypes 活动优惠券的所有类型
     * @Param $request
     * @Return  
     */
    public function fetchCouponTypes(Request $request)
    {/*{{{*/
        $types = array();
        $types = array();
        if (empty($request->input('type'))) {
            $types[] = array('id'=>0, 'name'=>'全部');
        }
        foreach (Constants::$COUPON_TYPE as $type => $info) {
            $types[] = array('id'=>$type, 'name'=>$info['mname'].'-'.$info['sname']);
        }

        return response()->clientSuccess($types);
    }/*}}}*/
    
    public function fetchManualCouponTypes(Request $request)
    {/*{{{*/
        $types = array();
        $types = array();
        if (empty($request->input('type'))) {
            $types[] = array('id'=>0, 'name'=>'全部');
        }
        foreach (Constants::$MANUAL_COUPON_TYPE as $type => $info) {
            $types[] = array('id'=>$type, 'name'=>$info['mname'].'-'.$info['sname']);
        }

        return response()->clientSuccess($types);
    }/*}}}*/
    
    public function fetchbussTypes(Request $request)
    {/*{{{*/
        $types = array();
        $types = array();
        if (empty($request->input('type'))) {
            $types[] = array('id'=>0, 'name'=>'全部');
        }
        foreach (Constants::$BUSS_TYPE as $type => $info) {
            $types[] = array('id'=>$type, 'name'=>$info['mname'].'-'.$info['sname']);
        }

        return response()->clientSuccess($types);
    }/*}}}*/

    public function fetchCitys(Request $request)
    {/*{{{*/
        $types = array();
        if (empty($request->input('type'))) {
            $types[] = array('id'=>0, 'name'=>'全部');
        }
        
        $citys = City::all();
        foreach ($citys as $city) {
            $types[] = array('id'=>$city->mapcode, 'name'=>$city->name);
        }

        return response()->clientSuccess($types);
    }/*}}}*/

    public function fetchCarModels(Request $request)
    {/*{{{*/
        $types = array();
        if (empty($request->input('type'))) {
            $types[] = array('id'=>0, 'name'=>'全部');
        }
        
        $cars = CarModel::orderBy('id', 'asc')->get();
        foreach ($cars as $car) {
            $types[] = array('id'=>$car->id, 'name'=>$car->model);
        }

        return response()->clientSuccess($types);
    }/*}}}*/

    /**
     * @brief userTypes 用户类型
     * @Param $request
     * @Return
     */
    public function userTypes(Request $request)
    {/*{{{*/
        $types[] = array('id' => 0, 'name' => '全部');
        foreach (Constants::$USER_TYPE as $type => $info) {
            $types[] = array('id' => $info['id'], 'name' => $info['name']);
        }
        return response()->clientSuccess($types);
    }/*}}}*/

    /**
      * @brief fetchPushJumpTypes 
      * @Return  
     */
    public function fetchPushJumpTypes()
    {/*{{{*/
        $types = array();
        $types[] = array('id'=>'ttyongche:///home/passenger',                           'name'=>'乘客首页');
        $types[] = array('id'=>'ttyongche:///home/driver',                              'name'=>'车主首页');
        $types[] = array('id'=>'ttyongche:///app/user_info',                            'name'=>'个人信息页');
        $types[] = array('id'=>'ttyongche:///app/user_auth',                            'name'=>'实名认证页');
        $types[] = array('id'=>'ttyongche:///app/car_image',                            'name'=>'车辆照片页');
        $types[] = array('id'=>'ttyongche:///app/upload_headimg',                       'name'=>'上传头像页');
        $types[] = array('id'=>'ttyongche:///app/user_coupon',                          'name'=>'我的优惠券页');
        $types[] = array('id'=>'ttyongche:///passenger/call?type=gowork',               'name'=>'乘客叫车(上班)');
        $types[] = array('id'=>'ttyongche:///passenger/call?type=gohome',               'name'=>'乘客叫车(下班)');
        $types[] = array('id'=>'ttyongche:///sns/news/detail?id=',                      'name'=>'push帖子');

        return response()->clientSuccess($types);
    }/*}}}*/


    /**
     * @brief checkStateTypes 用户认证状态
     * @Param $request
     * @Return
     */
    public function checkStateTypes(Request $request)
    {/*{{{*/
        $types[] = array('id' => -1, 'name' => '全部');
        foreach (Constants::$ACCOUNT_CHECK_STATES as $type => $info) {
            $types[] = array('id' => $info['id'], 'name' => $info['name']);
        }
        return response()->clientSuccess($types);
    }/*}}}*/



    public function accountAuthStates(Request $request)
    {/*{{{*/
        $types[] = array('id' => -1, 'name' => '全部');
        foreach (Constants::$ACCOUNT_AUTH_STATES as $type => $info) {
            $types[] = array('id' => $info['id'], 'name' => $info['name']);
        }
        return response()->clientSuccess($types);
    }/*}}}*/

    public function licenseCheckStates(Request $request)
    {/*{{{*/
        $types[] = array('id' => -1, 'name' => '全部');
        foreach (Constants::$DRIVER_LICENSE_STATES as $type => $info) {
            $types[] = array('id' => $info['id'], 'name' => $info['name']);
        }
        return response()->clientSuccess($types);
    }/*}}}*/

    public function headImgCheckStates(Request $request)
    {/*{{{*/
        $types[] = array('id' => -1, 'name' => '全部');
        foreach (Constants::$HEAD_IMAGE_STATES as $type => $info) {
            $types[] = array('id' => $info['id'], 'name' => $info['name']);
        }
        return response()->clientSuccess($types);
    }/*}}}*/

    public function carImgCheckStates(Request $request)
    {/*{{{*/
        $types[] = array('id' => -1, 'name' => '全部');
        foreach (Constants::$CAR_IMAGE_STATES as $type => $info) {
            $types[] = array('id' => $info['id'], 'name' => $info['name']);
        }
        return response()->clientSuccess($types);
    }/*}}}*/

    public function accountSex(Request $request)
    {/*{{{*/
        $types[] = array('id' => -1, 'name' => '全部');
        foreach (Constants::$ACCOUNT_SEX as $type => $info) {
            $types[] = array('id' => $info['id'], 'name' => $info['name']);
        }
        return response()->clientSuccess($types);
    }/*}}}*/

    //车牌号前缀
    public function carPrefix(Request $request)
    {/*{{{*/
        $types[] = array('id' => '', 'name' => '全部');
        $carPrefix = ['京', '冀', '津', '沪', '粤', '浙', '川', '渝',
                     '苏', '鄂', '陕', '黑', '吉', '辽', '蒙', '鲁',
                     '晋', '甘', '豫', '皖', '贵', '湘', '赣', '闽',
                     '桂', '云', '琼', '宁', '青', '藏', '新'];
        foreach ($carPrefix as $key => $value) {
            $types[] = array('id' => $value, 'name' => $value);
        }
        return response()->clientSuccess($types);
    }/*}}}*/

    /**
     * @brief fetchRealtimeServiceAddr 
     * 实时消息服务会再启动时把自己开放的服务地址写入redis,供前端连接
     * @param $request
     * @return 
     */
    public function fetchRealtimeServiceAddr(Request $request)
    {/*{{{*/
        $key = self::REALTIME_SERVICE_REDIS_KEY;
        $addr = Redis::get($key);
        if ($addr == false) {
            $addr = "10.12.6.178:12812";
        }
        return response()->clientSuccess($addr);
    }/*}}}*/

    public function fetchSmsUserReply(Request $request) 
    {/*{{{*/
        $path = "/tmp/sms_up.log.". Date('Y-m-d', time());
        $content = @file_get_contents($path);
        $arr = explode("\n", $content);
        $ret = array();

        foreach ($arr as $c) {

            $tmp = explode(',', $c);
            if ($tmp[0] == 1) {
                $ret[] = $tmp[1]. ' '. $tmp[2].' '. $tmp[6];
            }
        }
        return response()->clientSuccess($ret);
    }/*}}}*/

    public function fetchCaptcha(Request $request)
    {/*{{{*/
        $mobile = Constants::getAdminMobile();
        $key = RedisModel::GAEA_CAPTCHA_REDIS_KEY_PREFIX . $mobile;

        $captcha = mt_rand(1000, 9999);

        Redis::set($key, $captcha);
        Redis::expire($key, 300);  // 300s

        $content = '您的验证码是: '. $captcha .', 五分钟之内有效。';
        app('notice')->sendDindin($mobile, $content);

        return response()->clientSuccess([]);
    }/*}}}*/

    public function fetchUsernames(Request $request)
    {/*{{{*/
        $users = AdminUser::get();
        $ret = [];
        foreach ($users as $user) {
            $ret[] = ['id'=>$user->username, 'name'=>$user->username];
        }

        return response()->clientSuccess($ret);
    }/*}}}*/


    public function ciProjectList(Request $request)
    {/*{{{*/
        $types = array();
        if (empty($request->input('type'))) {
            $types[] = array('id'=>0, 'name'=>'全部');
        }
        //dd(Constants::getAdminAccount());
        $isCiSuperUser = CiProjectMember::isCiSuperUser(Constants::getAdminAccount());
        //dd($isCiSuperUser);
        $projects = CiProject::getProjectsByAdminAccount(Constants::getAdminAccount(), $isCiSuperUser);
        //dd($projects);
        foreach ($projects as $item) {
            $types[] = array('id'=>$item->project_id, 'name'=>$item->project_name);
        }
        return response()->clientSuccess($types);
    }/*}}}*/

    public function ciBuildStatusList(Request $request)
    {/*{{{*/
        $types = array();
        $statusList = array(
            array('id' => 'ALL',     'name' => '全部'),
            array('id' => 'SUCCESS', 'name' => '成功'),
            array('id' => 'FAILURE', 'name' => '失败'),
            array('id' => 'RUNNING', 'name' => '运行中'),
            array('id' => 'WAITING', 'name' => '等待'),
        );

        foreach ($statusList as $item) {
            $types[] = array('id'=>$item['id'], 'name'=>$item['name']);
        }

        return response()->clientSuccess($types);
    }/*}}}*/

    public function ciHostList(Request $request)
    {/*{{{*/
        $types = [
            ['id' => '1', 'name' => '开发机',      'host_name' => 'dev01v.corp.ttyc.com', 'ip' => '172.16.10.30', 'status'=>'true'],
            ['id' => '2', 'name' => '测试机',      'host_name' => 'test01v.corp.ttyc.com', 'ip' => '172.16.10.10', 'status'=>'true'],
            ['id' => '3', 'name' => 'slave机',     'host_name' => 'xxxxxxx', 'ip' => '100.9.10.30', 'status'=>'true'],
            //['id' => '4', 'name' => 'release机器', 'host_name' => 'dev01v.corp.ttyc.com', 'ip' => '172.16.10.30', 'status'=>'false']
        ];
        return response()->clientSuccess($types);
    }/*}}}*/

    public function ciDeployStatusList(Request $request)
    {/*{{{*/
        $types = array();
        $statusList = array(
            array('id' => 'ALL',     'name' => '全部'),
            array('id' => 'SUCCESS', 'name' => '成功'),
            array('id' => 'FAILURE', 'name' => '失败'),
            array('id' => 'RUNNING', 'name' => '运行中'),
            array('id' => 'CANCEL',  'name' => '取消'),
        );

        foreach ($statusList as $item) {
            $types[] = array('id'=>$item['id'], 'name'=>$item['name']);
        }

        return response()->clientSuccess($types);
    }/*}}}*/

    public function ciTestMember(Request $request)
    {/*{{{*/
        $types = array();
        //if (empty($request->input('type'))) {
            //$types[] = array('id'=>0, 'name'=>'全部');
        //}
        $projects = CiTestMember::get();
        foreach ($projects as $item) {
            $types[] = array('id'=>$item->user_id, 'name'=>$item->user_nickname, 'user_name' => $item->username);
        }
        return response()->clientSuccess($types);
    }/*}}}*/
}
