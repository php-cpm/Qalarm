<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Log\Writer as Logger;
use Monolog\Handler\StreamHandler;
use ReflectionClass;

abstract class BaseMonitorErrorLog extends Command
{
    protected $signature = 'plcommon:monitor_error_log';

    protected $description = '监控错误日志，并实时报警';

    // 记录文件
    protected $recordFile = '/app/MonitorErrorLog.json';

    // 记录数据
    protected $records;

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

    // 报告错误
    abstract protected function sendReport($timestr, $name, $message, $lines);

    public function handle(Logger $logger)
    {
        $this->loadRecords();

        // 添加新日志文件
        $logfiles = $this->getLogFiles($logger);
        foreach ($logfiles as $file) {
            if (!isset($this->records[$file])) {
                $this->records[$file] = ['inode' => 0, 'offset' => 0];
            }
        }

        // 处理日志文件
        foreach ($this->records as $file => &$record) {
            // 检查文件是否存在
            if (!file_exists($file)) {
                unset($this->records[$file]);
                continue;
            }

            // 检查inode
            $stat = stat($file);
            if ($stat['ino'] != $record['inode'] || $stat['size'] < $record['offset']) {
                $record['inode'] = $stat['ino'];
                $record['offset'] = 0;
            }

            // 打开并调整偏移量
            $this->info(sprintf('Log file processed %s@%d', $file, $record['offset']));
            $fp = fopen($file, 'r');
            fseek($fp, $record['offset']);

            // 处理文件
            $this->process($fp);

            // 保存偏移量
            $record['offset'] = ftell($fp);
        }

        $this->saveRecords();
    }

    // 获取需要监控的日志文件
    protected function getLogFiles(Logger $logger)
    {
        $files = [];

        // PHP error_log
        $files[] = ini_get('error_log');

        // 获取Monolog的日志文件
        // foreach ($logger->getMonolog()->getHandlers() as $handler) {
        //     if ($handler instanceof StreamHandler) {
        //         $reflection = new ReflectionClass(StreamHandler::class);
        //         $property = $reflection->getProperty('url');
        //         $property->setAccessible(true);
        //         $logfile = $property->getValue($handler);
        //         $files[] = $logfile;
        //     }
        // }

        // 过滤掉不存在的文件
        $files = array_filter($files, function ($value) {
            return !empty($value);
        });

        return $files;
    }

    // 加载记录文件
    protected function loadRecords()
    {
        $this->records = [];

        $file = $this->laravel->storagePath().$this->recordFile;

        if (!file_exists($file)) {
            return;
        }

        $raw = file_get_contents($file);
        $record = json_decode($raw, true);
        if (!is_array($record)) {
            return;
        }

        $this->records = $record;
    }

    // 写入记录文件
    protected function saveRecords()
    {
        $file = $this->laravel->storagePath().$this->recordFile;

        $raw = json_encode($this->records);

        file_put_contents($file, $raw, LOCK_EX);
    }

    // 处理当前打开文件
    protected function process($fp)
    {
        $error = null;
        while (!feof($fp)) {
            $line = trim(fgets($fp));
            if (!$line) {
                continue;
            }

            // 处理日志中首行
            if (preg_match('/^\[(.+?)\]\s*(.+?):\s*(.+)/', $line, $match)) {
                if (!is_null($error)) {
                    $this->report($error);
                }

                $error = [
                    'timestr' => $match[1],
                    'name' => $match[2],
                    'message' => $match[3],
                    'lines' => [$line],
                ];

                // 解析level
                if (preg_match('/^(\w+)\.(\w+)$/', $error['name'], $match)) {
                    $error['level'] = strtolower($match[2]);
                } else {
                    $error['level'] = 'error';
                }
            } elseif (!is_null($error)) {
                $error['lines'][] = $line;
            }
        }

        // Report
        if (!is_null($error)) {
            $this->report($error);
        }
    }

    // 报告该错误
    protected function report($error)
    {
        // 检查报告级别
        $level = $error['level'];
        if (!isset($this->levels[$level]) || !$this->levels[$level]) {
            return;
        }

        $this->comment(sprintf('report [%s] %s', $error['name'], $error['message']));

        $this->sendReport($error['timestr'], $error['name'], $error['message'], $error['lines']);
    }
}
