<?php

namespace App\Console\Commands;

use App\Components\Utils\LogUtil;

class MonitorErrorLog extends BaseMonitorErrorLog
{
    // 级别控制
    protected $levels = [
        'debug' => false,
        'info' => false,
        'notice' => false,
        'warning' => true,
        'error' => true,
        'critical' => true,
        'alert' => true,
        'emergency' => true,
    ];

    protected function sendReport($timestr, $name, $message, $lines)
    {
        LogUtil::error('MonitorPhpErrorLogreport', ['time' => $timestr, 'name' => $name, 'message' => $message, 'error' => implode("\n", $lines)], LogUtil::LOG_ERROR); 
    }
}
