<?php
/**
 * Created by PhpStorm.
 * User: weichen
 * Date: 15/10/10
 * Time: 上午11:34
 */

namespace App\Components\AccountCenterApi;


use App\Components\Utils\CurlUtil;

use App\Components\Utils\Constants;

use App\Components\Utils\HttpUtil;

use TTYC\Common\Utils\InnerServiceSDKHttp;
use TTYC\Common\Utils\InnerServiceSDK;

class AccountCenter
{

//    const URL_WEBSITE_ACCOUNTCENTER       = 'http://10.6.12.178:18181';
    const URL_WEBSITE_ACCOUNTCENTER     = 'http://ucenter.ttyongche.com';

    const ACCOUNTCENTER_ACC_DETAIL      = 'ucenter_v1_user_get';
    const ACCOUNTCENTER_ACC_BASIC_READ  = 'ucenter_v1_user_basic_get';
    const ACCOUNTCENTER_ACC_BASIC_PUT   = 'ucenter_v1_user_basic_put';
    const ACCOUNTCENTER_ACC_DRIVER_READ = 'ucenter_v1_user_driver_get';
    const ACCOUNTCENTER_ACC_DRIVER_PUT  = 'ucenter_v1_user_driver_put';
    const ACCOUNTCENTER_ACC_AUTH_READ   = 'ucenter_v1_user_auth_get';
    const ACCOUNTCENTER_ACC_AUTH_PUT    = 'ucenter_v1_user_auth_put';
    const ACCOUNTCENTER_ACC_CAR_READ    = 'ucenter_v1_user_car_get';
    const ACCOUNTCENTER_ACC_CAR_PUT     = 'ucenter_v1_user_car_put';
    const ACCOUNTCENTER_ACC_MATCH_READ  = 'ucenter_v1_user_match_get';
    const ACCOUNTCENTER_ACC_MATCH_PUT   = 'ucenter_v1_user_match_put';
    const ACCOUNTCENTER_ACC_CAR_BRAND_READ = 'ucenter_v1_common_car_brand_get';
    const ACCOUNTCENTER_ACC_OP_LOG_LIST = 'ucenter_v1_op_log_list_get';

    const ACCOUNTCENTER_ACC_PHOTOS_GET = 'ucenter_v1_user_photos_get';
    const ACCOUNTCENTER_ACC_PHOTOS_DEL = 'ucenter_v1_user_photos_delete';


    const HEADIMG_DEFAULT_URL = 'http://www.telecomhr.com/images/head_portrait.jpg';
    const CARIMG_DEFAULT_URL = 'http://7xavvn.com2.z0.glb.qiniucdn.com/32/1392989236586_ne3u5y.jpg!bigger';

    const DRIVERLICENSEIMG_DEFAULT_URL = 'http://ttyc-im.qiniudn.com/57885434881e0046f48ae3f5a177aa3df58e5ad82bcbdfa4073199256d78cce2';
    const CARLICENSEIMG_DEFAULT_URL = 'http://ttyc-im.qiniudn.com/57885434881e0046f48ae3f5a177aa3df58e5ad82bcbdfa4073199256d78cce2';

    const REQUEST_ACCOUNTCENTER_ID = 'id';
    const REQUEST_ACCOUNTCENTER_MOBILE = 'mobile';
    const REQUEST_ACCOUNTCENTER_NAME = 'name';
    const REQUEST_ACCOUNTCENTER_ACCTYPE = 'user_type';
    const REQUEST_ACCOUNTCENTER_IDCARD = 'idcard';


    protected $ucentrSdk;

    function __construct()
    {/*{{{*/
        $this->ucentrSdk = new InnerServiceSDKHttp(['gaea_ucenter' =>
            ['gaea_usercenter_d435a6cd', '4d134bc072212ace2df385dae143139da74ec0ef']
        ], 'gaea', app()->environment('local') ? InnerServiceSDK::TEST : InnerServiceSDK::PRODUCTION);

    }/*}}}*/

    /**
     * 账户列表
     * @param array $arr_search
     * @return array|string
     */
    public function accountList($arrSearch = [])
    {/*{{{*/
        $paramReturnInfo = array(
            "basic" => array("id", "name", "sex", "mobile", "user_type", "register_time", "home_location", "company_location"),
            "auth" => array(),
            "car" => array(),
            "driver" => array("state", "license_state", "license_upload_time", "license_reason")
        );
        $arrSearch[] = ['return_info' => json_encode($paramReturnInfo)];

        $method = self::ACCOUNTCENTER_ACC_DETAIL;
        $result = $this->ucentrSdk->$method($arrSearch);
        return $result;
    }/*}}}*/

