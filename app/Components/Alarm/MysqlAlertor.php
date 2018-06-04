<?php

namespace App\Components\Alarm;

use App\Components\Utils\HttpUtil;
use App\Models\Qalarm\Module;
use App\Models\Qalarm\Monitor;
use Log;
use App\Models\Qalarm\Project;

class MysqlAlertor
{
    private static $defaultStrategy = [
        'limit'  => [1,1,1],
        'period' => [0, 23],
        'sms'    => 1,
        'email'  => 1,
        'monitors' => 'chenfei60'
    ];

    public static function getMobiles($strategys)
    {/*{{{*/
        $mobiles = [];

        // 如果有1个以上的策略，则不用给默认的管理人员报警
        if (count($strategys) > 1) {
            array_shift($strategys);
        }

        foreach ($strategys as $strategy) {
            $monitors = Monitor::whereIn('username', explode(',', $strategy['monitors']))->get();
            foreach ($monitors as $monitor) {
                $mobiles[] = $monitor->mobile;
            }
        }

        $mobiles = array_unique($mobiles);

        return $mobiles;
    }/*}}}*/

    public static function getReceivors($strategys)
    {/*{{{*/
        $receivors = '';

        // 如果有1个以上的策略，则不用给默认的管理人员报警
        if (count($strategys) > 1) {
            array_shift($strategys);
        }

        foreach ($strategys as $strategy) {
            $receivors = $strategy['monitors'];
        }

        return $receivors;
    }/*}}}*/

    /**
     * @brief getStrategy 报警策略使用最长匹配法, watchers为树路径上的所有人
     *
        * @param $projectName
        * @param $item
        *
        * @return 
     */
    public static function getStrategys($projectName, $item)
    {/*{{{*/

        $strategys[] = self::$defaultStrategy;
        $project = Project::where('name', $projectName)
            ->first();
        if (is_null($project)) {
            return $strategys;
        }
        $s = $project->strategy->toArray();
        $s['monitors'] = join(',', [$project->monitor, $project->manager]);
        $strategys[] = $s;


        list($module, $code, $env) = explode('|', $item);
        $moduleModel = Module::where('project_id', $project->id)
            ->where('module', $module)
            ->first();

        if (is_null($moduleModel)) {
            return $strategys;
        }

        // 如果子模块关闭了报警,则不发送任何人
        if ($moduleModel->status == 0) {
            return [];
        }

        if (is_null($moduleModel->strategy)) {
            return $strategys;
        }

        $s = $moduleModel->strategy->toArray();
        $s['monitors'] = $moduleModel->monitors;
        $strategys[] = $s;

        return $strategys;
    }/*}}}*/

    public static function getLimitValues($strategy)
    {/*{{{*/
        return  $strategy['limit'];
    }/*}}}*/
}
