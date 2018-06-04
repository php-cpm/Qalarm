<?php
/**
* @file Constants.php
* @brief 常量定义类
* @author willas
* @version 1
* @date 2015-07-25
 */

namespace App\Components\Utils;

use App\Models\Gaea\AdminUser;

class Constants
{
    //cookie时间
    const COOKIE_TIME = 86400;

    //分页
    const MAX_PAGE_SIZE     = 15;

    //最大允许占用内存
    const MAX_MEM_COST = 1500000;

    // 函数的调用方式
    const CALL_SYNC  = 1; 
    const CALL_ASYNC = 0; 

    //web server type
    static public $WEB_SERVER_TYPE = array(
            1   => 'nginx',
            2   => 'apache'
        );
    
    static public $WEB_SERVER_CONFIG = array(
            1   => 'config/nginx_conf.php',
            2   => 'config/httpd_conf.php'
        );

    private static $admin = array();

    public static function setAdmin($admin)
    {
        self::$admin = $admin;
    }

    public static function getAdminName()
    {
        return isset(self::$admin['admin_name']) ?self::$admin['admin_name'] : '';
    }

    public static function getAdminAccount()
    {
        return isset(self::$admin['admin_account']) ?self::$admin['admin_account'] : '';
    }

    public static function getAdminMail()
    {
        return isset(self::$admin['admin_mail']) ?self::$admin['admin_mail'] : '';
    }

    public static function getAdminId()
    {
        return isset(self::$admin['admin_id']) ?self::$admin['admin_id'] : '';
    }

    public static function getAdminMobile()
    {
        return isset(self::$admin['admin_mobile']) ?self::$admin['admin_mobile'] : '';
    }

    public static function getSuperMan()
    {
        $super = AdminUser::where('username', 'superman')->first();
        if (!is_null($super)) {
            return $super;
        }

        return AdminUser::where('username', 'chenfei')->first();
    }


    const QALARM_PID = 500;
    const QALARM_MODEL_SMS = 29;
    const QALARM_MODEL_OTHER = 31;
    const QALARM_MODEL_PUSH = 28;
    const QALARM_MODEL_CALL = 32;

    // 服务端口，使用时会优先从qcon中取，如果没有取到则用此配置
//    const ONLINE_SERVERS = array(
//        array('ip'=>'10.10.135.97', 'port'=>12800),
//        array('ip'=>'10.6.4.111', 'port'=>12800),
//    );

    const PUSH_DEFAULT_TEMPLETE_SUB_CODE = 1;

    const SMS_TEMPLETE_SERVICE_TYPE = 1;
    const PUSH_TEMPLETE_SERVICE_TYPE = 2;
    
    const REQUEST_TYPE_ADD = 'add';
    const REQUEST_TYPE_GET = 'get';
    const REQUEST_TYPE_DELETE = 'delete';
    const REQUEST_TYPE_UPDATE = 'update';
    const REQUEST_TYPE_LIST = 'list';

    const SESSION_TIMEOUT = 2592000; // 30 * 24 * 3600 s




    /*
     * 七牛gaea空间相关配置
     */
    const USER_HEAD_IMG_PATH = '/home/t/system/ttyc-gaea/public/user_head/';
    const QINIU_ACCESS_KEY = 'WIOzjl79UpfOQjZnhhDOW1kK3u9ZYpKqNEf5uDmU';
    const QINIU_SECRET_KEY =  'ylIrcrgw7bd1wUH1dVQMkho4os2t-g0WX-vERGQB';
    //配置bucket
    const QINIU_BUCKET = "ttyc-gaea";
    //配置的域名访问地址
    const QINIU_HOST = "http://7xo3bd.com1.z0.glb.clouddn.com";
    //七牛SDK 固定值
    //$QINIU_UP_HOST	= 'http://up.qiniu.com';
    //$QINIU_RS_HOST	= 'http://rs.qbox.me';
    const QINIU_RSF_HOST	= 'http://rsf.qbox.me';


    static public $COUPON_TYPE =  array(
        '10' => array('mname'=>'优惠券', 'sname'=>'代金券', 'argv'=>1),
        '11' => array('mname'=>'优惠券', 'sname'=>'抵用券', 'argv'=>2),
        '12' => array('mname'=>'优惠券', 'sname'=>'通乘券', 'argv'=>1),
        '13' => array('mname'=>'优惠券', 'sname'=>'折扣券', 'argv'=>1),
        '14' => array('mname'=>'优惠券', 'sname'=>'任意金额代金券', 'argv'=>0),
        '21' => array('mname'=>'现金券', 'sname'=>'', 'argv'=>1),
        '31' => array('mname'=>'实物券', 'sname'=>'', 'argv'=>1),
        '41' => array('mname'=>'助补',   'sname'=>'', 'argv'=>1),
        '51' => array('mname'=>'红包',   'sname'=>'', 'argv'=>2),
        '101' => array('mname'=>'免服务费用', 'sname'=>'', 'argv'=>0),
    );

    /* '61' => array('mname'=>'手工发放', 'sname'=>'', 'argv'=>1), */
    const COUPON_TYPE_MANUAL = 61;