    /**
     * 账户详情接口
     * @param array $arr_search
     * @return array|string
     */
    public function accountDetail($arrSearch = [])
    {/*{{{*/
        $paramReturnInfo = array(
            "basic"     => array(),
            "auth"      => array(),
            "car"       => array(),
            "driver"    => array(),
            "match"     => array()
        );
        $arrSearch[] = ['return_info' => json_encode($paramReturnInfo)];

        $method = self::ACCOUNTCENTER_ACC_DETAIL;
        $result = $this->ucentrSdk->$method($arrSearch);
        return $result;
    }/*}}}*/

    /**
     * 实名认证接口
     * @param array $arr_search
     * @return array|string
     */
    public function accountAuthList($arrSearch = [])
    {/*{{{*/
        $paramReturnInfo = array(
            "basic" => array("id", "name", "sex", "mobile", "headimg", "user_type"),
            "auth" => array()
        );
        $arrSearch[] = ['return_info' => json_encode($paramReturnInfo)];

        $method = self::ACCOUNTCENTER_ACC_DETAIL;
        $result = $this->ucentrSdk->$method($arrSearch);
        return $result;
    }/*}}}*/

    /**
     * 用户实名认证信息修改
     * @param array $params
     * @return array
     */
    public function accountAuthUpdate($params = [])
    {/*{{{*/
        $method = self::ACCOUNTCENTER_ACC_AUTH_PUT;
        $result = $this->ucentrSdk->$method($params);
        return $result;
    }/*}}}*/

    /**
     * 车辆信息修改接口
     * @param array $params
     * @return array
     */
    function carUpdate($params = [])
    {/*{{{*/
        $method = self::ACCOUNTCENTER_ACC_CAR_PUT;
        $result = $this->ucentrSdk->$method($params);
        return $result;
    }/*}}}*/

    /**
     * 驾驶信息修改
     * @param array $params
     * @return array
     */
    public function driverUpdate($params = [])
    {/*{{{*/
        $method = self::ACCOUNTCENTER_ACC_DRIVER_PUT;

        $result = $this->ucentrSdk->$method($params);
        return $result;
    }/*}}}*/

    /**
     * 用户基本信息修改:包含头像状态.及称呼等修改
     * @param array $params
     * @return array
     */
    public function accountBasicUpdate($params = [])
    {/*{{{*/
        $method = self::ACCOUNTCENTER_ACC_BASIC_PUT;
        $result = $this->ucentrSdk->$method($params);
        return $result;
    }/*}}}*/

    /**
     * 用户头像审核列表接口
     * @param $arr_search
     * @return array|string
     */
    public function accountHeadImgCheckList($arrSearch = [])
    {/*{{{*/
        $paramReturnInfo = array(
            "basic" => array("id", "name", "sex", "mobile", "user_type", "register_time", "home_location", "company_location"),
            "auth" => array(),
            "car" => array(),
            "driver" => array("state", "license_state", "license_upload_time", "license_reason")
        );
        $arrSearch[] = ['return_info' => json_encode($paramReturnInfo)];

        $method = self::ACCOUNTCENTER_ACC_DETAIL;
        $result = $this->ucentrSdk->$method($arrSearch);
        return $result;
    }/*}}}*/

    /**
     * 用户证件审核列表接口
     * @param $arr_search
     * @return array|string
     */
    public function accountLicenseCheckList($arrSearch = [])
    {/*{{{*/
        $paramReturnInfo = array(
            "basic" => array("id", "name", "sex", "mobile", "user_type", "register_time", "home_location", "company_location"),
            "auth" => array(),
            "car" => array(),
            "driver" => array("state", "license_state", "license_upload_time", "license_reason")
        );

        $method = self::ACCOUNTCENTER_ACC_DETAIL;
        $arr_search[] = ['return_info' => json_encode($paramReturnInfo)];
        $result = $this->ucentrSdk->$method($arrSearch);
        return $result;
    }/*}}}*/


    //车照或是行驶证或是驾驶证处于 审核中数据
    public function accountNotCheckCarImgLicense($arrSearch = [])
    {/*{{{*/
        $paramReturnInfo = array(
            "basic" => array("id", "name", "sex", "mobile", "user_type", "register_time", "home_location", "company_location"),
            "auth" => array(),
            "car" => array(),
            "driver" => array("state", "license_state", "license_upload_time", "license_reason")
        );
        $arrSearch[] = ['return_info' => json_encode($paramReturnInfo)];

        $method = self::ACCOUNTCENTER_ACC_DETAIL;
        $result = $this->ucentrSdk->$method($arrSearch);
        return $result;
    }/*}}}*/

    public function carBrandList($arrSearch = [])
    {/*{{{*/
        $method = self::ACCOUNTCENTER_ACC_CAR_BRAND_READ;
        $result = $this->ucentrSdk->$method($arrSearch);
        return $result;
    }/*}}}*/


