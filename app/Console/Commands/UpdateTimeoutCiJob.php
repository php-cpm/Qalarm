<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Log;
use App\Components\Utils\LogUtil;
use Carbon\Carbon;

use App\Models\Gaea\CiBuildProject;
use App\Models\Gaea\CiBuildProjectLog;

class UpdateTimeoutCiJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gaea:update_cijob';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '修改超时的CI_Job状态';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {/*{{{*/
        //LogUtil::info('schedul', ['测试数据 输出']);
        //$this->info('Display this on the screen');
        $ciJobTimeOut = 5;
        $ciBuildJobLogs = CiBuildProjectLog::where('status', '<>', 'SUCCESS')
            ->where('status','<>', 'FAILURE')->get();

        //dd($ciBuildJobLogs);
        foreach ($ciBuildJobLogs as $buildJob) {
            $nowDate = date('Y-m-d H:m:s', time());
            $cle = strtotime($nowDate) - strtotime($buildJob->created);
            //超过构建时间；设置失败
            if ($cle > $ciJobTimeOut) {
                $buildJob->status           = 'FAILURE';
                //$buildJob->finished         = date('Y-m-d H:m:s', time());
                $buildJob->finished         = Carbon::now();
                $buildJob->log              = 'CI job runing is timeout !!';
                //$buildRow->jenkins_job_name = $jenkinsJobName;
                $buildJob->save();

                $buildProject = CiBuildProject::where('gaea_build_id', '=', $buildJob->gaea_build_id)->first();
                if ($buildProject != null) {
                    $buildProject->status           = 'FAILURE';
                    $buildProject->finishtime          = date('Y-m-d h:m:s', time());
                    $buildProject->save();
                }
            }
        }
    }/*}}}*/
}
