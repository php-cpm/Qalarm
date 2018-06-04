<?php

namespace App\Components\Alarm;

use App\Components\Utils\HttpUtil;              
use Log;

class Alertor
{
    private static $defaultStrategy = [
        'limit'   => '1,1,1',
        'period'  => '0-24',
        'watchers'=> 'chenfei60',
    ];

    private static $watchers = [
        'chenfei60'      => ['mobile' => '13658364971', 'mail' => 'chenfei60@wanda.cn'],
        'zhangkaihong3'  => ['mobile' => '15652918035', 'mail' => 'chenfei60@wanda.cn'],
        'zouyi6'         => ['mobile' => '18612260713', 'mail' => 'chenfei60@wanda.cn'],
        'zhagndawei6'    => ['mobile' => '18610193428', 'mail' => 'chenfei60@wanda.cn'],
        'gengjun3'       => ['mobile' => '15026912738', 'mail' => 'chenfei60@wanda.cn'],
        'zhangjianshan'  => ['mobile' => '13520641864', 'mail' => 'chenfei60@wanda.cn'],
        'liyanbao'       => ['mobile' => '15210687831', 'mail' => 'chenfei60@wanda.cn'],
        'zhangyongwei1'  => ['mobile' => '13520093035', 'mail' => 'chenfei60@wanda.cn'],
        'yinhongbo'      => ['mobile' => '13552348060', 'mail' => 'chenfei60@wanda.cn'],
        'liyanbao'       => ['mobile' => '15210687831', 'mail' => 'chenfei60@wanda.cn'],
        'xulei57'        => ['mobile' => '13811399140', 'mail' => 'chenfei60@wanda.cn'],
        'xiegang1'       => ['mobile' => '15810287550', 'mail' => 'chenfei60@wanda.cn'],
        'lijing151'      => ['mobile' => '13601310087', 'mail' => 'chenfei60@wanda.cn'],
        'liminghao8'     => ['mobile' => '13051562135', 'mail' => 'chenfei60@wanda.cn'],
    ];
    
    private static $groups = [
        'g_xapi_mop'     => 'zouyi6,zhagndawei6,gengjun3,zhangjianshan,liyanbao,zhangkaihong3,yinhongbo',
        'g_shake'        => 'liyanbao,zhangyongwei1,zhangkaihong3,yinhongbo',
        'g_xapi'         => 'xulei57,xiegang1,lijing151',
    ];
    
    // 监控维度：项目->模块|错误码|环境, 报警策略使用最长匹配法, watchers为树路径上的所有人
    // limit：连续x次y分钟内超过z次, xyz为最小为1的整数值
    // peroid: 通知报警的时间段，精度为小时
    // watchers: 报警人，可以是组和单个人的组合
    private static $alarmConfigs =  [
        'xapi_mop'   =>   [
            '||'                     => ['limit'  => '1,1,1', 'period' => '0-24', 'watchers'=> 'g_xapi_mop',],
            'mysql|200000|'          => ['limit'  => '1,1,1', 'period' => '0-24', 'watchers'=> 'chenfei60',],
            'code|200000|sit'        => ['limit'  => '1,1,5', 'period' => '0-24', 'watchers'=> '',],
            'code|200001|'           => ['limit'  => '1,1,1', 'period' => '0-24', 'watchers'=> 'g_xapi_mop',],
            'code|100002|'           => ['limit'  => '1,1,1', 'period' => '0-24', 'watchers'=> 'g_xapi_mop',],
            'code|100002|'           => ['limit'  => '1,1,1', 'period' => '0-24', 'watchers'=> 'g_xapi_mop',],
            'code|100003|'           => ['limit'  => '1,1,1', 'period' => '0-24', 'watchers'=> 'g_xapi_mop',],
            'code|100004|'           => ['limit'  => '1,1,1', 'period' => '0-24', 'watchers'=> 'g_xapi_mop',],
            'code|000000|'           => ['limit'  => '1,1,1', 'period' => '0-24', 'watchers'=> 'g_xapi_mop',],
        ],
        'shake'     =>   [
            '||'                     => ['limit' => '1,1,10', 'period' => '0-24', 'watchers'=> 'g_shake',],
            'codec|00003|'           => ['limit' => '1,1,10', 'period' => '0-24', 'watchers'=> 'g_shake',],
            'codec|00005|'           => ['limit' => '1,1,10', 'period' => '0-24', 'watchers'=> 'g_shake',],
        ],
        'qalarm'    =>   [
            '||'                     => ['limit' => '1,1,10', 'period' => '0-24', 'watchers'=> 'chenfei60',],
            'heartbeat||'            => ['limit' => '1,1,10000', 'period' => '0-24', 'watchers'=> 'chenfei60',],
            'sdk||'                  => ['limit' => '1,1,1', 'period' => '0-24', 'watchers'=> 'chenfei60',],
        ],
        'xapi'    =>   [
            '||'                     => ['limit' => '1,1,2', 'period' => '0-24', 'watchers'=> 'g_xapi',],
        ],
        'ha_redis'    =>   [
            '||'                     => ['limit' => '1,1,1', 'period' => '0-24', 'watchers'=> 'liminghao8',],
        ],
    ];

