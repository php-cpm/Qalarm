<?php
namespace App\Jobs;

use Log;
use App\Models\Qalarm\Alarm;

use Carbon\Carbon;

use App\Components\Utils\LogUtil;
use App\Components\Utils\MethodUtil;
use App\Models\Qalarm\Message;

class RecordAalrmJob extends BaseJob
{
    protected $object;

    public function __construct($obj) 
    {
        $this->object = $obj;
        parent::__construct($obj);
    }

    public function doHandle()
    {
        $message = $this->object;

        $model = new Alarm();
        {
            $model->time      = new Carbon();
            $model->message   = $message['message'];
            $model->receivor  = $message['receivors'];
            $model->project   = $message['project'];

        }
        $model->save();
        return true;
    }
}

