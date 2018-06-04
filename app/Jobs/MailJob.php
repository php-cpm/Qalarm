<?php
namespace App\Jobs;

use Log;
use Redis;
use Carbon\Carbon;
use App\Components\Utils\SmtpEmailUtil;
use App\Components\Utils\LogUtil;
use App\Models\Common\Mail;


class MailJob extends BaseJob
{
    public function __construct(Mail $mail)
    {
        $this->jobOBJ = $mail;
        parent::__construct($mail);
    }

    public function doHandle()
    {
        $result = SmtpEmailUtil::send($this->jobOBJ->to, $this->jobOBJ->title, $this->jobOBJ->content);
        if ($result == false) {
            LogUtil::error('邮件发送失败', 
                ['to' => $this->jobOBJ->to, 'title'=>$this->jobOBJ->title, 'content'=>$this->jobOBJ->content], 
                LogUtil::LOG_JOB);
            return false;
        }
        
        LogUtil::info('邮件发送', 
            ['to' => $this->jobOBJ->to, 'title'=>$this->jobOBJ->title, 'content'=>$this->jobOBJ->content], 
            LogUtil::LOG_JOB);
    }
}