    public static function getMobiles($strategys)
    {/*{{{*/
        $mobiles = [];

        $rawList = [];
        // 如果有1个以上的策略，则不用给默认的管理人员报警
        if (count($strategys) > 1) {
            array_shift($strategys);
        }
        foreach ($strategys as $strategy) {
            $rawList = array_merge($rawList, explode(',', $strategy['watchers']));
        }
        $rawList = array_unique($rawList);

        // 首先转换组成员
        $list    = [];
        foreach ($rawList as $member) {
            if (isset(self::$groups[$member])) {
                $list = array_merge($list, explode(',', self::$groups[$member]));
            } else {
                $list[] = $member;
            }
        }

        foreach ($list as $watcher) {
            if (isset(self::$watchers[$watcher])) {
                $mobiles[] = self::$watchers[$watcher]['mobile'];
            }
        }

        return $mobiles;
    }/*}}}*/

    /**
        * @brief getStrategy 报警策略使用最长匹配法, watchers为树路径上的所有人
        *
        * @param $project
        * @param $item
        *
        * @return 
     */
    public static function getStrategys($project, $item)
    {/*{{{*/
        $strategys[]  = self::$defaultStrategy;
        $strategyGroup = isset(self::$alarmConfigs[$project]) ? self::$alarmConfigs[$project] : null;

        if ($strategyGroup != null) {
            list ($module, $code, $env) = explode('|', $item);

            // 项目默认配置
            $strategy = self::getStrategy($project, sprintf('%s|%s|%s', '', '', ''));
            if ($strategy != null) {
                $strategys[] = $strategy;
            }

            // 项目模块配置
            $strategy = self::getStrategy($project, sprintf('%s|%s|%s', $module, '', ''));
            if ($strategy != null) {
                $strategys[] = $strategy;
            }

            // 项目错误码配置
            $strategy = self::getStrategy($project, sprintf('%s|%s|%s', $module, $code, ''));
            if ($strategy != null) {
                $strategys[] = $strategy;
            }

            // 匹配到最长路径
            $strategy = self::getStrategy($project, sprintf('%s|%s|%s', $module, $code, $env));
            if ($strategy != null) {
                $strategys[] = $strategy;
            }
        }

        return $strategys;
    }/*}}}*/

    public static function getLimitValues($strategy)
    {/*{{{*/
        return explode(',', $strategy['limit']);  
    }/*}}}*/

    private static function getStrategy($project, $item)
    {/*{{{*/
        if (isset(self::$alarmConfigs[$project][$item])) {
            return self::$alarmConfigs[$project][$item];
        }

        return null;
    }/*}}}*/
}
