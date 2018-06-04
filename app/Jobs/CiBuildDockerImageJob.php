<?php
namespace App\Jobs;

use Log;
use Redis;


use Carbon\Carbon;

use App\Components\Utils\LogUtil;
use App\Components\Util\TimeUtil;
use App\Components\Utils\MethodUtil;
use App\Models\Gaea\CiDockerImage;
use App\Components\Utils\ErrorCodes;

use App\Components\Jobdone\Jobdone;


class CiBuildDockerImageJob extends BaseJob
{
    protected $object;

    public function __construct($obj) 
    {
        $this->object = $obj;
        parent::__construct($obj);
    }

    public function doHandle()
    {
        $image = $this->object;

        $imageName = $image['image_name'];
        $version   = $image['version'];
        $params    = $image['params'];
        
        $ciDockerImage = CiDockerImage::where('name', $imageName)->first();

        if ($ciDockerImage == null) {
            LogUtil::error('Build docker cannot find this dockerimage', [$imageName], LogUtil::LOG_JOB);
            return true;
        }

        $result = $ciDockerImage->generateCiDeployDockerImage($imageName, $version, $params);

        if ($result['errno'] == ErrorCodes::ERR_FAILURE) {
            LogUtil::error('Build docker jobdone failed', ['image' => $image, 'result' => $result], LogUtil::LOG_JOB);
            return true;
        }

        
        $output = $result['data'];
        $jid = $output['jid'];


        $ciDockerImage->jid = $jid;
        $ciDockerImage->status = CiDockerImage::IMAGE_CREATE_STATUS_CREATING;
        $ciDockerImage->use_time = 0;
        $ciDockerImage->save();

        $exceptionCount = 3;      // 连续3次异常要退出
        while (true) {
            $result = app('jobdone')->doJob(JobDone::API_RET_QUERY, ['jid' => $jid], $output);
            if ($result) {
                if ($output['status'] == JobDone::JOB_RUNNING) {
                    $ciDockerImage->use_time += 3;
                    $ciDockerImage->save();
                    sleep(2);
                    continue;
                }
                $ciDockerImage->use_time += 3;
                $ciDockerImage->save();
                break;
            } else {      // 异常
                LogUtil::error('Build docker jobdone query result failed', [$output], LogUtil::LOG_JOB);

                if ($exceptionCount <= 0) {
                    $ciDockerImage->status  = CiDockerImage::IMAGE_CREATE_STATUS_FAILED;
                    $ciDockerImage->save();
                    return true;
                }
                --$exceptionCount;
            }
        }

        $ciDockerImage->status = CiDockerImage::IMAGE_CREATE_STATUS_DONE;
        $ciDockerImage->save();

        return true;
    }
}

