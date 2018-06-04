<?php
namespace App\Components\Workflow;

use App\Components\Utils\LogUtil;
use app\Models\Common\Mail;
use App\Job\MailJob;
use App\Models\Gaea\Workflow;
use App\Models\Gaea\AdminApp;

class MarketAppHandler  extends WorkflowHandler
{
    public function done($workflow) 
    {
        $adminApp = AdminApp::where('admin_id', $workflow->admin_id)
            ->where('app_id', $workflow->buss_id)
            ->get()
            ->first();
        if ($adminApp != null) return;

        $adminApp = new AdminApp;
        {
            $adminApp->admin_id = $workflow->admin_id;
            $adminApp->app_id   = $workflow->buss_id;
        }
        $adminApp->save();

        parent::done($workflow);
    }
}
