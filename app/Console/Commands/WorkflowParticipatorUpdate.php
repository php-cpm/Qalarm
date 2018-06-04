<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;

use App\Models\Gaea\Workflow;
use App\Models\Gaea\WorkflowParticipator;

class WorkflowParticipatorUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gaea:workflowparticipatorupdate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新工作流处理人';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {/*{{{*/
        foreach (Workflow::$workflow as $type => $handler) {
            // 如果有相关子类，则扫描添加
            if (isset($handler['buss'])) {
                $class = $handler['buss'];
                $objects = $class::get();

                foreach ($objects as $obj) {
                    $participator = WorkflowParticipator::where('workflow_type', $type)
                        ->where('workflow_name', $handler['name'])
                        ->where('workflow_handler', $handler['handler'])
                        ->where('buss_id', $obj->id)
                        ->first();

                    if (is_null($participator)) {
                        $participator = new WorkflowParticipator();
                        {
                            $participator->workflow_type        = $type;
                            $participator->workflow_name        = $handler['name'];
                            $participator->workflow_handler     = $handler['handler'];
                            $participator->buss_id              = $obj->id;
                            $participator->buss_name            = $obj->name;
                            $participator->participator         = join(',', Workflow::$defaultWFOps);
                        }
                        $participator->save();
                    }
                }
            } else {
                // 没有相关子类
                $participator = WorkflowParticipator::where('workflow_type', $type)
                    ->where('workflow_name', $handler['name'])
                    ->where('workflow_handler', $handler['handler'])
                    ->first();
                // 不存在才添加
                if (is_null($participator)) {
                    $participator = new WorkflowParticipator();
                    {
                        $participator->workflow_type        = $type;
                        $participator->workflow_name        = $handler['name'];
                        $participator->workflow_handler     = $handler['handler'];
                        $participator->buss_id              = 0;
                        $participator->buss_name            = '';
                        $participator->participator         = join(',', Workflow::$defaultWFOps);
                    }
                    $participator->save();
                }

            }
        }
    }/*}}}*/
}
