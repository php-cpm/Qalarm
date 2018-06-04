<?php
/**
 * 通用错误码
 *
 * @author willas
 */

namespace App\Components\Utils;

class ErrorCodes
{
    /**
     * 一般错误：0-99，其中，0和1是固定的，2-99由业务自定义
     */
    const ERR_SUCCESS = 1;          //成功
    const ERR_FAILURE = -1;          //失败

    /**
     * 参数错误：100-199
     */
    const ERR_PARAM_ERROR        = 100; //参数错误
    const ERR_EMPTY_PARAM        = 101; //参数为空
    const ERR_PARAM_TYPE_UNMATCH = 102;	//参数类型不匹配
    const ERR_EMPTY_CONFIG       = 103;	//配置信息为空
    const ERR_MISSING_PARAM      = 104;	//参数不全
    const ERR_BAD_PARAM_FORMAT   = 105;	//参数格式错误
    const ERR_PARAM_LEN_ERROR    = 106; //参数长度错误
    const ERR_STATUS_NOT_MATCH   = 107; //参数状态错误

    /**
     * MongoDB操作错误：200-299
     */
    const ERR_MONGO_ERROR             = 200; //MongoDB错误
    const ERR_MONGO_CONNECTION_FAILED = 201; //MongoDB连接错误
    const ERR_MONGO_READ_FAILED       = 202; //MongoDB读操作失败
    const ERR_MONGO_WRITE_FAILED      = 203; //MongoDB写操作失败
    const ERR_MONGO_EMPTY_DATA        = 204; //从MongoDB未读到数据

    /**
     * Redis操作错误：300-399
     */
    const ERR_REDIS_ERROR             = 300; //Redis错误
    const ERR_REDIS_CONNECTION_FAILED = 301; //Redis连接错误
    const ERR_REDIS_CLOSE_FAILED      = 302; //Redis关闭失败
    const ERR_REDIS_AUTH_FAILED       = 303; //Redis认证失败
    const ERR_REDIS_READ_FAILED       = 304; //Redis读操作失败
    const ERR_REDIS_WRITE_FAILED      = 305; //Redis写操作失败
    const ERR_REDIS_EMPTY_DATA        = 306; //从Redis未读到数据

    /**
     * Memcache操作错误：400-499
     */
    const ERR_MEMCACHE_ERROR = 400;	//Memcache错误

    /**
     * Kafka操作错误：500-599
     */
    const ERR_KAFKA_ERROR             = 500; //Kafka错误
    const ERR_KAFKA_ASYNC_SEND_FAILED = 501; //Kafka异步发送错误
    const ERR_KAFKA_SEND_FAILED       = 502; //Kafka同步发送错误
    const ERR_KAFKA_WORK_ERROR        = 503; //Kafka消费进程工作错误

    /**
     * Weibo错误：600-699
     */
    const ERR_WEIBO_ERROR           = 600; //Weibo错误
    const ERR_WEIBO_MISSING_KEY     = 601; //缺少微博app key或app secret
    const ERR_WEIBO_MISSING_TOKEN   = 602; //缺少微博access token或refresh token
    const ERR_WEIBO_MISSING_IP      = 603; //缺少用户IP
    const ERR_WEIBO_UNSUPPORTED_URL = 604; //不支持的微博URL
    const ERR_WEIBO_INTERFACE_ERROR = 605; //调用微博接口出错

    /**
     * User错误：700-799
     */
    const ERR_USER_ERROR        = 700; //User错误
    const ERR_PERMISSION_DENIED = 701; //没有操作权限

    /**
     * 数据库错误：800-899
     */
    const ERR_DB_ERROR             = 800; //数据库错误
    const ERR_DB_CONNECTION_FAILED = 801; //数据库连接错误
    const ERR_DB_READ_FAILED       = 802; //数据库读操作失败
    const ERR_DB_WRITE_FAILED      = 803; //数据库写操作失败
    const ERR_DB_EMPTY_DATA        = 804; //从数据库未读到数据

    /**
     * Cassandra错误：900-999
     */
    const ERR_CASSANDRA_ERROR             = 900; //Cassandra错误
    const ERR_CASSANDRA_CONNECTION_FAILED = 901; //Cassandra连接错误
    const ERR_CASSANDRA_READ_FAILED       = 902; //Cassandra读操作错误
    const ERR_CASSANDRA_WRITE_FAILED      = 903; //Cassandra写操作错误

    /**
	 * Filter错误：1000-1099
	 */
	const ERR_FILTER_CURL = 1000;

    /**
     * 授权认证
     */
    const ERR_AUTH_PARAM_ERROR = 1100;
    const ERR_AUTH_FAILED = 1101;

    /**
     * Curl 相关错误
     */
    const ERR_CURL_FAILED = 1200;
    const ERR_CURL_TIMEOUT = 1201;

    /**
     * Push 相关错误
     */
    const ERR_PUSH_TOKEN_EMPTY = 1300;

    /*
     * 短信和电话相关错误
     */
    const ERR_CAPTCHA_OVER_QUOTA = 1400;

    /*
     * 优惠券活动相关错误
     */
    const ERR_COUPON_NOT_EXIST        = 1500;
    const ERR_COUPON_STATUS_NOT_MATCH = 1501;
    const ERR_COUPON_EXPIRED          = 1502;
    const ERR_COUPON_HAS_BEEN_GET     = 1503;


	/**
     * 其他错误: 10000以上，由具体业务自定义
     */
    const ERR_OTHER_ERROR = 10000;					//其他错误
}
