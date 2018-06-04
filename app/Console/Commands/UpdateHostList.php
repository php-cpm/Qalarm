<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Redis;

class UpdateHostList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature ='alarm:updatehost';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新全局机器列表';

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
    {
        $hostListKey = env('ALARM_HOSTLIST_REDIS_KEY');

        $hosts = [];
        $localHosts = [];
        $rawHosts = Redis::get($hostListKey);
        if ($rawHosts != null) {
            $hosts = json_decode($rawHosts, true);
        }

        $hostString = file_get_contents(storage_path('app/hosts'));
        $hostnames = explode("\n", $hostString);

        foreach ($hostnames as $host) {
            if (empty($host)) continue;
            $host = trim($host);
            $localHosts[$host] = ['host' => $host, 'time' => time()];
        }

        $allHosts = array_merge($localHosts, $hosts);

        Redis::set($hostListKey, json_encode($allHosts));
    }
}
