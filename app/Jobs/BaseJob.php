<?php
namespace App\Jobs;

use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use DB;
use Illuminate\Support\Facades\Redis;

use App\Components\Utils\LogUtil;

use App\Jobs\Job;

abstract class BaseJob extends Job implements SelfHandling, ShouldQueue
{
    use DispatchesJobs;

    use InteractsWithQueue, SerializesModels;

    const JOB_REDIS_KEY_PREFIX = 'laravel_job';

    protected $jobUniqueId;

    protected $jobOBJ;

    public function __construct($object)
    {
        // 如果参数为数组，则转换成对象
        $this->jobOBJ = $object;

        if (is_array($object)) {
            $object = (object)$object;
        }
        $this->jobUniqueId = join('_', [self::JOB_REDIS_KEY_PREFIX, get_class($this), $object->job_id]);
    }

    private function lock()
    {
        // 防止源头灌入重复任务
        if ($this->attempts() ==  1) {
            if (Redis::get($this->jobUniqueId) != null) {
                return false;
            }

            Redis::set($this->jobUniqueId, '1');
            Redis::expire($this->jobUniqueId, 86400);

            return true;
        }

        return true;
    }

    private function unlock()
    {
        Redis::del($this->jobUniqueId);
    }

    // 输出追踪用日志
    protected function track($msg, $data = [])
    {
        LogUtil::info(sprintf('%s %s %s', static::class, $this->jobUniqueId, $msg), [$data], LogUtil::LOG_JOB);
    }

    // 实际的处理方法
    public function handle()
    {
        // if ($this->lock() == false) {
        //     $this->track('The same job is running');
        // }
        $this->track('start', (array)$this->jobOBJ);

        $this->doHandle();

        // $this->unlock();
        $this->track('done', (array)$this->jobOBJ);
    }
}
