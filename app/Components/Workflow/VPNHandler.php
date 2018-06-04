<?php
namespace App\Components\Workflow;

use App\Components\Utils\LogUtil;
use app\Models\Common\Mail;
use App\Job\MailJob;
use App\Models\Gaea\Workflow;

use App\Components\JobDone\JobDone;

class VPNHandler extends WorkflowHandler
{
    public function doing($workflow)
    {
        $attachment = json_decode($workflow->attachment, true);
        $params['username'] = $attachment['user'];
        $result = app('jobdone')->applyVPN($params);

        if ($result['errno'] == 0) {
            parent::doing($workflow);
        }

        return $result;
    }
}
