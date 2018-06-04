<?php
namespace App\Console\Commands;

use Cache;
use DB;
use Illuminate\Console\Command;
use Log;
use Redis;
use App\Components\QAlarm;

class MonitorInfraService extends Command
{
    protected $signature = 'ttyc:monitor_infra_service';

    protected $description = '监控基础架构服务的连接状态';

    // 记录测试失败
    protected function fail($module, $msg, $extra = [])
    {
        $this->error(sprintf('[Fail] %s %s %s', $module, $msg, json_encode($extra)));

        return false;
    }

    // 记录测试成功
    protected function success($module, $extra = [])
    {
        $this->info(sprintf('[Success] %s %s', $module, json_encode($extra)));

        return true;
    }

    public function handle()
    {
        // Redis
        foreach (config('database.redis') as $key => $value) {
            if (in_array($key, ['cluster'])) {
                continue;
            }
            $this->checkRedis($key);
        }

        // Database
        foreach (config('database.connections') as $key => $value) {
            // MySQL
            if ($value['driver'] == 'mysql') {
                $this->checkMysql($key, $value);
            }

            // MongoDB
            elseif ($value['driver'] == 'mongodb') {
                $this->checkMongo($key, $value);
            }
        }

        // Memcache
        if (Cache::getDefaultDriver() == 'memcached') {
            $this->checkMemcache(config('cache.stores.memcached'));
        }
    }

    // 检查Redis
    protected function checkRedis($name)
    {
        $client = Redis::connection($name);

        // Ping
        $status = $client->ping();
        if ($status->getPayload() != 'PONG') {
            return $this->fail('redis', 'ping invalid response', [
                'name' => $name,
                'status' => $result,
            ]);
        } else {
            return $this->success('redis', [
                'name' => $name,
            ]);
        }
    }

    // 检查MySQL
    protected function checkMysql($name, $config)
    {
        $clients = [];
        if (isset($config['write'])) {
            $clients[$name.'::write'] = DB::connection($name.'::write');
            $clients[$name.'::read'] = DB::connection($name.'::read');
        } else {
            $clients[$name] = DB::connection($name);
        }

        // Execute query
        $sql = 'select 1 as fine';
        foreach ($clients as $name => $client) {
            $result = $client->selectOne($sql);
            $this->success('mysql', ['name' => $name]);
        }
    }

    // 检查MongoDB
    protected function checkMongo($name, $config)
    {
        $client = DB::connection($name);
        $db = $client->getMongoDB();

        // Ping
        $result = $db->command([
            'ping' => 1,
        ]);
        $this->success('mongodb', ['name' => $name]);
    }

    // 检查Memcache
    protected function checkMemcache($config)
    {
        $count = count($config['servers']);
        $count *= 10; // 确保尽可能hash到每个server

        for ($i = 0; $i < $count; $i++) {
            $key = 'dummy_ping_'.$i;
            Cache::forever($key, '');
            Cache::forget($key, '');
        }
        $this->success('memcached', [
            'servers' => $config['servers'],
        ]);
    }
}
