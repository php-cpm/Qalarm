<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Log\Writer as Logger;
use Monolog\Handler\RotatingFileHandler;
use App\Components\Utils\LogUtil;
use ReflectionClass;

class GenerateLogFile extends Command
{
    protected $signature = 'plcommon:generate_log_file';

    protected $description = '生成日志文件，并更新权限';

    public function handle()
    {
        $files = [];

        $logNames = LogUtil::getLoggerInstancesFiles();

        foreach ($logNames as $log) {
            $logger = LogUtil::getLogger($log);

            // 获取Monolog的日志文件
            foreach ($logger->getMonolog()->getHandlers() as $handler) {
                var_dump($logger->getMonolog()->getHandlers());
                if ($handler instanceof RotatingFileHandler) {
                    $reflection = new ReflectionClass(RotatingFileHandler::class);

                    // 日志文件路径
                    $property = $reflection->getProperty('url');
                    $property->setAccessible(true);
                    $logfile = $property->getValue($handler);

                    // 文件名日期格式
                    $property = $reflection->getProperty('dateFormat');
                    $property->setAccessible(true);
                    $format = $property->getValue($handler);

                    // 替换文件名
                    $today = date($format);
                    $tomorrow = date($format, time() + 86400);
                    $logfile = str_replace($today, $tomorrow, $logfile);

                    $files[] = $logfile;
                }
            }

            if (!$files) {
                $this->info('No file touched');

                return;
            }

            foreach ($files as $logfile) {
                // 新建文件
                touch($logfile);
                $this->info(sprintf('Log file %s touched', $logfile));

                // 调整权限
                chmod($logfile, 0666);
                $this->info(sprintf('Log file %s permission=666', $logfile));
            }
        }
    }
}
