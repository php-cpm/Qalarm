<?php
/**
 * Created by PhpStorm.
 * User: weichen
 * Date: 15/10/10
 * Time: 上午11:34
 */

namespace App\Components\UserCenterApi;


use App\Components\Utils\CurlUtil;

use App\Components\Utils\Constants;

class UserCenter
{

    const URL_WEBSITE_USERCENTER        = 'http://10.6.12.178:18181';
    const USERCENTER_USER_DETAIL        = '/usercenter/v1/user/read';
    const USERCENTER_USER_BASIC_READ    = '/usercenter/v1/user/basic/read';
    const USERCENTER_USER_BASIC_PUT     = '/usercenter/v1/user/basic/put';
    const USERCENTER_USER_DRIVER_READ   = '/usercenter/v1/user/driver/read';
    const USERCENTER_USER_DRIVER_PUT    = '/usercenter/v1/user/driver/put';
    const USERCENTER_USER_AUTH_READ     = '/usercenter/v1/user/auth/read';
    const USERCENTER_USER_AUTH_PUT      = '/usercenter/v1/user/auth/put';
    const USERCENTER_USER_CAR_READ      = '/usercenter/v1/user/car/read';
    const USERCENTER_USER_CAR_PUT       = '/usercenter/v1/user/car/put';
    const USERCENTER_USER_MATCH_READ    = '/usercenter/v1/user/match/read';
    const USERCENTER_USER_MATCH_PUT     = '/usercenter/v1/user/match/put';


    const HEADIMG_DEFAULT_URL = 'http://www.telecomhr.com/images/head_portrait.jpg';
    const CARIMG_DEFAULT_URL = 'http://7xavvn.com2.z0.glb.qiniucdn.com/32/1392989236586_ne3u5y.jpg!bigger';

    const REQUEST_USERCENTER_ID         = 'id';
    const REQUEST_USERCENTER_MOBILE     = 'mobile';
    const REQUEST_USERCENTER_NAME       = 'name';
    const REQUEST_USERCENTER_USERTYPE   = 'user_type';
    const REQUEST_USERCENTER_IDCARD     = 'idcard';

    function userList($arr_search=[]){

        if(count($arr_search) > 0){
            $search = '';
            foreach($arr_search as $key => $value){
                $search .= $key .'='.$value.'&';
            }
            $search = substr($search,0,-1);

            $url = self::URL_WEBSITE_USERCENTER.self::USERCENTER_USER_DETAIL.'?'.$search;
            $curl = new CurlUtil($url);
            return $curl->execute();
        }else{
            return '';
        }
    }

    function userDetail($arr_search=[]){

        if(count($arr_search) > 0){
            $search = '';
            foreach($arr_search as $key => $value){
                $search .= $key .'='.$value.'&';
            }
            $search = substr($search,0,-1);

            $url = self::URL_WEBSITE_USERCENTER.self::USERCENTER_USER_DETAIL.'?'.$search;
            $curl = new CurlUtil($url);
            return $curl->execute();

        }else{
            return '';
        }
    }


    //格式化用户详细信息
    function formatData($array)
    {
        foreach ($array as $key => $item) {

            if(array_key_exists('basic',$item)){
                $this->formatDataUserBasic($array[$key]['basic']);
            }

            if(array_key_exists('driver',$item) && is_array($item['driver'])){
                $this->formatDataUserDriver($array[$key]['driver']);
            }

            if(array_key_exists('auth',$item) && is_array($item['auth'])){
                $this->formatDataUserAuth($array[$key]['auth']);
            }

            if(array_key_exists('car',$item) && is_array($item['car'])){
                $this->formatDataUserCar($array[$key]['car']);
            }

            if(array_key_exists('match',$item) && is_array($item['match'])){
                $this->formatDataUserMatch($array[$key]['match']);
            }
        }

        return $array;
    }

    //格式化用户基本信息
    function formatDataUserBasic(&$arr_basic=[]){

        if(array_key_exists('sex',$arr_basic) && is_array($arr_basic)) {
            $arr_basic['sex_desc'] = '数据异常';
            foreach(Constants::$USER_SEX as $sub_key => $sub_item){
                if($sub_item['id'] == $arr_basic['sex']){
                    $arr_basic['sex_desc'] = $sub_item['name'];
                    break;
                }
            }
        }

        if(array_key_exists('headimg',$arr_basic) && $arr_basic['headimg'] == ''){
            $arr_basic['headimg'] = self::HEADIMG_DEFAULT_URL;
        }


        if(array_key_exists('user_type',$arr_basic)){
            $arr_basic['user_type_desc'] = '数据异常';
            foreach(Constants::$USER_TYPE as $sub_key => $sub_item){
                if($sub_item['id'] == $arr_basic['user_type']){
                    $arr_basic['user_type_desc'] = $sub_item['name'];
                    break;
                }
            }
        }

        if(array_key_exists('headimg_checking',$arr_basic) && $arr_basic['headimg_checking'] == ''){
            $arr_basic['headimg_checking'] = self::HEADIMG_DEFAULT_URL;
        }

        if(array_key_exists('headimg_state',$arr_basic)){
            foreach(Constants::$USER_CHECK_STATES as $sub_key => $sub_item){
                if($sub_item['id'] == $arr_basic['headimg_state']){
                    $arr_basic['headimg_state_desc'] = $sub_item['name'];
                    break;
                }
            }
        }

        if(array_key_exists('birthday',$arr_basic) && $arr_basic['birthday'] > 0 ){
            $arr_basic['birthday'] = date("Y-m-d H:i:s",$arr_basic['birthday']);
        }
    }

