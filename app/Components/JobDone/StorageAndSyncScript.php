<?php
namespace App\Components\JobDone;

use Log;
use Redis;
use Carbon\Carbon;
use App\Components\Utils\LogUtil;
use App\Models\Gaea\OpsScripts;

class StorageAndSyncScript
{

    const RSYNC      = '/usr/bin/rsync -altv';
    const RSYNC_USER = 'ttyc';

    public static function storageAndSync($script)
    {

        $base = storage_path('app');

        $ownerPath = sprintf('%s/%s', $base, $script->owner);
        $dirPath   = sprintf('%s/%s/%s', $base, $script->owner, $script->scriptdir);
        $filePath  = sprintf('%s/%s/%s/%s', $base, $script->owner, $script->scriptdir, $script->scriptname);

        if (!file_exists($ownerPath)) {
            mkdir($ownerPath);
        }
        if (!file_exists($dirPath)) {
            mkdir($dirPath);
        }

        // write
        $byte = file_put_contents($filePath, $script->script, LOCK_EX);
        if ($byte === false) {
            LogUtil::error('写入脚本失败', ['ojb'=>$script], LogUtil::LOG_JOB);
            return false;
        }
        
        $md5 = exec("/usr/bin/md5sum $filePath |awk '{print $1}'");
        if ($md5 != $script->md5) {
            LogUtil::error('写入脚本md5校验失败', ['ojb'=>$script], LogUtil::LOG_JOB);
            return false;
        }

        // rsync file
        $cmd =  sprintf('%s --timeout=10 %s/* %s@%s::%s', 
            self::RSYNC, $ownerPath, self::RSYNC_USER, env('OPS_SCRIP_STORAGE_HOST'), OpsScripts::$scriptStorageDir[$script->owner]['rsyncname']);

        LogUtil::info('同步脚本命令', ['rsync_cmd'=>$cmd], LogUtil::LOG_JOB);

        exec($cmd, $res, $ret);
        if ($ret == 0) {
            LogUtil::info('脚本同步成功', ['ojb'=>$script], LogUtil::LOG_JOB);
        } else {
            LogUtil::error('脚本同步失败', ['ojb'=>$script, 'res' => $res], LogUtil::LOG_JOB);
            return false;
        }

        return true;
    }
}
