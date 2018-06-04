<?php
namespace App\Jobs;

use App\Models\Qalarm\Module;
use App\Models\Qalarm\Project;
use Log;


use Carbon\Carbon;

use App\Components\Utils\LogUtil;
use App\Components\Utils\MethodUtil;
use App\Models\Qalarm\Message;
use Illuminate\Support\Facades\Redis;

class RecordMessageJob extends BaseJob
{
    protected $object;

    public function __construct($obj) 
    {
        $this->object = $obj;
        parent::__construct($obj);
    }

    public function doHandle()
    {
        $payload = $this->object['payload'];

        // tyep=m: {"project":"xapi_mod","module":"code","code":"200000","env":"prod","time":1467352887,"server_ip":"willasdeMacBook-Pro.local","client_ip":"172.0.0.1","script":"\/Users\/willas\/Src\/Qalarm-php-sdk\/src\/test.php:10","url":"test.php","post_data":[],"cookie":[],"type":"m"}
        $project = $payload['project'];
        $module  = $payload['module'];
        $code    = $payload['code'];
        $env     = $payload['env'];

        // 模块加上环境
        $module .= '-'.$env;

        $projectMessageDetailKey       = join('|', ['project', $project]);
        $projectModuleMessageDetailKey = join('|', ['module', $project, $module]);

        $encode = serialize($payload);
        Redis::lpush($projectMessageDetailKey, $encode);
        Redis::ltrim($projectMessageDetailKey, 0, 2048);

        Redis::lpush($projectModuleMessageDetailKey, $encode);
        Redis::ltrim($projectModuleMessageDetailKey, 0, 2048);

        $model = new Message();
        {
            $model->project   = $payload['project'];
            $model->module    = $module;
            $model->code      = $payload['code'];
            $model->env       = $payload['env'];
            $model->time      = Carbon::createFromTimestamp($payload['time']);
            $model->server_ip = $payload['server_ip'];
            $model->client_ip = $payload['client_ip'];
            $model->script    = $payload['script'];
            $model->message   = $payload['message'];
        }
        $model->save();


        // 只添加线上环境的模块配置
        if ($env != 'prod') {
            return true;
        }

        // 更新模块信息
        $projectInstance = Project::where('name', $project)->first();
        if (is_null($projectInstance)) {
            return true;
        }

        $moduleInstance = Module::where('project_id', $projectInstance->id)
            ->where('module',  $module)
            ->first();

        if (is_null($moduleInstance)) {
            $moduleInstance = new Module();
            {
                $moduleInstance->project_id = $projectInstance->id;
                $moduleInstance->module     = $module;
                $moduleInstance->monitors   = '';
                $moduleInstance->strategy_id= 0;
            }
            $moduleInstance->save();
        }

        return true;
    }
}