    //格式化用户司机信息
    function formatDataUserDriver(&$arr_driver=[]){
        if(array_key_exists('state',$arr_driver)) {
            $arr_driver['state_desc'] = '数据异常';
            foreach(Constants::$DRIVER_STATES as $sub_key => $sub_item){
                if($sub_item['id'] == $arr_driver['state']){
                    $arr_driver['state_desc'] = $sub_item['name'];
                    break;
                }
            }
        }

        if(array_key_exists('license_state',$arr_driver)) {
            $arr_driver['license_state_desc'] = '数据异常';
            foreach(Constants::$USER_CHECK_STATES as $sub_key => $sub_item){
                if($sub_item['id'] == $arr_driver['license_state']){
                    $arr_driver['license_state_desc'] = $sub_item['name'];
                    break;
                }
            }
        }
    }

    //格式化用户认证信息
    function formatDataUserAuth(&$arr_auth =[]){
        if(array_key_exists('state',$arr_auth)) {
            $arr_auth['state_desc'] = '数据异常';
            foreach(Constants::$AUTH_STATES as $sub_key => $sub_item){
                if($sub_item['id'] == $arr_auth['state']){
                    $arr_auth['state_desc'] = $sub_item['name'];
                    break;
                }
            }
        }
    }

    //格式化用户车辆信息
    function formatDataUserCar(&$arr_car=[]){

        if(array_key_exists('image',$arr_car) && $arr_car['image'] == '') {
            $arr_car['image'] = self::CARIMG_DEFAULT_URL;
        }

        if(array_key_exists('image_state',$arr_car)) {
            $arr_car['image_state_desc'] = '数据异常';
            foreach(Constants::$CAR_IMAGE_STATES as $sub_key => $sub_item){
                if($sub_item['id'] == $arr_car['image_state']){
                    $arr_car['image_state_desc'] = $sub_item['name'];
                    break;
                }
            }
        }

        if(array_key_exists('license',$arr_car) && $arr_car['license'] == '') {
            $arr_car['license'] = self::CARIMG_DEFAULT_URL;
        }

        if(array_key_exists('license_state',$arr_car)) {
            $arr_car['license_state_desc'] = '数据异常';
            foreach(Constants::$CAR_LICENCE_STATES as $sub_key => $sub_item){
                if($sub_item['id'] == $arr_car['license_state']){
                    $arr_car['license_state_desc'] = $sub_item['name'];
                    break;
                }
            }
        }

    }

    //格式化用户匹配信息
    function formatDataUserMatch(&$arr_match=[]){
        if(array_key_exists('enable',$arr_match)) {
            $arr_match['enable_desc'] = '数据异常';
            foreach(Constants::$MATCH_ENABLES as $sub_key => $sub_item){
                if($sub_item['id'] == $arr_match['enable']){
                    $arr_match['enable_desc'] = $sub_item['name'];
                    break;
                }
            }
        }

        if(array_key_exists('time_type',$arr_match)) {
            $arr_match['match']['time_type_desc'] = '数据异常';
            foreach(Constants::$MATCH_TIME_TYPE as $sub_key => $sub_item){
                if($sub_item['id'] == $arr_match['time_type']){
                    $arr_match['time_type_desc'] = $sub_item['name'];
                    break;
                }
            }
        }
    }

    private function getTestData(){

        $testData = array(
            'code' => 0,
            'msg' => '',
            'data' => array([
                'basic' => array(
                    'id' =>                   1234,
                    'name' =>                 '王先生',
                    'sex' =>                  1,
                    'headimg' =>              'http://url',
                    'mobile' =>               '18610278047',
                    'user_type' =>            1,
                    'invite_code' =>          'bdcode123',
                    'city_id' =>              131,
                    'headimg_checking' =>     'http://url',
                    'headimg_state' =>        2,
                    'headimg_reason' =>       '',
                    'home_location' =>        '北京市海淀区中关村',
                    'company_location' =>     '北京有趣技术有限公司',
                    'tags' =>                 '脑残',
                    'birthday' =>             1435905091,
                    'trade_name' =>           '互联网',
                    'position' =>             '产品经理',
                    'companys' =>              '奇虎',
                    'school_name' =>          '北大',
                    'hometown' => array(
                        'province_id' =>          13,
                        'province_name' =>        '北京',
                        'city_id' =>              131,
                        'city_name' =>            '北京',
                        'district_id' =>          4,
                        'district_name' =>        '西城区'
                    ),
                    'hobby' =>                '滑雪',
                    'intro' =>                '脱离低级趣味'
                ),

                'driver' => array(
                    'state' =>            2,
                    'license' =>          '',
                    'licence_state' =>    1,
                    'license_reason' =>   '模糊不清'
                ),

                'auth' => array (
                    'realname' =>         '冯小芳',
                    'idcard' =>           '110102198702',
                    'state' =>            2,
                    'reason' =>           '   '
                ),

                'car' => array (
                    'model_id' =>         1234,
                    'model' =>            '宝马323',
                    'color' =>            '白色',
                    'number_prefix' =>    '京',
                    'number_suffix' =>    'N***W00',
                    'image' =>            '',
                    'image_state' =>      2,
                    'image_reason' =>     '太难看了',
                    'license' =>          '',
                    'licence_state' =>    1,
                    'license_reason' =>   '车型错误',
                ),

                'match' => array (
                    'enable' =>           1,
                    'time_type' =>        1,
                    'up_time' => array(
                        'start' =>        3600,
                        'end' =>          7200,
                    ),
                    'down_time' => array (
                        'start' =>        57600,
                        'end' =>          72000,
                    )
                )
            ])
        );

        return json_encode($testData);
    }
}