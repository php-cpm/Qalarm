<?php

namespace App\Console;
use Log;
use App\Components\Utils\LogUtil;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    public function __construct(Application $app, Dispatcher $events)
    {
        /**
         * 目前Api项目的cli程序都运行在web机
         * 所以在cli初始化的时候设置内存上限
         */
        ini_set('memory_limit', '1024m');

        parent::__construct($app, $events);
    }

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\MonitorErrorLog::class,

        \App\Console\Commands\GenerateLogFile::class,
        \App\Console\Commands\UserMobileUpdate::class,
        \App\Console\Commands\WorkflowParticipatorUpdate::class,
        \App\Console\Commands\UpdateTimeoutCiJob::class,
        \App\Console\Commands\SyncGitUser::class,
        \App\Console\Commands\SetGitWebHook::class,
        Commands\KafkaConsumer::class,
        Commands\KafkaCommitOffset::class,
        Commands\PageMonitorKafkaConsumer::class,
        Commands\ManualConsumer::class,
        Commands\LocalFileConsumer::class,
        Commands\MergeCount::class,
        Commands\MonitorUnavailableHost::class,
        Commands\UpdateCtx::class,
        Commands\CheckRedisMaster::class,
        Commands\GeneratePageJobs::class,
        Commands\PageFFanIndeGenerate::class,
        Commands\Test::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
//        $schedule->command('plcommon:monitor_error_log')->everyMinute()->withoutOverlapping();
//        $schedule->command('ttcommon:monitor_queue_backlog 10')->everyMinute();
//
//        $schedule->command('plcommon:generate_log_file')->dailyAt('23:55');
//        $schedule->command('gaea:usermobile')->dailyAt('23:55');
//        $schedule->command('gaea:update_cijob')->everyFiveMinutes()->withoutOverlapping();
//
//        $schedule->command('gaea:sync_git_user')->everyFiveMinutes()->withoutOverlapping();
//        $schedule->command('gaea:set_git_webhook')->hourly()->withoutOverlapping();

        if (exec('hostname') == 'CDVM-213025131') {
//            $schedule->command('page:index')->everyFiveMinutes();
            $schedule->command('alarm:unavailable_host')->hourly()->withoutOverlapping();
        }
    }
}