    public function photos($arrSearch = [])
    {/*{{{*/
        $method = self::ACCOUNTCENTER_ACC_PHOTOS_GET;
        $result = $this->ucentrSdk->$method($arrSearch);
        return $result;
    }/*}}}*/

    public function photoDel($arrSearch = [])
    {/*{{{*/
        $method = self::ACCOUNTCENTER_ACC_PHOTOS_DEL;
        $result = $this->ucentrSdk->$method($arrSearch);
        return $result;
    }/*}}}*/

    public function photosDelLogs($arrSearch = [])
    {/*{{{*/
        $method = self::ACCOUNTCENTER_ACC_OP_LOG_LIST;
        $result = $this->ucentrSdk->$method($arrSearch);
        return $result;
    }/*}}}*/

    public function voiceDelLogs($arrSearch = [])
    {/*{{{*/
        $method = self::ACCOUNTCENTER_ACC_OP_LOG_LIST;
        $result = $this->ucentrSdk->$method($arrSearch);
        return $result;
    }/*}}}*/

    //格式化用户详细信息
    public function formatData($array)
    {/*{{{*/
        foreach ($array as $key => $item) {

            if (array_key_exists('basic', $item)) {
                $this->formatDataAccountBasic($array[$key]['basic']);
            }

            if (array_key_exists('driver', $item) && is_array($item['driver'])) {
                $this->formatDataAccountDriver($array[$key]['driver']);
            }

            if (array_key_exists('auth', $item) && is_array($item['auth'])) {
                $this->formatDataAccountAuth($array[$key]['auth']);
            }

            if (array_key_exists('car', $item) && is_array($item['car'])) {
                $this->formatDataAccountCar($array[$key]['car']);
            }

            if (array_key_exists('match', $item) && is_array($item['match'])) {
                $this->formatDataAccountMatch($array[$key]['match']);
            }
        }

        return $array;
    }/*}}}*/

    //格式化用户基本信息
    protected function formatDataAccountBasic(&$arr_basic = [])
    {/*{{{*/
        if (array_key_exists('sex', $arr_basic) && is_array($arr_basic)) {
            $arr_basic['sex_desc'] = '数据异常';
            foreach (Constants::$ACCOUNT_SEX as $sub_key => $sub_item) {
                if ($sub_item['id'] == $arr_basic['sex']) {
                    $arr_basic['sex_desc'] = $sub_item['name'];
                    break;
                }
            }
        }

        if (array_key_exists('headimg', $arr_basic)) {
            if ($arr_basic['headimg'] == '' || $arr_basic['headimg'] == null) {
                $arr_basic['headimg'] = self::HEADIMG_DEFAULT_URL;
            } else {
                $arr_basic['headimg'] = $this->isPhoto($arr_basic['headimg']);
            }
        }

        if (array_key_exists('user_type', $arr_basic)) {
            $arr_basic['user_type_desc'] = '数据异常';
            foreach (Constants::$ACCOUNT_TYPE as $sub_key => $sub_item) {
                if ($sub_item['id'] == $arr_basic['user_type']) {
                    $arr_basic['user_type_desc'] = $sub_item['name'];
                    break;
                }
            }
        }

        if (array_key_exists('headimg_checking', $arr_basic) && $arr_basic['headimg_checking'] == '') {
            $arr_basic['headimg_checking'] = self::HEADIMG_DEFAULT_URL;
        }

        if (array_key_exists('headimg_state', $arr_basic)) {
            foreach (Constants::$ACCOUNT_CHECK_STATES as $sub_key => $sub_item) {
                if ($sub_item['id'] == $arr_basic['headimg_state']) {
                    $arr_basic['headimg_state_desc'] = $sub_item['name'];
                    break;
                }
            }
        }

        if (array_key_exists('birthday', $arr_basic) && $arr_basic['birthday'] > 0) {
            $arr_basic['birthday'] = date("Y-m-d H:i:s", $arr_basic['birthday']);
        }

        if (array_key_exists('register_time', $arr_basic) && $arr_basic['register_time'] > 0) {
            $arr_basic['register_time'] = date("Y-m-d H:i:s", $arr_basic['register_time']);
        }

        if (array_key_exists('headimg_upload_time', $arr_basic) && $arr_basic['headimg_upload_time'] > 0) {
            $arr_basic['headimg_upload_time'] = date("Y-m-d H:i:s", $arr_basic['headimg_upload_time']);
        }
    }/*}}}*/

