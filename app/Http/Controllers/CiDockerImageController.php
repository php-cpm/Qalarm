<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Redis;
use Cookie;
use Exception;
use RuntimeException;
use App\Exceptions\ApiException;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Carbon\Carbon;

use App\Components\Utils\Paginator; 
use App\Components\Utils\ErrorCodes;
use App\Components\Utils\Constants;
use App\Components\Utils\MethodUtil;
use App\Components\Utils\LogUtil;
use App\Components\JobDone\JobDone;

use App\Models\RedisModel;
use App\Models\Gaea\AdminUser;
use App\Models\Gaea\OpsK8sHost;
use App\Models\Gaea\CiDockerImage;
use App\Models\Gaea\CiDockerImageSegment;

use App\Jobs\CiBuildDockerImageJob;


class CiDockerImageController extends Controller
{

    /**
     * @brief fetchCiDockerServices 取软件安装和服务安装的名称
     * @param $request
     * @return 
     */
    public function fetchCiDockerServices(Request $request)
    {/*{{{*/
        CiDockerImageSegment::softwares();

        return response()->clientSuccess([
            'languages'       =>  CiDockerImageSegment::languages(),
            'softwares'  => CiDockerImageSegment::softwares()
        ]);
    }/*}}}*/
    
    public function fetchCiDockerImages(Request $request)
    {/*{{{*/
        $status = $request->input('status', '');
        $query = CiDockerImage::orderBy('created_at', 'desc');

        if (!empty($status)) {
            $query->where('status', $status);
        }

        $paginator = new Paginator($request);
        $hosts = $paginator->runQuery($query);

        return $this->responseList($paginator, $hosts);
    }/*}}}*/

    public function assembleCiDockerImage(Request $request)
    {/*{{{*/
        $this->validate($request, [
            'segments'            => 'required',
            'name'                => 'required',
            'ports'               => 'required',
        ]);

        $name     = $request->input('name');
        $segments = $request->input('segments');
        $ports    = $request->input('ports');

        $segmentsList = json_decode($segments, true);
        if ($segmentsList == null) {
            return response()->clientError(ErrorCodes::ERR_FAILURE, '定制数据不是json数据');
        }

        $dockerImage = CiDockerImage::where('name', $name)->first();

        if ($dockerImage != null) {
            return response()->clientError(ErrorCodes::ERR_FAILURE, '镜像名重复');
        }

        // 处理服务端口
        $portsTmp = explode('|', $ports);

        foreach ($portsTmp as $port) {
            if (!is_numeric($port)) {
                return response()->clientError(ErrorCodes::ERR_FAILURE, '端口必须是数字');
            }
            
            if ($port <= 1024 || $port >= 65535) {
                return response()->clientError(ErrorCodes::ERR_FAILURE, '请使用1024--65535之间的端口号');
            }
        }

        $params['ports'] = join(' ', $portsTmp);

        list($dockerfile, $supervisor) = CiDockerImageSegment::fetchDockerFileAndSupervisor($segmentsList, $params);

        $dockerImage = new CiDockerImage();
        {
            $dockerImage->name      = $name;
            $dockerImage->segments  = $segments;
            $dockerImage->status    = CiDockerImage::IMAGE_CREATE_STATUS_WAIT;
            $dockerImage->dockerfile= $dockerfile;
            $dockerImage->supervisor= $supervisor;
            $dockerImage->ports     = $request->input('ports');
        }
        $dockerImage->save();

        // 扔队列执行
        $jobData = MethodUtil::assemblyJobData(['image_name' =>$dockerImage->name, 'version' => 'init', 'params' => []]);
        $job = new CiBuildDockerImageJob($jobData);
        $this->dispatch($job); 

        return response()->clientSuccess([]);
    }/*}}}*/

    // 重新构建基础镜像
    public function reAssembleCiDockerImage(Request $request)
    {/*{{{*/
        $this->validate($request, [
            'id'      => 'required'
        ]);

        $dockerImage = CiDockerImage::where('id', $request->input('id'))->first();
        if ($dockerImage == null) {
            return response()->clientError(ErrorCodes::ERR_FAILURE, '找不到此镜像');
        }

        // 扔队列执行
        $jobData = MethodUtil::assemblyJobData(['image_name' =>$dockerImage->name, 'version' => 'init', 'params' => []]);
        $job = new CiBuildDockerImageJob($jobData);
        $this->dispatch($job); 

        return response()->clientSuccess([]);
    }/*}}}*/
    
    public function updateCiDockerImage(Request $request)
    {/*{{{*/
        $this->validate($request, [
            'image_name'                => 'required',
        ]);

        $name     = $request->input('image_name');

        $used = OpsK8sHost::where('image_name', $name)->get()->toArray();
        if (count($used) != 0) {
            return response()->clientError(ErrorCodes::ERR_FAILURE, '有项目正在使用此镜像，请先删除');
        }

        $image = CiDockerImage::where('name', $name)->first();
        CiDockerImage::destroy($image->id);

        return response()->clientSuccess([]);
    }/*}}}*/

    /**
     * @brief generateCiDeployDockerImage 生成用来部署的dockerimage
     * @param $request
     * @return $newDockerImageUrl
     */
    public function generateCiDeployDockerImage(Request $request)
    {/*{{{*/
    }/*}}}*/

    // 获取docker 构建日志
    public function fetchCiDockerBuildLog(Request $request)
    {/*{{{*/
        $this->validate($request, [
            'docker_name'  => 'required'
         ]);

        $dockerImage = CiDockerImage::where('name', $request->input('docker_name'))->first();
        
        $result = app('jobdone')->doJob(JobDone::API_RET_QUERY, ['jid' => $dockerImage->jid], $output);
        $log = '';
        if ($result) {
            if ($output['status'] != JobDone::JOB_RUNNING) {
                $log = isset($output['return'][0]['output'])? $output['return'][0]['output']:'';
            }
        } else {
            $log = $output;
        }

        if (empty($log)) {
            $log = '无';
        }

        return response()->clientSuccess($log);

    }/*}}}*/

    protected function responseList($paginator, $collection, $callee='export')
    {/*{{{*/
        return response()->clientSuccess([
            'page'     => $paginator->info($collection),
                'results'  => $collection->map(function($item, $key) use ($callee) {
                    return call_user_func([$item, $callee]);
                }),
                ]);
    }/*}}}*/
}
