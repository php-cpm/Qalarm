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
use App\Models\Gaea\OpsHost;
use App\Models\Gaea\CiProject;
use App\Models\Gaea\CiProjectDns;
use App\Models\Gaea\CiProjectHost;
use App\Models\Gaea\OpsK8sHost;
use App\Models\Gaea\CiDockerImage;

use App\Components\Kubernetes\Kubernetes;

class CiHostController extends Controller
{
    public function fetchCiProjectHosts(Request $request)
    {/*{{{*/
        $this->validate($request, [
            'project_id'    =>  'required'
        ]);

        $projectId = $request->input('project_id');
        $query = CiProjectHost::orderBy('created_at', 'desc');
        $query->where('project_id' , $projectId);

        $paginator = new Paginator($request);
        $hosts = $paginator->runQuery($query);

        return $this->responseList($paginator, $hosts);
    }/*}}}*/

    public function updateCiProjectHost(Request $request)
    {/*{{{*/
        $this->validate($request, [
            'action'              => 'required|in:add,update',
            'project_id'          => 'required',
            'id'                  => 'required_if:action,update',
            'opt'                 => 'required_if:action,update',
            'host_type'           => 'required_if:action,add',
            'host_env_type'       => 'required_if:action,add',
            'host_cluster'        => 'required_if:action,add',
            'vm_host'             => 'required_if:host_type,'.CiProjectHost::HOST_TYPE_VM,
            'docker_quota_type'   => 'required_if:host_type,'.CiProjectHost::HOST_TYPE_DOCKER,
            'docker_replica'      => 'required_if:host_type,'.CiProjectHost::HOST_TYPE_DOCKER,
            'docker_image'        => 'required_if:host_type,'.CiProjectHost::HOST_TYPE_DOCKER,
        ]);


        $action = $request->input('action');
        $projectId    = $request->input('project_id');

        // 新建添加
        if ($action == 'add') {
            $ciProjectHost = new CiProjectHost();
            {
                $ciProjectHost->host_type          = $request->input('host_type');
                if ($ciProjectHost->host_type == CiProjectHost::HOST_TYPE_VM) {
                    $hostname = $request->input('vm_host');
                    // 一台主机可能会有多个ip，所以用like查询
                    $host = OpsHost::Orwhere('server_name', $hostname)->Orwhere('ip', 'LIKE', "%$hostname%")->first();
                    if ($host == null) {
                        return response()->clientError(ErrorCodes::ERR_FAILURE, '主机不存在');
                    }

                    // 查找此机器是否已经添加
                    $ciHost = CiProjectHost::where('project_id', $projectId)->where('vm_id', $host->id)->first();
                    if ($ciHost != null) {
                        return response()->clientError(ErrorCodes::ERR_FAILURE, '请不要重复添加同一主机');
                    }

                    $ciProjectHost->vm_id          = $host->id;
                } else if ($ciProjectHost->host_type  == CiProjectHost::HOST_TYPE_DOCKER) {
                    $dockerImageName = $request->input('docker_image');
                    $dockerImage = CiDockerImage::where('name', $dockerImageName)->first();
                    $ports = explode('|', $dockerImage->ports);

                    $project = CiProject::where('project_id', $projectId)->first();
                    Kubernetes::setEnv($request->input('host_env_type') == CiProjectHost::HOST_ENV_TEST ? Kubernetes::ENV_TEST : Kubernetes::ENV_PRODUCTION);
                    //Kubernetes::setEnv($ciProjectHost->host_env_type == CiProjectHost::HOST_ENV_TEST ? Kubernetes::ENV_TEST : Kubernetes::ENV_TEST);
                    //$result = Kubernetes::createOrUpdateService($project->project_name, $ports); 
                    //$ciCdStepCurrentStep = $request->input('host_env_type') == CiProjectHost::HOST_ENV_TEST ? 'beta' : 'b_level'; 
                    $ciCdStepCurrentStep = $request->input('host_env_type') == CiProjectHost::HOST_ENV_TEST ? 'sandbox' : 'production'; 
                    $svcNameSubfix = rand(1000, 9999); 
                    $result = Kubernetes::createOrUpdateService($project->project_name, $ports, $ciCdStepCurrentStep, $svcNameSubfix); 

                    if ($result['errno'] != ErrorCodes::ERR_SUCCESS) {
                        return response()->clientError($result['errno'], $result['errmsg']);
                    }

                    $k8sHost = new OpsK8sHost(); {
                        $k8sHost->image_name           = $request->input('docker_image');
                        $k8sHost->resource_quota_type  = $request->input('docker_quota_type');
                        $k8sHost->ip                   = $result['data']['cluster_ip']; 
                        $k8sHost->replica              = $request->input('docker_replica');
                        $k8sHost->namespace            = Kubernetes::GAEA_K8S_NAMESPACE;
                        $k8sHost->k8s_service_name     = Kubernetes::getSvcName($project->project_name, $ciCdStepCurrentStep, $svcNameSubfix);
                    }
                    $k8sHost->save();

                    $ciProjectHost->k8s_id         = $k8sHost->id;
                }

                $ciProjectHost->project_id         = $request->input('project_id');
                $ciProjectHost->host_env_type      = $request->input('host_env_type');
                $ciProjectHost->host_tag           = $request->input('host_cluster');
                $ciProjectHost->host_is_slave      = CiProjectHost::HOST_SLAVE_NO;
                $ciProjectHost->enable             = CiProjectHost::HOST_ENABLE;
            }
            $ciProjectHost->save();
        }


        // 状态更新
        if ($action == 'update') {
            $opt = $request->input('opt');
            $ciProjectHost = CiProjectHost::where('id', $request->input('id'))->first();

            switch ($opt) {
            case 'slave':
                $ciProjectHost->host_is_slave  = CiProjectHost::HOST_SLAVE_YES;
                // FIXME
                if ($ciProjectHost->host_type == CiProjectHost::HOST_TYPE_DOCKER) {
                    Kubernetes::setEnv($ciProjectHost->host_env_type == CiProjectHost::HOST_ENV_TEST ? Kubernetes::ENV_TEST : Kubernetes::ENV_PRODUCTION);
                    //Kubernetes::setEnv($ciProjectHost->host_env_type == CiProjectHost::HOST_ENV_TEST ? Kubernetes::ENV_TEST : Kubernetes::ENV_TEST);

                    $k8sHostInfo = OpsK8sHost::where('id', $ciProjectHost->k8s_id)->first();
                    $svcName = $k8sHostInfo->k8s_service_name;

                    $project = CiProject::where('project_id', $ciProjectHost->project_id)->first();
                    //$svcSelectorName = Kubernetes::getPodSelectorName($project->project_name, 'a-level');
                    $svcSelectorName = Kubernetes::getPodSelectorName($project->project_name, 'stage');

                    $result = Kubernetes::updateSvcSelector($svcName, $svcSelectorName);
                    if ($result['errno'] != ErrorCodes::ERR_SUCCESS) {
                        return response()->clientError($result['errno'], $result['errmsg']);
                    }
                }

                break;
            case 'unslave':
                $ciProjectHost->host_is_slave  = CiProjectHost::HOST_SLAVE_NO;
                // FIXME
                if ($ciProjectHost->host_type == CiProjectHost::HOST_TYPE_DOCKER) {
                    Kubernetes::setEnv($ciProjectHost->host_env_type == CiProjectHost::HOST_ENV_TEST ? Kubernetes::ENV_TEST : Kubernetes::ENV_PRODUCTION);
                    //Kubernetes::setEnv($ciProjectHost->host_env_type == CiProjectHost::HOST_ENV_TEST ? Kubernetes::ENV_TEST : Kubernetes::ENV_TEST);

                    $k8sHostInfo = OpsK8sHost::where('id', $ciProjectHost->k8s_id)->first();
                    $svcName = $k8sHostInfo->k8s_service_name;

                    $project = CiProject::where('project_id', $ciProjectHost->project_id)->first();
                    $svcSelectorName = Kubernetes::getPodSelectorName($project->project_name, 'production');

                    $result = Kubernetes::updateSvcSelector($svcName, $svcSelectorName);
                    if ($result['errno'] != ErrorCodes::ERR_SUCCESS) {
                        return response()->clientError($result['errno'], $result['errmsg']);
                    }
                }
                break;
            case 'enable':
                $ciProjectHost->enable         = CiProjectHost::HOST_ENABLE;
                break;
            case 'disable':
                // 如果机器正在对外提供服务，则需要下线后才能禁用
                $dnses  = CiProjectDns::where('project_id', $projectId)->get();
                $hostName = $request->input('host_name');
                $usedDnsName = [];

                foreach ($dnses as $dns) {
                    $tmpHosts = json_decode($dns->hostinfo, true);
                    foreach ($tmpHosts as $host) {
                        if ($host['host'] == $hostName && $host['status'] == CiProjectDns::DNS_HOST_STATUS_UP) {
                            $usedDnsName[] = $dns->name;
                        }
                    }
                }

                if (count($usedDnsName) != 0) {
                    return response()->clientError(ErrorCodes::ERR_FAILURE, '请下线此机器后再停用,机器服役于域名：'. join(',', $usedDnsName));
                }

                $ciProjectHost->enable         = CiProjectHost::HOST_DISABLE;
                break;
            case 'delete':
                // 如果机器正在对外提供服务，则需要下线后才能禁用
                $dnses  = CiProjectDns::where('project_id', $projectId)->get();
                $hostName = $request->input('host_name');
                $usedDnsName = [];

                foreach ($dnses as $dns) {
                    $tmpHosts = json_decode($dns->hostinfo, true);
                    foreach ($tmpHosts as $host) {
                        if ($host['host'] == $hostName && $host['status'] == CiProjectDns::DNS_HOST_STATUS_UP) {
                            $usedDnsName[] = $dns->name;
                        }
                    }
                }

                if (count($usedDnsName) != 0) {
                    return response()->clientError(ErrorCodes::ERR_FAILURE, '请下线此机器后再删除,机器服役于域名：'. join(',', $usedDnsName));
                }

                // FIXME
                if ($ciProjectHost->host_type == CiProjectHost::HOST_TYPE_DOCKER) {
                    Kubernetes::setEnv($ciProjectHost->host_env_type == CiProjectHost::HOST_ENV_TEST ? Kubernetes::ENV_TEST : Kubernetes::ENV_PRODUCTION);
                    //$project = CiProject::where('project_id', $ciProjectHost->project_id)->first();
                    //$result = Kubernetes::deleteService($project->project_name);
                    
                    $k8sHostInfo = OpsK8sHost::where('id', $ciProjectHost->k8s_id)->first();
                    $svcName = $k8sHostInfo->k8s_service_name;

                    dd($svcName);
                    $result = Kubernetes::deleteService($svcName);
                    if ($result['errno'] != ErrorCodes::ERR_SUCCESS) {
                        return response()->clientError($result['errno'], $result['errmsg']);
                    }
                }
                CiProjectHost::destroy($ciProjectHost->id);
                break;
            }
            $ciProjectHost->save();
        }

        return response()->clientSuccess([]);
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
