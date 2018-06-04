<?php
namespace App\Components\Workflow;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Bus\DispatchesJobs;

use App\Components\Utils\LogUtil;
use App\Components\Utils\MethodUtil;
use App\Models\Common\Mail;
use App\Jobs\DindinJob;
use App\Models\Gaea\Workflow;
use App\Models\Gaea\AdminUser;

class WorkflowHandler
{
    use DispatchesJobs; 

    private $systemName = '[gaea]';

    public function apply($workflow)
    {/*{{{*/
        // 通知用户
        $dindin = [];
        $attachment      = json_decode($workflow->attachment, true); 
        $dindin['content'] = sprintf('您于 %s 申请了 %s，已分配给 %s 处理，请耐心等待操作人员处理。', 
                $workflow->created_at, 
                Workflow::$workflow[$workflow->type]['name'] .'-'.$attachment['wf_desc'],
                $workflow->alloc_name
        );
        $dindin['mobiles'] = $workflow->admin->mobile;

        $jobData = MethodUtil::assemblyJobData($dindin);
        $job  = new DindinJob($jobData);
        $this->dispatch($job);

        // 通知操作者
        $dindin['content']   = sprintf('%s 在 %s 申请了 %s，请尽快处理, http://gaea.ttyongche.com/。',
            $workflow->admin->nickname,
            $workflow->created_at, 
            Workflow::$workflow[$workflow->type]['name'] .'-'.$attachment['wf_desc']
        );
        $dindin['mobiles'] = $workflow->ops->mobile;
        $jobData = MethodUtil::assemblyJobData($dindin);
        $job  = new DindinJob($jobData);
        $this->dispatch($job);
    }/*}}}*/

    public function doing($workflow)
    {/*{{{*/
        $dindin = [];
        $attachment      = json_decode($workflow->attachment, true); 
        $dindin['content'] = sprintf('您于 %s 申请了 %s， %s 正在处理中...',
            $workflow->created_at, 
            Workflow::$workflow[$workflow->type]['name'] .'-'.$attachment['wf_desc'],
            $workflow->alloc_name
        );
        $dindin['mobiles'] = $workflow->admin->mobile;

        $jobData = MethodUtil::assemblyJobData($dindin);
        $job  = new DindinJob($jobData);
        $this->dispatch($job);
    }/*}}}*/

    public function done($workflow)
    {/*{{{*/
        $dindin = [];
        $attachment      = json_decode($workflow->attachment, true); 
        $dindin['content'] = sprintf('您于 %s 申请了 %s，已经成功处理完成，可以放心使用。',
            $workflow->created_at, 
            Workflow::$workflow[$workflow->type]['name'] .'-'.$attachment['wf_desc']
        );
        $dindin['mobiles'] = $workflow->admin->mobile;

        $jobData = MethodUtil::assemblyJobData($dindin);
        $job  = new DindinJob($jobData);
        $this->dispatch($job);
    }/*}}}*/

    public function failed($workflow)
    {/*{{{*/
        $dindin = [];
        $attachment      = json_decode($workflow->attachment, true); 
        $dindin['content'] = sprintf('您于 %s 申请了 %s，处理失败了，失败原因:%s， 如有疑问请联系服务提供者。',
            $workflow->created_at, 
            Workflow::$workflow[$workflow->type]['name'] .'-'.$attachment['wf_desc'],
            $workflow->remark
        );
        $dindin['mobiles'] = $workflow->admin->mobile;

        $jobData = MethodUtil::assemblyJobData($dindin);
        $job  = new DindinJob($jobData);
        $this->dispatch($job);
    }/*}}}*/

    public function reback($workflow)
    {/*{{{*/
        $dindin = [];
        $attachment      = json_decode($workflow->attachment, true); 
        $dindin['content']   = sprintf('您于 %s 申请了 %s，没有审核通过，不通过原因:%s， 如有疑问请联系服务提供者。',
            $workflow->created_at, 
            Workflow::$workflow[$workflow->type]['name'] .'-'.$attachment['wf_desc'],
            $workflow->remark
        );

        $dindin['mobiles'] = $workflow->admin->mobile;

        $jobData = MethodUtil::assemblyJobData($dindin);
        $job  = new DindinJob($jobData);
        $this->dispatch($job);
    }/*}}}*/

    public function notice($username, $content)
    {/*{{{*/
        $dindin = [];

        $user = AdminUser::where('username', $username)->first();
        $dindin['content'] = $content;
        $dindin['mobiles'] = $user->mobile;

        $jobData = MethodUtil::assemblyJobData($dindin);
        $job  = new DindinJob($jobData);
        $this->dispatch($job);
    }/*}}}*/
}
