<?php

namespace App\Components\Utils;

use Carbon\Carbon;

class MethodUtil
{

    protected static $appSecret = 'ab37655347122540857b44950fca0ba3';

    /**
     * @brief convertToUTF8 把其他编码的中文转换成UTF8
     *
     * @Param $data
     *
     * @Return  
     */
    public static function convertToUTF8($data)
    {/*{{{*/
        if (!empty($data) ) {
            $fileType = mb_detect_encoding($data , array('UTF-8','GBK','LATIN1','BIG5')) ;
            if( $fileType != 'UTF-8') {
                $data = mb_convert_encoding($data ,'utf-8' , $fileType);
            }
        }

        return $data;
    }/*}}}*/

    public static function getSignatureArray()
    {/*{{{*/
        $in = array();
        $in['timestamp'] = microtime(true);
        $in['rand'] = mt_rand();
        $var = sprintf("%s%s%s",self::$appSecret, $in['timestamp'], $in['rand']);
        $in['sign'] = sha1($var);

        return $in;
    }/*}}}*/

    public static function getActions()
    {/*{{{*/
        return join(',', [
            Constants::REQUEST_TYPE_ADD,
            Constants::REQUEST_TYPE_GET,
            Constants::REQUEST_TYPE_DELETE,
            Constants::REQUEST_TYPE_UPDATE,
            Constants::REQUEST_TYPE_LIST
        ]);
    }/*}}}*/


    /**
     * @brief getUniqueId 返回一个唯一ID
     * @Return  long long 20位
     */
    public static function getUniqueId()
    {/*{{{*/
        // 最大2位
        $pidField = posix_getpid() % 100; 
        // 去掉前面的20，2015年到2100的85年轮回已经够了
        $dateField = date("Ymdhis"); 
        $dateField = substr($dateField, 2);
        $now = microtime(true);
        // microtime返回一个浮点数，有可能没有.分隔符
        if (strpos($now, '.') !== false) {
            list($s, $ms) = explode (".", $now);
        } else {
            $s = $now;
            $ms = 0;
        }
        // 最大3位
        $msField = sprintf("%03d", $ms % 1000); 

        // 0 -- 99 的随机数
        $rand = mt_rand(0, 99);

        return $dateField.$msField.$pidField.$rand;
    }/*}}}*/


    /**
     * @brief assemblyJobData 组装一个异步Job的数据
     * @param $raw
     * @return  array|object
     */
    public static function assemblyJobData($raw)
    {/*{{{*/
        if (is_array($raw)) {
            $raw['job_id'] = self::getUniqueId();
            $raw['job_created_at'] = Carbon::now();
        } else {
            $raw->job_id   = self::getUniqueId();
            $raw->job_created_at = Carbon::now();
        }

        return $raw;
    }/*}}}*/


    /**
     * @brief objectToArray 将对象转换为多维数组  
     * @param $d
     * @return array
     */
    public static function objectToArray($d) 
    {/*{{{*/
        if (is_object($d)) {
                // Gets the properties of the given object
                // with get_object_vars function
                $d = get_object_vars($d);
        }

        if (is_array($d)) {
            /*
             * Return array converted to object
             * Using __METHOD__(Magic constant)
             * for recursive call
             */
            return array_map(__METHOD__, $d);
        }
        else {
            // Return array
            return $d;
        }
    }/*}}}*/


    /**
     * @brief arrayToObject 多维数组转换为对象
     * @param $d
     * @return object
     */
    public static function arrayToObject($d)
    {/*{{{*/
        if (is_array($d)) {
            /*
             * Return array converted to object
             * Using __METHOD__(Magic constant)
             * for recursive call
             */
            return (object) array_map(__METHOD__, $d);
        }
        else {
            // Return object
            return $d;
        }
    }/*}}}*/

    /**
     * @brief getLaravelEnvName 获取环境名字
     * @return test | production
     */
    public static function getLaravelEnvName()
    {/*{{{*/
        $env = 'test';
        if (app()->environment('production', 'staging')) { 
            $env = 'production';
        }

        return $env;
    }/*}}}*/

}