    //格式化用户司机信息
    protected function formatDataAccountDriver(&$arr_driver = [])
    {/*{{{*/
        if (array_key_exists('state', $arr_driver)) {
            $arr_driver['state_desc'] = '数据异常';
            foreach (Constants::$DRIVER_STATES as $sub_key => $sub_item) {
                if ($sub_item['id'] == $arr_driver['state']) {
                    $arr_driver['state_desc'] = $sub_item['name'];
                    break;
                }
            }
        }

        if (array_key_exists('license', $arr_driver)) {
            if ($arr_driver['license'] == '' || $arr_driver['license'] == null) {
                $arr_driver['license'] = self::DRIVERLICENSEIMG_DEFAULT_URL;
            } else {
                $arr_driver['license'] = $this->isPhoto($arr_driver['license']);
            }
        }

        if (array_key_exists('license_state', $arr_driver)) {
            $arr_driver['license_state_desc'] = '数据异常';
            foreach (Constants::$DRIVER_LICENSE_STATES as $sub_key => $sub_item) {
                if ($sub_item['id'] == $arr_driver['license_state']) {
                    $arr_driver['license_state_desc'] = $sub_item['name'];
                    break;
                }
            }
        }

        if (array_key_exists('license_upload_time', $arr_driver) && $arr_driver['license_upload_time'] > 0) {
            $arr_driver['license_upload_time'] = date("Y-m-d H:i:s", $arr_driver['license_upload_time']);
        }
    }/*}}}*/

    //格式化用户认证信息
    protected function formatDataAccountAuth(&$arr_auth = [])
    {/*{{{*/
        if (array_key_exists('state', $arr_auth)) {
            $arr_auth['state_desc'] = '数据异常';
            foreach (Constants::$ACCOUNT_AUTH_STATES as $sub_key => $sub_item) {
                if ($sub_item['id'] == $arr_auth['state']) {
                    $arr_auth['state_desc'] = $sub_item['name'];
                    break;
                }
            }
        }
    }/*}}}*/

    //格式化用户车辆信息
    protected function formatDataAccountCar(&$arr_car = [])
    {/*{{{*/
        if (array_key_exists('image', $arr_car)) {
            if ($arr_car['image'] == '' || $arr_car['image'] == null) {
                $arr_car['image'] = self::CARIMG_DEFAULT_URL;
            } else {
                $arr_car['image'] = $this->isPhoto($arr_car['image']);
            }
        }

        if (array_key_exists('image_state', $arr_car)) {
            $arr_car['image_state_desc'] = '数据异常';
            foreach (Constants::$CAR_IMAGE_STATES as $sub_key => $sub_item) {
                if ($sub_item['id'] == $arr_car['image_state']) {
                    $arr_car['image_state_desc'] = $sub_item['name'];
                    break;
                }
            }
        }

        if (array_key_exists('license', $arr_car)) {
            if ($arr_car['license'] == '' || $arr_car['license'] == null) {
                $arr_car['license'] = self::CARLICENSEIMG_DEFAULT_URL;
            } else {
                $arr_car['license'] = $this->isPhoto($arr_car['license']);
            }
        }

        if (array_key_exists('license_state', $arr_car)) {
            $arr_car['license_state_desc'] = '数据异常';
            foreach (Constants::$CAR_LICENCE_STATES as $sub_key => $sub_item) {
                if ($sub_item['id'] == $arr_car['license_state']) {
                    $arr_car['license_state_desc'] = $sub_item['name'];
                    break;
                }
            }
        }

        if (array_key_exists('license_upload_time', $arr_car) && $arr_car['license_upload_time'] > 0) {
            $arr_car['license_upload_time'] = date("Y-m-d H:i:s", $arr_car['license_upload_time']);
        }

    }/*}}}*/

    //格式化用户匹配信息
    protected function formatDataAccountMatch(&$arr_match = [])
    {/*{{{*/
        if (array_key_exists('enable', $arr_match)) {
            $arr_match['enable_desc'] = '数据异常';
            foreach (Constants::$MATCH_ENABLES as $sub_key => $sub_item) {
                if ($sub_item['id'] == $arr_match['enable']) {
                    $arr_match['enable_desc'] = $sub_item['name'];
                    break;
                }
            }
        }

        if (array_key_exists('time_type', $arr_match)) {
            $arr_match['match']['time_type_desc'] = '数据异常';
            foreach (Constants::$MATCH_TIME_TYPE as $sub_key => $sub_item) {
                if ($sub_item['id'] == $arr_match['time_type']) {
                    $arr_match['time_type_desc'] = $sub_item['name'];
                    break;
                }
            }
        }
    }/*}}}*/

    /**
     * 简单校验图片有效性:有效返回图片地址;无效返回空
     * @param $subject
     * @return bool
     */
    function isPhoto($subject)
    {/*{{{*/
        $pattern = '/\b(([\w-]+:\/\/?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|\/)))/';
        if (preg_match($pattern, $subject)) {
            return $subject;
        }
        return '';
    }/*}}}*/
}