    const SHUN_FENG_CHE                 = 1;
    const CAR_LIFE                      = 2;
    const ALL_CAR_LIFE                  = 100;
    const BUSS_TYPE_WASH                = 10;
    const BUSS_TYPE_MAINTAIN            = 11;
    const BUSS_TYPE_PAD_PASTING         = 12;
    const BUSS_TYPE_PAINT_CARE          = 13;
    const BUSS_TYPE_CLEAN               = 14;
    const BUSS_TYPE_PLATE_SPRAY         = 15;
    const BUSS_TYPE_INSPECTION          = 16;
    const BUSS_TYPE_JINJING_LICENSE     = 17;
    const BUSS_TYPE_PECCANCY            = 18;
    const BUSS_TYPE_SPRAY_PAINTING      = 19;
    const BUSS_TYPE_MANUAL              = 20;

    static public $BUSS_TYPE  = array(
        '1'  => array('mname' => '顺风车', 'sname' => '必应', 'mbusstype' => self::SHUN_FENG_CHE),
        '2'  => array('mname' => '顺风车', 'sname' => '其他', 'mbusstype' => self::SHUN_FENG_CHE),
        '10' => array('mname' => '车服务', 'sname' => '洗车', 'mbusstype' => self::CAR_LIFE),
        '11' => array('mname' => '车服务', 'sname' => '保养', 'mbusstype' => self::CAR_LIFE),
        '12' => array('mname' => '车服务', 'sname' => '贴膜', 'mbusstype' => self::CAR_LIFE),
        '13' => array('mname' => '车服务', 'sname' => '漆面护理', 'mbusstype' => self::CAR_LIFE),
        '14' => array('mname' => '车服务', 'sname' => '内饰深度清洁', 'mbusstype' => self::CAR_LIFE),
        '15' => array('mname' => '车服务', 'sname' => '钣喷', 'mbusstype' => self::CAR_LIFE),
        '16' => array('mname' => '车服务', 'sname' => '年检', 'mbusstype' => self::CAR_LIFE),
        '17' => array('mname' => '车服务', 'sname' => '进京证', 'mbusstype' => self::CAR_LIFE),
        '18' => array('mname' => '车服务', 'sname' => '违章', 'mbusstype' => self::CAR_LIFE),
        '19' => array('mname' => '车服务', 'sname' => '喷漆', 'mbusstype' => self::CAR_LIFE),
        '20' => array('mname' => '车服务', 'sname' => '手工单', 'mbusstype' => self::CAR_LIFE),
        '100' => array('mname' => '车服务', 'sname' => '车生活全部可用优惠卷', 'mbusstype' => self::CAR_LIFE),
    );

    const SUB_SERVICE   = 71;
    static public $MANUAL_COUPON_TYPE  = array(
        '1'  => array('mname' => '用户补偿', 'sname' => ''),
        '2'  => array('mname' => '内部测试', 'sname' => ''),
        '3'  => array('mname' => '车服务',   'sname' => '保养'),
        '71' => array('mname' => '免服务费', 'sname' => '免服务费'),
    );


    static public $USER_TYPE = array(
        array('id' => 1, 'name' => '正式用户'),
        array('id' => 2, 'name' => '测试用户')
    );

    static public $ACCOUNT_TYPE = array(
        array('id' => 1, 'name' => '正式用户'),
        array('id' => 2, 'name' => '测试用户')
    );

    static public $ACCOUNT_CHECK_STATES = array(
        array('id' => 0, 'name' => '未上传'),
        array('id' => 1, 'name' => '审核中'),
        array('id' => 2, 'name' => '成功'),
        array('id' => 3, 'name' => '失败'),
    );

    static public $ACCOUNT_SEX = array(
        array('id' => 1, 'name' => '男'),
        array('id' => 2, 'name' => '女')
    );

    static public $DRIVER_STATES = array(
        array('id' => 0, 'name' => '未上传'),
        array('id' => 1, 'name' => '审核中'),
        array('id' => 2, 'name' => '成功'),
        array('id' => 3, 'name' => '失败')
    );

    static public $DRIVER_LICENSE_STATES = array(
        array('id' => 0, 'name' => '未上传'),
        array('id' => 1, 'name' => '审核中'),
        array('id' => 2, 'name' => '成功'),
        array('id' => 3, 'name' => '失败')
    );

    static public $ACCOUNT_AUTH_STATES = array(
        array('id' => 0, 'name' => '未认证'),
        array('id' => 1, 'name' => '认证中'),
        array('id' => 2, 'name' => '成功'),
        array('id' => 3, 'name' => '失败')
    );

    static public $CAR_IMAGE_STATES = array(
        array('id' => 0, 'name' => '未上传'),
        array('id' => 1, 'name' => '审核中'),
        array('id' => 2, 'name' => '成功'),
        array('id' => 3, 'name' => '失败')
    );

    static public $CAR_LICENCE_STATES = array(
        array('id' => 0, 'name' => '未上传'),
        array('id' => 1, 'name' => '审核中'),
        array('id' => 2, 'name' => '成功'),
        array('id' => 3, 'name' => '失败')
    );

    static public $MATCH_ENABLES = array(
        array('id' => 0, 'name' => '开启推送'),
        array('id' => 1, 'name' => '未开启推送')
    );

    static public $MATCH_TIME_TYPE = array(
        array('id' => 1, 'name' => '所有时间'),
        array('id' => 2, 'name' => '指定时间')
    );

    static public $HEAD_IMAGE_STATES = array(
        array('id' => 0, 'name' => '未上传'),
        array('id' => 1, 'name' => '审核中'),
        array('id' => 2, 'name' => '成功'),
        array('id' => 3, 'name' => '失败')
    );


}
