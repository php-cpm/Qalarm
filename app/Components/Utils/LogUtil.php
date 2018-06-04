<?php
namespace App\Components\Utils;

use Monolog;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Illuminate\Log\Writer;

use App\Components\Utils\QAlarmUtil;

class LogUtil
{
    const LOG_ERROR       = 'error';
    const LOG_RECORD      = 'record';
    const LOG_JOB         = 'job';
    const LOG_DEFAULT     = 'qalarm';

    protected static $loggers = array();

    public static function getLogger($file)
    {
        if (empty(self::$loggers[$file])) {
            $log =  new Writer(new Logger($file));  
            $log->useDailyFiles(
                app()->storagePath().'/logs/'.$file.'.log', 
                app()->make('config')->get('app.log_max_files', 15)
            );
            self::$loggers[$file] = $log;
        }

        $log = self::$loggers[$file];

        return $log;
    }

    public static function getLoggerInstancesFiles()
    {
        $class = new \ReflectionClass("App\Components\Utils\LogUtil");
        $consts = $class->getConstants();
        $logFiles = [];
        foreach ($consts as $const => $log) {
            if (strpos($const, "LOG_") !== false) {
                $logFiles[] = $log;
            }
        }

        return $logFiles;
    }

    public static function debug($errmsg, $context, $file = self::LOG_DEFAULT)
    {
        $msg = self::fullMsg($errmsg);
        self::getLogger($file)->debug($msg, $context);
    }
    
    public static function info($errmsg, $context, $file = self::LOG_DEFAULT) 
    {
        $msg = self::fullMsg($errmsg);
        self::getLogger($file)->info($msg, $context);
    }

    public static function notice($errmsg, $context, $file = self::LOG_DEFAULT) 
    {
        $msg = self::fullMsg($errmsg);
        self::getLogger($file)->notice($msg, $context);
    }

    public static function warning($errmsg, $context, $file = self::LOG_DEFAULT)
    {
        $msg = self::fullMsg($errmsg);
        self::getLogger($file)->warning($msg, $context);
    }

    public static function error($errmsg, $context, $errorCode = 100, $file = self::LOG_ERROR)
    {
        $msg = self::fullMsg($errmsg);
        self::getLogger($file)->error($msg, $context);
        QAlarmUtil::send('exception', $errorCode, $errmsg .json_encode($context), '', '', $msg);
    }
    
    public static function critical($errmsg, $context, $QalarmModel = '', $errorCode = 100, $file = self::LOG_ERROR) 
    {
        $msg = self::fullMsg($errmsg);
        self::getLogger($file)->critical($msg, $context);
        QAlarmUtil::send('exception', $errorCode, $errmsg .json_encode($context), '', '', $msg);
    }


    private static function fullMsg($errmsg = '')
    {
        $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = array_merge($stack[1], $stack[2]);
        $callClass = $caller['class'];
        $callLine  = $caller['line'];

        return "[$callClass:$callLine] "."[$errmsg]";
    }
}
